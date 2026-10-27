#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
backup_dcm_agent.py — Agente de Backup Local DCM para LIDiag y Sincronización QNAP (Qsync)

Este script se ejecuta localmente en el servidor de cada sede/hospital.
Realiza el volcado local de MySQL con mysqldump a alta velocidad de disco (SSD/NVMe),
lo comprime a formato ZIP en la carpeta destino local (C:\\backups_dcm) y purga los
archivos antiguos que superen los días de retención (30 días).

Funcionalidades de grado clínico:
1. Control de espacio libre en disco (Pre-flight check).
2. Volcado seguro con mysqldump (--hex-blob, --max_allowed_packet=512M, auto-detección MySQL 5.7 / 8.0+).
3. Prueba de integridad CRC32 del ZIP antes de borrar el SQL crudo.
4. Generación de checksum criptográfico SHA-256 (.sha256).
5. Manifiesto de telemetría y salud centralizado (backup_status_sede_{id}.json) sincronizado por Qsync.
6. Purga automática de backups y hashes antiguos.

Requisitos:
- Python 3.7+ (Solo bibliotecas estándar: no requiere pip install).
- Acceso local a mysqldump (MySQL Server o MariaDB).

Uso:
  python backup_dcm_agent.py
  python backup_dcm_agent.py --config config.json
  python backup_dcm_agent.py --sede 1 --nombre espinal --destination-dir "C:\\backups_dcm"

