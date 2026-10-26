#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================================
LIDiag — Agente de Restauración Cronológica Descendente de Imágenes PACS
=============================================================================
Herramienta de Recuperación ante Desastres (Disaster Recovery).
Restaura el repositorio de imágenes DICOM desde la NAS QNAP hacia el disco local
en orden estrictamente cronológico descendente (año más reciente al más antiguo).

Objetivo clínico:
Permite que el año en curso (ej. 2026) quede 100% operativo en pocas horas,
habilitando de inmediato la visualización de estudios en urgencias/UCI en Oviyam/OHIF,
mientras los años históricos continúan restaurándose en segundo plano.

Autor: Jhon Javer Lozano Arce / Equipo LIDiag
Fecha: 2026-10-26
=============================================================================
"""

import argparse
import atexit
import datetime
import json
import logging
from logging.handlers import RotatingFileHandler
import os
import re
import shutil
import signal
import socket
import subprocess
import sys
import time
from typing import Dict, List, Optional, Tuple

AGENT_DIR = os.path.dirname(os.path.abspath(__file__))
_venv_site = os.path.join(AGENT_DIR, "venv", "Lib", "site-packages")
if os.path.isdir(_venv_site) and _venv_site not in sys.path:
    sys.path.insert(0, _venv_site)
CONFIG_FILE = os.path.join(AGENT_DIR, "config.json")
LOGS_DIR = os.path.join(AGENT_DIR, "logs")
os.makedirs(LOGS_DIR, exist_ok=True)

LOG_FILE = os.path.join(LOGS_DIR, "restore_imagenes.log")
LOCK_FILE = os.path.join(LOGS_DIR, "restore_dcm.lock")
STATUS_RESTORE_FILE = os.path.join(LOGS_DIR, "status_restore_imagenes.json")

CURRENT_ROBOCOPY_PROC = None

# Configuración de Logging con Rotación Automática
logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    handlers=[
        RotatingFileHandler(LOG_FILE, maxBytes=20 * 1024 * 1024, backupCount=3, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
logger = logging.getLogger("LIDiag_RestoreDCM")


def is_pid_running(pid: int) -> bool:
    """Verifica si un PID de Windows se encuentra actualmente en ejecución."""
    if pid <= 0:
        return False
    if sys.platform == "win32":
        try:
            res = subprocess.run(
                ["tasklist", "/FI", f"PID eq {pid}", "/NH"],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
                text=True,
                check=False,
            )
            return str(pid) in (res.stdout or "")
        except Exception:
            return False
    else:
        try:
            os.kill(pid, 0)
            return True
        except OSError:
            return False


def acquire_process_lock() -> bool:
    """Asegura que sólo una restauración se ejecute a la vez."""
    if os.path.isfile(LOCK_FILE):
        try:
            with open(LOCK_FILE, "r", encoding="utf-8") as f:
                content = f.read().strip()
                if content.isdigit():
                    prev_pid = int(content)
                    if is_pid_running(prev_pid):
                        logger.warning(
                            f"Ya existe un proceso de restauración activo (PID {prev_pid}). "
                            "Se omite la nueva ejecución para evitar contención de red y disco."
                        )
                        return False
        except Exception:
            pass

    try:
        with open(LOCK_FILE, "w", encoding="utf-8") as f:
            f.write(str(os.getpid()))
        return True
    except Exception as e:
        logger.error(f"No se pudo crear archivo de bloqueo {LOCK_FILE}: {e}")
        return True


def release_process_lock():
    """Libera el archivo de bloqueo al salir."""
    try:
        if os.path.isfile(LOCK_FILE):
            os.remove(LOCK_FILE)
    except Exception:
        pass


atexit.register(release_process_lock)


def handle_exit_signal(signum, frame):
    """Cierre limpio del subproceso Robocopy ante Ctrl+C o interrupción."""
    global CURRENT_ROBOCOPY_PROC
    logger.info("Detención solicitada por el usuario. Cancelando subprocesos en curso...")
    if CURRENT_ROBOCOPY_PROC and CURRENT_ROBOCOPY_PROC.poll() is None:
        try:
            CURRENT_ROBOCOPY_PROC.terminate()
            CURRENT_ROBOCOPY_PROC.wait(timeout=3)
        except Exception:
            try:
                CURRENT_ROBOCOPY_PROC.kill()
            except Exception:
                pass
    release_process_lock()
    sys.exit(0)


try:
    signal.signal(signal.SIGINT, handle_exit_signal)
    signal.signal(signal.SIGTERM, handle_exit_signal)
except Exception:
    pass


def parse_tolerant_json(file_path: str) -> dict:
    """Lee JSON tolerando barras simples de Windows sin fallar por invalid escape."""
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()

    try:
        return json.loads(content)
    except json.JSONDecodeError:
        pass

    def fix_string(match):
        val = match.group(0)
        inner = val[1:-1].replace('\\"', "___ESCAPED_QUOTE___").replace("\\", "/").replace("___ESCAPED_QUOTE___", '\\"')
        return f'"{inner}"'

    fixed = re.sub(r'"(?:[^"\\]|\\.)*"', fix_string, content)
    try:
        return json.loads(fixed)
    except Exception:
        return json.loads(content.replace("\\", "/"))


def format_bytes(bytes_num: float) -> str:
    """Formatea bytes a representación humana legible."""
    for unit in ["B", "KB", "MB", "GB", "TB"]:
        if bytes_num < 1024.0:
            return f"{bytes_num:.2f} {unit}"
        bytes_num /= 1024.0
    return f"{bytes_num:.2f} PB"


def get_free_disk_space_gb(path: str) -> float:
    """Retorna los gigabytes libres disponibles en el disco destino."""
    try:
        total, used, free = shutil.disk_usage(path)
        return round(free / (1024**3), 2)
    except Exception:
        return 999.0


def check_qnap_port(ip: str, port: int = 445, timeout: float = 3.0) -> bool:
    """Verifica si el puerto SMB (445) de la QNAP responde."""
    try:
        with socket.create_connection((ip, port), timeout=timeout):
            return True
    except (socket.timeout, socket.error, OSError):
        return False


def authenticate_smb(qnap_ip: str, share: str, user: str, password: str) -> bool:
    """Autentica sesión SMB contra el recurso compartido de la QNAP."""
    unc_path = f"\\\\{qnap_ip}\\{share}"
    cmd = ["net", "use", unc_path, password, f"/user:{user}", "/persistent:no"]
    try:
        res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=15)
        if res.returncode == 0 or "Ya está conectado" in res.stderr or "already" in res.stderr.lower():
            return True
        return os.path.isdir(unc_path)
    except Exception:
        return os.path.isdir(unc_path)


def discover_qnap_years(unc_dest: str) -> List[Tuple[str, str]]:
    """
    Escanea la raíz de la QNAP y retorna las carpetas de años ordenadas
    en orden estrictamente cronológico descendente (ej. ['2026', '2025', '2024', ...]).
    Retorna lista de tuplas: (nombre_carpeta, ruta_completa).
    """
    if not os.path.isdir(unc_dest):
        return []

    year_pattern = re.compile(r"^(19|20)\d{2}$")
    years_found = []
    other_folders = []

    try:
        for entry in os.scandir(unc_dest):
            if not entry.is_dir():
                continue
            name = entry.name
            # Omitir carpetas del sistema y de papeleras de la NAS
            if name.startswith(".") or name.startswith("@") or name.lower() in ("$recycle.bin", "system volume information"):
                continue

            if year_pattern.match(name):
                years_found.append((name, entry.path))
            else:
                other_folders.append((name, entry.path))
    except Exception as e:
        logger.error(f"Error al explorar directorios en la QNAP ({unc_dest}): {e}")
        return []

    # Ordenar numéricamente descendente (2026, 2025, 2024...)
    years_found.sort(key=lambda x: int(x[0]), reverse=True)

    # Las demás carpetas que no tengan formato de año se agregan al final en orden alfabético inverso
    other_folders.sort(key=lambda x: x[0], reverse=True)

    return years_found + other_folders


def restore_folder_robocopy(remote_folder: str, local_folder: str, threads: int = 16) -> Tuple[bool, dict]:
    """
    Ejecuta Robocopy de alta velocidad desde la QNAP hacia el almacenamiento local.
    Usa /XO /XN /XC para ser 100% reanudable (no re-copia archivos ya existentes en local).
    """
    global CURRENT_ROBOCOPY_PROC

    os.makedirs(local_folder, exist_ok=True)

    cmd = [
        "robocopy",
        remote_folder,
        local_folder,
        "/E",
        "/XO",
        "/XN",
        "/XC",
        "/FFT",
        f"/MT:{threads}",
        "/R:2",
        "/W:3",
        "/NP",
        "/XF",
        "desktop.ini",
        "Thumbs.db",
        "*.tmp",
        "*.syncrootid",
        "*.part",
        "*.crdownload",
        "/XD",
        "@Recycle",
        ".syncing_db",
        ".qsyncclient",
        "$RECYCLE.BIN",
        "System Volume Information",
    ]

    start_time = time.time()
    try:
        proc = subprocess.Popen(
            cmd,
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            encoding="cp1252",
            errors="replace",
        )
        CURRENT_ROBOCOPY_PROC = proc

        # Monitorear salida
        for line in proc.stdout:
            line_s = line.strip()
            if line_s and ("nuevo arch" in line_s.lower() or "modificado" in line_s.lower() or "copiado" in line_s.lower()):
                # Mostrar solo líneas relevantes sin saturar consola
                pass

        proc.wait()
        returncode = proc.returncode
        duration = round(time.time() - start_time, 2)

        # Robocopy codes: < 8 es éxito
        is_success = (returncode < 8)
        return is_success, {"duracion": duration, "returncode": returncode}
    except Exception as e:
        logger.error(f"Excepción durante la restauración de {remote_folder}: {e}")
        return False, {"error": str(e)}
    finally:
        CURRENT_ROBOCOPY_PROC = None


def parse_arguments():
    parser = argparse.ArgumentParser(
        description="LIDiag — Agente de Restauración Cronológica Descendente de Imágenes PACS (QNAP -> Local)"
    )
    parser.add_argument("--config", "-c", default="config.json", help="Ruta al archivo config.json")
    parser.add_argument("--year", "-y", help="Restaura exclusivamente este año (ej. --year=2026)")
    parser.add_argument("--from-year", help="Restaura en orden descendente desde el año actual hasta este año límite (ej. --from-year=2024)")
    parser.add_argument("--threads", "-t", type=int, default=16, help="Hilos concurrentes de Robocopy (por defecto: 16)")
    parser.add_argument("--dry-run", action="store_true", help="Modo simulación: muestra el orden y las carpetas sin copiar")
    return parser.parse_args()


def main():
    args = parse_arguments()

    if not os.path.isfile(args.config):
        logger.critical(f"Archivo de configuración no encontrado: {args.config}")
        logger.critical("Copia config.json.example a config.json y completa los datos antes de iniciar.")
        sys.exit(1)

    try:
        config = parse_tolerant_json(args.config)
    except Exception as e:
        logger.critical(f"Error al parsear archivo de configuración: {e}")
        sys.exit(1)

    # Control de instancia única
    if not acquire_process_lock():
        sys.exit(0)

    qnap_ip = config.get("qnap_ip", "").strip()
    share_name = (config.get("qnap_share_imagenes") or config.get("qnap_share", "")).strip()
    qnap_user = config.get("qnap_user", "").strip()
    qnap_password = config.get("qnap_password", "")
    local_images_dir = config.get("local_images_dir", "D:\\img_dcm").strip()
    threads = args.threads or int(config.get("threads", 16))
    sede_nombre = config.get("sede_nombre", "Sede")

    unc_dest = f"\\\\{qnap_ip}\\{share_name}"

    print("=========================================================================")
    print("   LIDiag — RESTAURACIÓN CRONOLÓGICA DESCENDENTE DE IMÁGENES PACS        ")
    print("=========================================================================")
    print(f" Sede               : {sede_nombre}")
    print(f" Origen QNAP NAS    : {unc_dest}")
    print(f" Destino Local PACS : {local_images_dir}")
    print(f" Hilos de Copia     : {threads}")
    print(" Orden de Prioridad : CRONOLÓGICO DESCENDENTE (Más reciente al más antiguo)")
    print("=========================================================================\n")

    # 1. Comprobar socket 445 de la QNAP
    logger.info(f"[1/4] Verificando conectividad con el almacenamiento QNAP ({qnap_ip}:445)...")
    if not check_qnap_port(qnap_ip, port=445):
        logger.critical(f"La NAS QNAP no responde en {qnap_ip}:445. Verifica la red y que la NAS esté encendida.")
        sys.exit(1)
    logger.info("      [OK] Conexión TCP con QNAP establecida exitosamente.")

    # 2. Autenticar SMB
    logger.info("[2/4] Autenticando sesión de red SMB contra la NAS...")
    if not authenticate_smb(qnap_ip, share_name, qnap_user, qnap_password):
        if not os.path.isdir(unc_dest):
            logger.critical(f"No se pudo acceder al recurso compartido en la NAS: {unc_dest}")
            sys.exit(1)
    logger.info("      [OK] Recurso compartido autenticado y accesible.")

    # 3. Descubrir y ordenar carpetas en orden cronológico descendente
    logger.info("[3/4] Escaneando árbol de carpetas en la QNAP para ordenamiento cronológico...")
    candidate_folders = discover_qnap_years(unc_dest)

    if not candidate_folders:
        logger.critical(f"No se encontraron carpetas de imágenes en {unc_dest}.")
        sys.exit(1)

    # Filtrar según argumentos si fueron suministrados
    plan_to_restore = []
    if args.year:
        target_year = str(args.year).strip()
        plan_to_restore = [f for f in candidate_folders if f[0] == target_year]
        if not plan_to_restore:
            logger.critical(f"El año especificado '{target_year}' no existe en la NAS QNAP.")
            sys.exit(1)
    elif args.from_year:
        try:
            min_year = int(args.from_year)
            plan_to_restore = [f for f in candidate_folders if f[0].isdigit() and int(f[0]) >= min_year]
        except ValueError:
            logger.critical(f"Valor no numérico para --from-year: {args.from_year}")
            sys.exit(1)
    else:
        plan_to_restore = candidate_folders

    print("\n-------------------------------------------------------------------------")
    print(f" PLAN DE RESTAURACIÓN PRIORIZADA ({len(plan_to_restore)} bloques en orden):")
    for idx, (folder_name, full_path) in enumerate(plan_to_restore, 1):
        prio_label = "PRIORIDAD MÁXIMA (Urgencias / Clínica)" if idx == 1 else "Segundo plano"
        print(f"  [{idx:02d}] Año/Carpeta: {folder_name:<10} | Prioridad: {prio_label}")
    print("-------------------------------------------------------------------------\n")

    if args.dry_run:
        logger.info("[DRY-RUN] Simulación completada con éxito. No se copiaron archivos.")
        sys.exit(0)

    # 4. Ejecución del plan de restauración descendente
    logger.info("[4/4] Iniciando transferencia cronológica descendente de imágenes...")
    total_start = time.time()
    years_completed = []

    for idx, (folder_name, remote_path) in enumerate(plan_to_restore, 1):
        target_local_path = os.path.join(local_images_dir, folder_name)

        free_gb = get_free_disk_space_gb(local_images_dir)
        print(f"\n=========================================================================")
        print(f" [{idx}/{len(plan_to_restore)}] RESTAURANDO AÑO: {folder_name}")
        print(f" Origen  : {remote_path}")
        print(f" Destino : {target_local_path}")
        print(f" Espacio libre en disco local: {free_gb:.2f} GB")
        print("=========================================================================")

        success, meta = restore_folder_robocopy(remote_path, target_local_path, threads=threads)

        if success:
            dur = meta.get("duracion", 0)
            years_completed.append(folder_name)
            print("-------------------------------------------------------------------------")
            print(f" [HITO CLÍNICO ALCANZADO] ¡Año {folder_name} RESTAURADO CON ÉXITO en {dur}s!")
            if idx == 1:
                print(" >>> ATENCIÓN RADIOLOGÍA: Los estudios más recientes ya están 100% disponibles <<<")
                print(" >>> en el visor DICOM (Oviyam/OHIF) para consulta médica y urgencias.        <<<")
            print("-------------------------------------------------------------------------")
        else:
            logger.error(f"Fallo al restaurar el bloque {folder_name} (Código: {meta.get('returncode')}).")
            logger.info("El proceso continuará con los siguientes bloques; luego se podrá reintentar este año.")

    total_duration = round(time.time() - total_start, 2)
    print("\n=========================================================================")
    print(f" RESTAURACIÓN CRONOLÓGICA FINALIZADA EN {total_duration}s")
    print(f" Bloques completados: {len(years_completed)} de {len(plan_to_restore)}")
    print(f" Destino local      : {local_images_dir}")
    print("=========================================================================")


if __name__ == "__main__":
    main()