Autor: Jhon Javer Lozano Arce
Fecha: 2026-10-19
"""

import argparse
import atexit
import datetime
import glob
import hashlib
import json
import os
import re
import shutil
import socket
import subprocess
import sys
import time
import zipfile


AGENT_DIR = os.path.dirname(os.path.abspath(__file__))
_venv_site = os.path.join(AGENT_DIR, "venv", "Lib", "site-packages")
if os.path.isdir(_venv_site) and _venv_site not in sys.path:
    sys.path.insert(0, _venv_site)
LOGS_DIR = os.path.join(AGENT_DIR, "logs")
os.makedirs(LOGS_DIR, exist_ok=True)
LOG_FILE = os.path.join(LOGS_DIR, "backup_db.log")
LOCK_FILE = os.path.join(LOGS_DIR, "backup_db.lock")


def is_pid_running(pid: int) -> bool:
    """Verifica si un proceso Python con ese PID se encuentra actualmente en ejecución."""
    if pid <= 0:
        return False
    if sys.platform == "win32":
        try:
            res = subprocess.run(
                ["tasklist", "/FI", f"PID eq {pid}", "/FI", "IMAGENAME eq python*", "/NH"],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
                check=False,
            )
            out = (res.stdout or "").strip()
            return bool(out and str(pid) in out and "no hay tareas" not in out.lower() and "info:" not in out.lower())
        except Exception:
            return False
    else:
        try:
            os.kill(pid, 0)
            return True
        except OSError:
            return False


def acquire_process_lock() -> bool:
    """Asegura que sólo un respaldo de base de datos se ejecute a la vez."""
    if os.path.isfile(LOCK_FILE):
        try:
            with open(LOCK_FILE, "r", encoding="utf-8") as f:
                content = f.read().strip()
                if content.isdigit():
                    prev_pid = int(content)
                    if is_pid_running(prev_pid):
                        print(f"[AVISO] Ya existe un respaldo de MySQL en ejecución (PID {prev_pid}). Se omite nueva ejecución.")
                        return False
        except Exception:
            pass

    try:
        with open(LOCK_FILE, "w", encoding="utf-8") as f:
            f.write(str(os.getpid()))
        return True
    except Exception as e:
        print(f"[ADVERTENCIA] No se pudo crear archivo de bloqueo {LOCK_FILE}: {e}")
        return True


def release_process_lock():
    """Libera el archivo de bloqueo al salir."""
    try:
        if os.path.isfile(LOCK_FILE):
            os.remove(LOCK_FILE)
    except Exception:
        pass


atexit.register(release_process_lock)


def check_mysql_socket(host: str, port: int = 3306, timeout: float = 3.0) -> bool:
    """Verifica conectividad TCP al puerto de MySQL antes de invocar mysqldump."""
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except (socket.timeout, socket.error, OSError):
        return False


HEALTHCHECK_FILE = os.path.join(LOGS_DIR, "healthcheck_agente.json")


def update_unified_healthcheck(sede_id: int, sede_nombre: str, service: str, info: dict, unc_dest: str = ""):
    """Actualiza el estado consolidado de los servicios en logs/healthcheck_agente.json."""
    data = {"sede_id": sede_id, "sede_nombre": sede_nombre, "servicios": {}}
    if os.path.isfile(HEALTHCHECK_FILE):
        try:
            with open(HEALTHCHECK_FILE, "r", encoding="utf-8") as f:
                existing = json.load(f)
                if isinstance(existing, dict):
                    data = existing
        except Exception:
            pass

    data["sede_id"] = sede_id
    data["sede_nombre"] = sede_nombre
    data["ultima_actualizacion"] = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    if "servicios" not in data or not isinstance(data["servicios"], dict):
        data["servicios"] = {}
    data["servicios"][service] = info

    try:
        with open(HEALTHCHECK_FILE, "w", encoding="utf-8") as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        if unc_dest and os.path.isdir(unc_dest):
            with open(os.path.join(unc_dest, "healthcheck_agente.json"), "w", encoding="utf-8") as f:
                json.dump(data, f, indent=2, ensure_ascii=False)
    except Exception:
        pass


class TeeLogger:
    """Duplica toda la salida de consola hacia logs/backup_db.log de forma segura."""
    def __init__(self, log_path, stream):
        self.stream = stream
        self.log_file = open(log_path, "a", encoding="utf-8")

    def write(self, message):
        self.stream.write(message)
        self.log_file.write(message)
        self.log_file.flush()

    def flush(self):
        self.stream.flush()
        self.log_file.flush()


def parse_arguments():
    parser = argparse.ArgumentParser(description="Agente de Backup Local DCM para LIDiag / QNAP Qsync")
    parser.add_argument("--config", "-c", default="config.json", help="Ruta al archivo de configuración JSON (por defecto: config.json)")
    parser.add_argument("--sede", type=int, help="ID de la sede (anula valor del config.json)")
    parser.add_argument("--nombre", "--sede-nombre", dest="sede_nombre", help="Nombre o slug de la sede (ej. espinal, chaparral)")
    parser.add_argument("--db-host", help="Host de MySQL (anula config)")
    parser.add_argument("--db-port", type=int, help="Puerto de MySQL (anula config)")
    parser.add_argument("--db-name", help="Nombre de la base de datos (anula config)")
    parser.add_argument("--db-user", help="Usuario de MySQL (anula config)")
    parser.add_argument("--db-password", help="Contraseña de MySQL (anula config)")
    parser.add_argument("--mysqldump-path", "--mysqldump", dest="mysqldump_path", help="Ruta completa al ejecutable mysqldump")
    parser.add_argument("--destination-dir", "--dest", dest="destination_dir", help="Carpeta destino local sincronizada por Qsync (ej. C:\\backups_dcm)")
    parser.add_argument("--retention-days", type=int, dest="retention_days", help="Días de retención de backups locales (por defecto: 30)")
    parser.add_argument("--min-free-gb", type=float, dest="min_free_space_gb", help="Espacio libre mínimo en disco en GB (por defecto: 5)")
    parser.add_argument("--qnap-ip", dest="qnap_ip", help="IP del servidor QNAP (ej. 10.35.47.21)")
    parser.add_argument("--qnap-share", dest="qnap_share_backups", help="Recurso compartido en QNAP para backups (ej. backups_espinal)")
    return parser.parse_args()


def parse_tolerant_json(content):
    r"""
    Parsea JSON con tolerancia a rutas de Windows con barras invertidas simples (\).
    En Windows, al escribir 'C:\Program Files\...' o 'C:\backups_dcm' el parser estándar
    falla con 'Invalid \escape' (\b es backspace, \P es escape inválido).
    """
    try:
        return json.loads(content)
    except Exception:
        pass

    def fix_string(match):
        val = match.group(0)
        inner = val[1:-1]
        inner = inner.replace('\\"', "___ESCAPED_QUOTE___")
        inner = inner.replace("\\", "/")
        inner = inner.replace("___ESCAPED_QUOTE___", '\\"')
        return f'"{inner}"'

    fixed = re.sub(r'"(?:[^"\\]|\\.)*"', fix_string, content)
    try:
        return json.loads(fixed)
    except Exception:
        return json.loads(content.replace("\\", "/"))


def load_config(config_path, args):
    if not os.path.exists(config_path):
        print("=========================================================")
        print(f"[ERROR] No se encontró el archivo de configuración: {config_path}")
        print("Copia 'config.json.example' a 'config.json' y ajusta las credenciales de esta sede.")
        print("=========================================================")
        sys.exit(1)

    try:
        with open(config_path, "r", encoding="utf-8") as f:
            content = f.read()
        loaded = parse_tolerant_json(content)
        if not isinstance(loaded, dict):
            raise ValueError("El contenido del archivo no es un objeto JSON válido.")
    except Exception as e:
        print("=========================================================")
        print(f"[ERROR] No se pudo leer o parsear el archivo '{config_path}': {e}")
        print("Verifica la sintaxis del archivo JSON antes de continuar.")
        print("=========================================================")
        sys.exit(1)

    # Compatibilidad de claves alternativas
    if "mysqldump_bin" in loaded and not loaded.get("mysqldump_path"):
        loaded["mysqldump_path"] = loaded["mysqldump_bin"]
    if "sede_slug" in loaded and not loaded.get("sede_nombre"):
        loaded["sede_nombre"] = loaded["sede_slug"]

    # Validar campos obligatorios de la sede
    required_fields = ["db_name", "db_user"]
    missing = [field for field in required_fields if field not in loaded]
    if missing:
        print("=========================================================")
        print(f"[ERROR] Faltan campos obligatorios en '{config_path}': {', '.join(missing)}")
        print("Por favor especifícalos en el archivo de configuración.")
        print("=========================================================")
        sys.exit(1)

    # Configuración base con valores operativos por defecto
    config = {
        "sede_id": loaded.get("sede_id", 0),
        "sede_nombre": loaded.get("sede_nombre", ""),
        "db_host": loaded.get("db_host", "127.0.0.1"),
        "db_port": int(loaded.get("db_port", 3306)),
        "db_name": loaded["db_name"],
        "db_user": loaded["db_user"],
        "db_password": loaded.get("db_password", ""),
        "mysqldump_path": loaded.get("mysqldump_path", ""),
        "destination_dir": loaded.get("destination_dir", "C:\\backups_dcm"),
        "retention_days": int(loaded.get("retention_days", 30)),
        "min_free_space_gb": float(loaded.get("min_free_space_gb", 5.0)),
        "temp_dir": loaded.get("temp_dir", "./temp_backups"),
        "qnap_ip": loaded.get("qnap_ip", "").strip(),
        "qnap_user": loaded.get("qnap_user", "").strip(),
        "qnap_password": loaded.get("qnap_password", ""),
        "qnap_share_backups": (loaded.get("qnap_share_backups") or loaded.get("qnap_share", "")).strip(),
    }

    # Sobreescribir con argumentos de línea de comandos si fueron suministrados
    if args.sede is not None:
        config["sede_id"] = args.sede
    if args.sede_nombre:
        config["sede_nombre"] = args.sede_nombre
    if args.db_host:
        config["db_host"] = args.db_host
    if args.db_port:
        config["db_port"] = args.db_port
    if args.db_name:
        config["db_name"] = args.db_name
    if args.db_user:
        config["db_user"] = args.db_user
    if args.db_password is not None:
        config["db_password"] = args.db_password
    if args.mysqldump_path:
        config["mysqldump_path"] = args.mysqldump_path
    if args.destination_dir:
        config["destination_dir"] = args.destination_dir
    if args.retention_days is not None:
        config["retention_days"] = args.retention_days
    if args.min_free_space_gb is not None:
        config["min_free_space_gb"] = args.min_free_space_gb
    if getattr(args, "qnap_ip", None):
        config["qnap_ip"] = args.qnap_ip
    if getattr(args, "qnap_share_backups", None):
        config["qnap_share_backups"] = args.qnap_share_backups

    return config


def format_bytes(bytes_num):
    for unit in ["B", "KB", "MB", "GB", "TB"]:
        if bytes_num < 1024.0:
            return f"{bytes_num:.2f} {unit}"
        bytes_num /= 1024.0
    return f"{bytes_num:.2f} PB"


def sanitize_filename_part(text):
    if not text:
        return ""
    cleaned = re.sub(r"[^a-zA-Z0-9_\-]+", "_", str(text).strip().lower())
    return cleaned.strip("_")


def calculate_sha256(filepath):
    """Calcula el hash SHA-256 en bloques de 1MB para bajo consumo de memoria."""
    h = hashlib.sha256()
    with open(filepath, "rb") as f:
        while chunk := f.read(1024 * 1024):
            h.update(chunk)
    return h.hexdigest()


def check_disk_space(target_path, min_gb_required):
    """Verifica si el disco donde reside la ruta tiene suficiente espacio disponible."""
    try:
        # Asegurar directorio o ruta padre existente para disk_usage
        check_dir = target_path
        while check_dir and not os.path.exists(check_dir):
            parent = os.path.dirname(check_dir)
            if parent == check_dir:
                break
            check_dir = parent
        usage = shutil.disk_usage(check_dir if check_dir else ".")
        free_gb = usage.free / (1024 ** 3)
        return usage.free, free_gb, (free_gb >= min_gb_required)
    except Exception as e:
        print(f"[ADVERTENCIA] No se pudo consultar espacio en disco: {e}")
        return 0, 999.0, True


def find_mysqldump(custom_path):
    """
    Resuelve el ejecutable mysqldump según la ruta configurada en config.json
    o buscándolo en el PATH del sistema.
    """
    if custom_path:
        clean_path = custom_path.strip("\"' \t\r\n")
        clean_path = os.path.expandvars(os.path.expanduser(clean_path))
        if os.path.isfile(clean_path):
            return clean_path
        if shutil.which(clean_path):
            return shutil.which(clean_path)
        print("=========================================================")
        print(f"[ERROR] La ruta 'mysqldump_path' configurada en config.json no existe: {custom_path}")
        print("Verifica la ruta al ejecutable mysqldump.exe en el servidor.")
        print("=========================================================")
        sys.exit(1)

    # Si no se configuró ruta, buscar en el PATH del sistema
    which_path = shutil.which("mysqldump")
    if which_path:
        return which_path

    print("=========================================================")
    print("[ERROR] No se encontró el ejecutable 'mysqldump' en el PATH del sistema.")
    print("Por favor especifica la ruta completa en el parámetro 'mysqldump_path' de config.json.")
    print("=========================================================")
    sys.exit(1)


def check_column_statistics_supported(mysqldump_bin):
    """
    Verifica si mysqldump soporta la opción --column-statistics (introducida en MySQL 8.0+).
    En MySQL 5.7 o MariaDB dicha opción no existe y provoca error fatal con código 7.
    """
    try:
        proc = subprocess.run(
            [mysqldump_bin, "--help"],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            text=True,
            check=False,
        )
        return "column-statistics" in (proc.stdout or "")
    except Exception:
        return False


def purge_old_backups(destination_dir, retention_days):
    if retention_days <= 0:
        return 0, 0

    cutoff_time = time.time() - (retention_days * 86400)
    purged_count = 0
    purged_bytes = 0

    pattern = os.path.join(destination_dir, "backup_dcm_*.zip")
    for file_path in glob.glob(pattern):
        try:
            mtime = os.path.getmtime(file_path)
            if mtime < cutoff_time:
                file_size = os.path.getsize(file_path)
                os.remove(file_path)
                purged_count += 1
                purged_bytes += file_size
                print(f"      [PURGA] Eliminado por retención (> {retention_days} días): {os.path.basename(file_path)} ({format_bytes(file_size)})")

                # Purgar archivo .sha256 gemelo si existe
                sha_file = f"{file_path}.sha256"
                if os.path.exists(sha_file):
                    try:
                        os.remove(sha_file)
                    except Exception:
                        pass
        except Exception as e:
            print(f"      [ADVERTENCIA] No se pudo purgar {file_path}: {e}")

    return purged_count, purged_bytes


def write_status_manifest(dest_dir, sede_id, status_data):
    """Guarda el archivo de estado y telemetría para monitoreo en QNAP."""
    try:
        slug = sanitize_filename_part(status_data.get("sede_nombre", "local"))
        sede_tag = f"sede_{sede_id}" if sede_id else f"sede_{slug}"
        manifest_path = os.path.join(dest_dir, f"backup_status_{sede_tag}.json")
        with open(manifest_path, "w", encoding="utf-8") as f:
            json.dump(status_data, f, indent=2, ensure_ascii=False)
        # También guardar alias genérico
        alias_path = os.path.join(dest_dir, "backup_status.json")
        with open(alias_path, "w", encoding="utf-8") as f:
            json.dump(status_data, f, indent=2, ensure_ascii=False)
        return manifest_path
    except Exception as e:
        print(f"[ADVERTENCIA] No se pudo guardar manifiesto de estado: {e}")
        return None


def check_qnap_port(ip: str, port: int = 445, timeout: float = 3.0) -> bool:
    """Verifica si el puerto SMB (445) del QNAP responde dentro del timeout."""
    try:
        with socket.create_connection((ip, port), timeout=timeout):
            return True
    except (socket.timeout, socket.error, OSError):
        return False


def authenticate_smb(qnap_ip: str, share: str, user: str, password: str) -> bool:
    """Autentica la sesión SMB contra el recurso compartido del QNAP con net use."""
    unc_path = f"\\\\{qnap_ip}\\{share}"
    cmd = ["net", "use", unc_path, password, f"/user:{user}", "/persistent:no"]
    try:
        res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=15)
        if res.returncode == 0 or "Ya está conectado" in res.stderr or "already" in res.stderr.lower():
            return True
        return os.path.isdir(unc_path)
    except Exception:
        return os.path.isdir(unc_path)


def transfer_backup_to_qnap(config: dict, files_to_transfer: list, retention_days: int) -> dict:
    """Transmite inmediatamente los archivos del backup (.zip, .sha256, status.json) al QNAP."""
    qnap_ip = config.get("qnap_ip", "").strip()
    share = config.get("qnap_share_backups", "").strip()
    user = config.get("qnap_user", "").strip()
    password = config.get("qnap_password", "")

    if not qnap_ip or not share:
        print("\n[QNAP] Sin IP o recurso compartido 'qnap_share_backups' en config.json. El backup se conserva localmente.")
        return {"estado": "SKIPPED", "motivo": "No configurado"}

    unc_dest = f"\\\\{qnap_ip}\\{share}"
    print(f"\n[QNAP] Transmitiendo backup inmediatamente al NAS ({unc_dest})...")

    # 1. Probar puerto 445
    if not check_qnap_port(qnap_ip, port=445):
        err = f"QNAP {qnap_ip}:445 no responde. Se conserva el backup local intacto."
        print(f"      [ADVERTENCIA] {err}")
        return {"estado": "OFFLINE", "error": err}

    # 2. Autenticar SMB
    if not authenticate_smb(qnap_ip, share, user, password):
        if not os.path.isdir(unc_dest):
            err = f"No se pudo acceder al recurso compartido {unc_dest}"
            print(f"      [ERROR QNAP] {err}")
            return {"estado": "AUTH_ERROR", "error": err}

    t_start = time.time()
    total_bytes = 0

    try:
        os.makedirs(unc_dest, exist_ok=True)
        for local_file in files_to_transfer:
            if not local_file or not os.path.isfile(local_file):
                continue
            fname = os.path.basename(local_file)
            remote_file = os.path.join(unc_dest, fname)
            fsize = os.path.getsize(local_file)
            print(f"      Copiando {fname} ({format_bytes(fsize)})...")
            shutil.copy2(local_file, remote_file)
            # Verificación de integridad post-transferencia en la NAS
            if os.path.isfile(remote_file):
                remote_size = os.path.getsize(remote_file)
                if remote_size != fsize:
                    print(f"      [REINTENTO] Diferencia de tamaño ({remote_size} != {fsize}). Reintentando copia...")
                    shutil.copy2(local_file, remote_file)
                    remote_size = os.path.getsize(remote_file)
                    if remote_size != fsize:
                        raise IOError(f"Copia corrupta de {fname} en QNAP ({remote_size} bytes recibidos vs {fsize} esperados)")
            total_bytes += fsize

        upload_time = round(time.time() - t_start, 2)
        mb_rate = round((total_bytes / (1024 * 1024)) / upload_time, 2) if upload_time > 0 else 0.0
        print(f"      [OK] Backup transferido exitosamente al QNAP en {upload_time}s (~{mb_rate} MB/s).")

        # 3. Purga remota en la carpeta del QNAP
        purged_qnap, bytes_qnap = purge_old_backups(unc_dest, retention_days)
        if purged_qnap > 0:
            print(f"      [OK] Purga remota en QNAP: {purged_qnap} backup(s) antiguos eliminados ({format_bytes(bytes_qnap)} liberados).")

        return {
            "estado": "SUCCESS",
            "qnap_destino": unc_dest,
            "segundos": upload_time,
            "bytes_transferidos": total_bytes,
        }
    except Exception as e:
        err = f"Error durante la copia hacia el QNAP: {e}"
        print(f"      [ERROR QNAP] {err}")
        return {"estado": "ERROR", "error": err}


def main():
    try:
        sys.stdout = TeeLogger(LOG_FILE, sys.stdout)
        sys.stderr = TeeLogger(LOG_FILE, sys.stderr)
    except Exception:
        pass

    # Control de concurrencia: asegurar una sola ejecución simultánea
    if not acquire_process_lock():
        sys.exit(0)

    start_total_time = time.time()
    args = parse_arguments()
    config = load_config(args.config, args)

    print("=========================================================")
    print("      AGENTE LOCAL DE BACKUPS DCM — LIDiag / QNAP        ")
    print("=========================================================")
    print(f" Sede ID          : {config['sede_id']}" + (f" ({config['sede_nombre']})" if config.get("sede_nombre") else ""))
    print(f" Base de Datos    : {config['db_name']} @ {config['db_host']}:{config['db_port']}")
    print(f" Usuario MySQL    : {config['db_user']}")
    if config.get("mysqldump_path"):
        print(f" MySQLDump Path   : {config['mysqldump_path']}")
    print(f" Carpeta Destino  : {config['destination_dir']}")
    if config.get("qnap_ip") and config.get("qnap_share_backups"):
        print(f" Destino QNAP NAS : \\\\{config['qnap_ip']}\\{config['qnap_share_backups']}")
    print(f" Días Retención   : {config['retention_days']} días")
    print("=========================================================")

    # Asegurar existencia de directorios
    dest_dir = os.path.abspath(config.get("destination_dir", "C:\\backups_dcm"))
    temp_dir = os.path.abspath(config.get("temp_dir", "./temp_backups"))
    os.makedirs(dest_dir, exist_ok=True)
    os.makedirs(temp_dir, exist_ok=True)

    # 1. Pre-flight check: Conectividad TCP a MySQL PACS
    db_host = config.get("db_host", "127.0.0.1")
    db_port = int(config.get("db_port", 3306))
    if not check_mysql_socket(db_host, db_port):
        err_msg = f"No hay conexión con el servidor MySQL en {db_host}:{db_port}. Verifica que el servicio MySQL PACS esté en ejecución."
        print(f"[ERROR CRÍTICO] {err_msg}")
        write_status_manifest(dest_dir, config["sede_id"], {
            "sede_id": config["sede_id"],
            "sede_nombre": config.get("sede_nombre", ""),
            "fecha": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "estado": "MYSQL_OFFLINE",
            "error": err_msg,
        })
        sys.exit(1)

    # 2. Pre-flight check: Control de espacio libre en disco
    min_gb = config.get("min_free_space_gb", 5.0)
    free_bytes, free_gb, is_space_ok = check_disk_space(dest_dir, min_gb)
    print(f"[1/5] Verificación previa de disco: {format_bytes(free_bytes)} libres ({free_gb:.2f} GB)")

    if not is_space_ok:
        err_msg = f"Espacio insuficiente en disco destino ({free_gb:.2f} GB disponibles < {min_gb:.2f} GB requeridos)."
        print(f"[ERROR CRÍTICO] {err_msg}")
        write_status_manifest(dest_dir, config["sede_id"], {
            "sede_id": config["sede_id"],
            "sede_nombre": config.get("sede_nombre", ""),
            "fecha": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "estado": "ERROR",
            "error": err_msg,
            "espacio_libre_gb": round(free_gb, 2),
        })
        sys.exit(1)

    timestamp = datetime.datetime.now().strftime("%Y-%m-%d_%H%M%S")
    sede_tag = f"sede_{config['sede_id']}" if config.get("sede_id") else "sede"
    if config.get("sede_nombre"):
        slug = sanitize_filename_part(config["sede_nombre"])
        if slug:
            sede_tag = f"sede_{config['sede_id']}_{slug}" if config.get("sede_id") else f"sede_{slug}"

    sql_filename = f"database_dcm_{sede_tag}_{config['db_name']}_{timestamp}.sql"
    sql_path = os.path.join(temp_dir, sql_filename)
    zip_filename = f"backup_dcm_{sede_tag}_{timestamp}.zip"
    zip_path = os.path.join(dest_dir, zip_filename)

    mysqldump_bin = find_mysqldump(config.get("mysqldump_path"))
    supports_col_stats = check_column_statistics_supported(mysqldump_bin)
    print(f"      Ejecutable mysqldump: {mysqldump_bin} (column-statistics: {'soportado' if supports_col_stats else 'no requerido/ignorado'})")

    # 2. Paso 1: Dump local de MySQL
    print(f"[2/5] Generando volcado MySQL en disco local ({sql_filename})...")
    start_dump = time.time()

    cmd = [
        mysqldump_bin,
        f"--host={config['db_host']}",
        f"--port={config['db_port']}",
        f"--user={config['db_user']}",
        "--single-transaction",
        "--quick",
        "--routines",
        "--triggers",
        "--hex-blob",
        "--max_allowed_packet=512M",
    ]

    if supports_col_stats:
        cmd.append("--column-statistics=0")

    cmd.extend([
        f"--result-file={sql_path}",
        config["db_name"],
    ])

    env = os.environ.copy()
    if config.get("db_password"):
        env["MYSQL_PWD"] = config["db_password"]

    try:
        proc = subprocess.run(cmd, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, check=False)

        # Reintento de contingencia si falló por unknown variable 'column-statistics=0'
        if proc.returncode != 0 and "column-statistics" in proc.stderr:
            print("      [AVISO] Reintentando volcado sin opción --column-statistics=0...")
            cmd_retry = [arg for arg in cmd if not arg.startswith("--column-statistics")]
            proc = subprocess.run(cmd_retry, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, check=False)

        if proc.returncode != 0:
            if os.path.exists(sql_path):
                try:
                    os.remove(sql_path)
                except Exception:
                    pass
            err_msg = f"mysqldump falló con código {proc.returncode}: {proc.stderr.strip()}"
            print(f"[ERROR] {err_msg}")
            write_status_manifest(dest_dir, config["sede_id"], {
                "sede_id": config["sede_id"],
                "sede_nombre": config.get("sede_nombre", ""),
                "fecha": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                "estado": "ERROR",
                "error": err_msg,
            })
            sys.exit(1)
    except FileNotFoundError:
        if os.path.exists(sql_path):
            try:
                os.remove(sql_path)
            except Exception:
                pass
        err_msg = f"No se pudo ejecutar mysqldump en '{mysqldump_bin}'. Verifica la ruta configurada."
        print(f"[ERROR] {err_msg}")
        sys.exit(1)

    if not os.path.exists(sql_path) or os.path.getsize(sql_path) == 0:
        if os.path.exists(sql_path):
            try:
                os.remove(sql_path)
            except Exception:
                pass
        print("[ERROR] El archivo .sql generado no existe o está vacío.")
        sys.exit(1)

    dump_size = os.path.getsize(sql_path)
    dump_duration = round(time.time() - start_dump, 2)
    print(f"      [OK] Dump completado en {dump_duration}s (Tamaño sin comprimir: {format_bytes(dump_size)})")

    # 3. Paso 2: Compresión directa en carpeta destino
    print(f"[3/5] Comprimiendo directamente en destino Qsync ({zip_filename})...")
    start_zip = time.time()

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        zf.write(sql_path, arcname=sql_filename)

    zip_size = os.path.getsize(zip_path)
    zip_duration = round(time.time() - start_zip, 2)
    ratio = round(dump_size / zip_size, 1) if zip_size > 0 else 1
    print(f"      [OK] Archivo ZIP generado en {zip_duration}s: {format_bytes(zip_size)} (Compresión {ratio}x)")

    # 4. Paso 3: Verificación estricta de integridad del ZIP antes de borrar el SQL
    print("[4/5] Verificando integridad interna del archivo ZIP (testzip)...")
    try:
        with zipfile.ZipFile(zip_path, "r") as zf_test:
            bad_file = zf_test.testzip()
            if bad_file:
                raise zipfile.BadZipFile(f"Archivo corrupto en el archivo ZIP: {bad_file}")
        print("      [OK] Integridad del archivo ZIP 100% verificada (CRC32 válido).")

        # Eliminar archivo SQL crudo temporal solo tras confirmar la integridad del ZIP
        try:
            os.remove(sql_path)
            print("      [OK] Dump temporal .sql eliminado de forma segura.")
        except Exception:
            pass

    except Exception as e:
        err_msg = f"Error de integridad en el archivo ZIP generado: {e}"
        print(f"[ERROR CRÍTICO] {err_msg}")
        print("      AVISO: El archivo SQL original se conserva intacto en: " + sql_path)
        write_status_manifest(dest_dir, config["sede_id"], {
            "sede_id": config["sede_id"],
            "sede_nombre": config.get("sede_nombre", ""),
            "fecha": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "estado": "ERROR",
            "error": err_msg,
            "sql_conservado": sql_path,
        })
        sys.exit(1)

    # 5. Paso 4: Huella digital criptográfica SHA-256
    print("[5/5] Calculando checksum criptográfico SHA-256...")
    sha256_hash = calculate_sha256(zip_path)
    sha256_file = f"{zip_path}.sha256"
    try:
        with open(sha256_file, "w", encoding="utf-8") as f:
            f.write(f"{sha256_hash}  {zip_filename}\n")
        print(f"      [OK] SHA-256: {sha256_hash}")
        print(f"      Archivo hash generado: {os.path.basename(sha256_file)}")
    except Exception as e:
        print(f"      [ADVERTENCIA] No se pudo guardar archivo .sha256: {e}")

    # Paso 6: Purga automática por retención
    retention_days = int(config.get("retention_days", 30))
    print(f"[Purga] Verificando backups mayores a {retention_days} días en {dest_dir}...")
    purged_count, purged_bytes = purge_old_backups(dest_dir, retention_days)
    if purged_count > 0:
        print(f"      [OK] Purgados {purged_count} archivo(s) antiguo(s), liberados {format_bytes(purged_bytes)}.")
    else:
        print("      [OK] No hay archivos que superen los días de retención.")

    # Paso 7: Escribir manifiesto de estado final para monitoreo en QNAP
    total_duration = round(time.time() - start_total_time, 2)
    end_free_bytes, end_free_gb, _ = check_disk_space(dest_dir, 0)

    status_data = {
        "sede_id": config["sede_id"],
        "sede_nombre": config.get("sede_nombre", ""),
        "db_name": config["db_name"],
        "fecha_ejecucion": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "estado": "SUCCESS",
        "archivo_zip": zip_filename,
        "sha256": sha256_hash,
        "tamano_db_bytes": dump_size,
        "tamano_db_humano": format_bytes(dump_size),
        "tamano_zip_bytes": zip_size,
        "tamano_zip_humano": format_bytes(zip_size),
        "ratio_compresion": f"{ratio}x",
        "duracion_dump_segundos": dump_duration,
        "duracion_zip_segundos": zip_duration,
        "duracion_total_segundos": total_duration,
        "espacio_libre_disco": format_bytes(end_free_bytes),
        "espacio_libre_gb": round(end_free_gb, 2),
        "archivos_purgados": purged_count,
        "archivos_purgados_bytes": purged_bytes,
    }
    manifest_file = write_status_manifest(dest_dir, config["sede_id"], status_data)
    if manifest_file:
        print(f"[Telemetría] Manifiesto generado localmente: {os.path.basename(manifest_file)}")

    # Paso 8: Envío inmediato a la NAS QNAP (si está configurada)
    print("\n[Paso 8/8] Transmitiendo backup inmediatamente a la NAS QNAP...")
    files_to_transfer = [zip_path, sha256_file, manifest_file, os.path.join(dest_dir, "backup_status.json")]
    qnap_res = transfer_backup_to_qnap(config, files_to_transfer, retention_days)

    if qnap_res.get("estado") == "SUCCESS":
        status_data["qnap_transfer"] = qnap_res
        write_status_manifest(dest_dir, config["sede_id"], status_data)

    # Actualizar manifiesto unificado de salud (healthcheck)
    update_unified_healthcheck(
        sede_id=config["sede_id"],
        sede_nombre=config.get("sede_nombre", ""),
        service="backup_db",
        info={
            "estado": status_data["estado"],
            "fecha": status_data["fecha_ejecucion"],
            "archivo_zip": status_data.get("archivo_zip", ""),
            "tamano_zip": status_data.get("tamano_zip_humano", ""),
            "sha256": status_data.get("sha256", ""),
            "duracion_segundos": status_data.get("duracion_total_segundos", 0),
        },
        unc_dest=qnap_res.get("qnap_destino", ""),
    )

    print("=========================================================")
    print(f" BACKUP DCM FINALIZADO CON ÉXITO EN {total_duration}s")
    print(f" Destino local : {zip_path}")
    print(f" SHA-256       : {sha256_hash}")
    if qnap_res.get("estado") == "SUCCESS":
        print(f" Destino QNAP  : {qnap_res.get('qnap_destino')}")
        print(f" Tasa envío    : {qnap_res.get('segundos')}s ({format_bytes(qnap_res.get('bytes_transferidos', 0))})")
    elif qnap_res.get("estado") == "SKIPPED":
        print(" Destino QNAP  : No configurado (backup conservado localmente)")
    else:
        print(f" Estado QNAP   : {qnap_res.get('estado')} ({qnap_res.get('error', '')})")
        print("                 (El backup local se encuentra a salvo e íntegro)")
    print("=========================================================")


if __name__ == "__main__":
    main()
