#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================================
LIDiag — Agente de Sincronización Continua de Imágenes PACS / DICOM hacia QNAP
=============================================================================
Propósito:
  Sincroniza de forma continua e incremental las imágenes radiológicas (.dcm)
  desde la estación local del PACS hacia la carpeta compartida en el QNAP NAS,
  funcionando con el modelo Dropbox / Google Drive:
  - 100% Unidireccional y Modo Backup Seguro (nunca borra en el NAS).
  - Multi-hilo de alta velocidad (Robocopy nativo multi-thread).
  - Tolerancia a caídas de red y reanudación automática instantánea.
  - Generación de telemetría JSON para monitoreo central.

Autor: Jhon Javer Lozano Arce / Equipo LIDiag
Fecha: 2026-10-21
=============================================================================
"""

import os
import sys
import json
import time
import socket
import shutil
import logging
import argparse
import subprocess
from datetime import datetime

AGENT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_FILE = os.path.join(AGENT_DIR, "config.json")
LOG_FILE = os.path.join(AGENT_DIR, "sync_imagenes.log")
ROBOCOPY_LOG_FILE = os.path.join(AGENT_DIR, "robocopy_scan.log")
STATUS_FILE = os.path.join(AGENT_DIR, "status_sync_imagenes.json")

# Configuración de Logging
logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
logger = logging.getLogger("LIDiag_SyncDCM")


def parse_tolerant_json(file_path: str) -> dict:
    """
    Lee un archivo JSON tolerando barras invertidas simples de Windows (C:\\...)
    sin arrojar JSONDecodeError por caracteres de escape no válidos.
    """
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()

    try:
        return json.loads(content)
    except json.JSONDecodeError:
        fixed_lines = []
        for line in content.splitlines():
            if ":" in line:
                key, val = line.split(":", 1)
                val_stripped = val.strip()
                if val_stripped.startswith('"') and val_stripped.endswith('",') or val_stripped.endswith('"'):
                    val_cleaned = val.replace("\\", "\\\\").replace("\\\\\\\\", "\\\\")
                    fixed_lines.append(f"{key}:{val_cleaned}")
                    continue
            fixed_lines.append(line)
        return json.loads("\n".join(fixed_lines))


def check_qnap_port(ip: str, port: int = 445, timeout: float = 3.0) -> bool:
    """Verifica rápidamente si el puerto SMB (445) del QNAP responde."""
    try:
        with socket.create_connection((ip, port), timeout=timeout):
            return True
    except (socket.timeout, socket.error, OSError):
        return False


def authenticate_smb(qnap_ip: str, share: str, user: str, password: str) -> bool:
    """
    Establece la sesión autenticada con el recurso compartido del QNAP
    mediante 'net use' de Windows sin alterar unidades lógicas.
    """
    unc_path = f"\\\\{qnap_ip}\\{share}"
    cmd = ["net", "use", unc_path, password, f"/user:{user}", "/persistent:no"]
    try:
        res = subprocess.run(cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=15)
        if res.returncode == 0 or "Ya está conectado" in res.stderr or "already" in res.stderr.lower():
            return True
        logger.warning(f"Respuesta de autenticación SMB ({res.returncode}): {res.stderr.strip()}")
        return res.returncode == 0
    except Exception as e:
        logger.error(f"Error al ejecutar net use para {unc_path}: {e}")
        return False


def get_free_disk_space_gb(path: str) -> float:
    """Calcula el espacio libre en gigabytes para la unidad de disco."""
    try:
        drive = os.path.splitdrive(os.path.abspath(path))[0] or path
        total, used, free = shutil.disk_usage(drive)
        return round(free / (1024**3), 2)
    except Exception:
        return 0.0


def parse_robocopy_summary(output: str) -> dict:
    """Extrae las estadísticas del informe estándar de Robocopy."""
    stats = {"copiados": 0, "omitidos": 0, "fallidos": 0, "bytes_copiados": "0"}
    for line in output.splitlines():
        line_clean = line.strip()
        if line_clean.startswith("Archivos :") or line_clean.startswith("Files :"):
            parts = [p for p in line_clean.split() if p.isdigit()]
            if len(parts) >= 4:
                stats["copiados"] = int(parts[1]) if len(parts) > 1 else 0
                stats["omitidos"] = int(parts[2]) if len(parts) > 2 else 0
                stats["fallidos"] = int(parts[4]) if len(parts) > 4 else 0
        elif line_clean.startswith("Bytes :"):
            parts = line_clean.split(":")
            if len(parts) > 1:
                stats["bytes_copiados"] = parts[1].strip()
    return stats


def read_robocopy_log_tail(log_path: str, max_lines: int = 40) -> str:
    """Lee las últimas líneas del log de Robocopy tolerando codificaciones UTF-16 y UTF-8."""
    if not os.path.isfile(log_path):
        return ""
    for enc in ["utf-16", "utf-8", "cp1252"]:
        try:
            with open(log_path, "r", encoding=enc, errors="ignore") as f:
                lines = f.readlines()
                return "".join(lines[-max_lines:])
        except Exception:
            continue
    return ""


def sync_cycle(config: dict) -> bool:
    """
    Ejecuta un ciclo de sincronización incremental desde local hacia el QNAP.
    """
    qnap_ip = config.get("qnap_ip", "").strip()
    qnap_share = (config.get("qnap_share_imagenes") or config.get("qnap_share", "")).strip()
    qnap_user = config.get("qnap_user", "").strip()
    qnap_password = config.get("qnap_password", "")
    local_dir = config.get("local_images_dir", "D:\\imagenes_pacs").strip()
    threads = int(config.get("threads", 8))
    sede_nombre = config.get("sede_nombre", "Sede")
    min_space = float(config.get("min_free_space_gb", 5))

    start_time = time.time()
    unc_dest = f"\\\\{qnap_ip}\\{qnap_share}"

    # 1. Validar directorio de imágenes local
    if not os.path.isdir(local_dir):
        logger.error(f"Directorio local de imágenes no encontrado: {local_dir}")
        emit_status(config, status="ERROR", msg=f"Directorio local no existe: {local_dir}")
        return False

    # 2. Pre-flight check: Espacio en disco local
    free_gb = get_free_disk_space_gb(local_dir)
    if free_gb < min_space:
        logger.warning(f"Espacio libre en disco bajo ({free_gb} GB < {min_space} GB). Alerta de almacenamiento.")

    # 3. Conectividad TCP al QNAP
    if not check_qnap_port(qnap_ip, port=445):
        logger.warning(f"QNAP no alcanzable en {qnap_ip}:445. Se reintentará en el siguiente ciclo.")
        emit_status(config, status="OFFLINE", msg=f"QNAP {qnap_ip}:445 inalcanzable")
        return False

    # 4. Asegurar sesión SMB autenticada
    if not authenticate_smb(qnap_ip, qnap_share, qnap_user, qnap_password):
        # A veces ya está autenticado a nivel de sistema; verificar si os.path.isdir responde
        if not os.path.isdir(unc_dest):
            logger.error(f"Fallo de autenticación o recurso inaccesible: {unc_dest}")
            emit_status(config, status="AUTH_ERROR", msg=f"No se pudo acceder al recurso {unc_dest}")
            return False

    # 5. Ejecutar Robocopy nativo con /LOG directo, /TEE y /V (Verbose)
    logger.info(f"[1/2] Iniciando escaneo e indexación en vivo del repositorio en '{local_dir}'...")
    logger.info(f"      Destino QNAP : {unc_dest} | Hilos: {threads}")
    logger.info(f"      Log detallado: {ROBOCOPY_LOG_FILE}")

    # Flags de alto rendimiento para escaneo y registro en tiempo real:
    #   /E       : Copia subcarpetas (series, estudios)
    #   /XO      : Exclude older (no toca lo que ya está igual o más reciente)
    #   /XN      : Exclude newer (no reemplaza nada más nuevo en el destino)
    #   /XC      : Exclude changed (no altera archivos existentes -> Modo Backup Seguro)
    #   /FFT     : Tolerancia de 2s en timestamps FAT/SMB
    #   /MT      : Hilos paralelos (16 o 32 para cargas masivas)
    #   /R:2     : 2 reintentos ante archivos en escritura activa por el PACS
    #   /W:3     : 3 segundos entre reintentos
    #   /NP      : Sin porcentaje de progreso
    #   /V       : VERBOSE — Obliga a registrar CADA directorio y CADA imagen analizada
    #   /FP      : Incluye la ruta completa del archivo local
    #   /LOG     : Escribe directamente en robocopy_scan.log sin búfer intermedio
    #   /TEE     : Muestra la salida en la pantalla del CMD en tiempo real
    cmd = [
        "robocopy",
        local_dir,
        unc_dest,
        "/E",
        "/XO",
        "/XN",
        "/XC",
        "/FFT",
        f"/MT:{threads}",
        "/R:2",
        "/W:3",
        "/NP",
        "/V",
        "/FP",
        f"/LOG:{ROBOCOPY_LOG_FILE}",
        "/TEE",
    ]

    try:
        # timeout=None permite sincronizaciones masivas de terabytes (18 TB) sin interrupciones
        res = subprocess.run(cmd, timeout=None)
        duration = round(time.time() - start_time, 2)

        # Extraer estadísticas desde el log de Robocopy en disco
        log_tail = read_robocopy_log_tail(ROBOCOPY_LOG_FILE, max_lines=50)
        stats = parse_robocopy_summary(log_tail)

        # Códigos de salida de Robocopy:
        # < 8 = Éxito / Operación normal (0=sin cambios, 1=copiados, 2=extra, 3=copiados+extra)
        # >= 8 = Error de copia
        if res.returncode < 8:
            copiados = stats.get("copiados", 0)
            omitidos = stats.get("omitidos", 0)
            fallidos = stats.get("fallidos", 0)
            bytes_cop = stats.get("bytes_copiados", "0")

            logger.info(f"[2/2] Escaneo y sincronización completados en {duration}s.")
            logger.info(f"      Archivos copiados   : {copiados}")
            logger.info(f"      Archivos omitidos   : {omitidos}")
            logger.info(f"      Archivos fallidos   : {fallidos}")
            logger.info(f"      Volumen transferido : {bytes_cop}")

            emit_status(
                config,
                status="OK",
                msg="Sincronizado",
                copiados=copiados,
                duration=duration,
                free_gb=free_gb,
                unc_dest=unc_dest,
            )
            return True
        else:
            logger.error(f"Robocopy reportó error (código {res.returncode}). Revisa '{ROBOCOPY_LOG_FILE}' para detalles.")
            emit_status(config, status="ROBOCOPY_ERROR", msg=f"Error código {res.returncode}", duration=duration)
            return False

    except subprocess.TimeoutExpired:
        logger.error("Robocopy superó el tiempo límite de 30 minutos.")
        emit_status(config, status="TIMEOUT", msg="Timeout de 30 min en sincronización")
        return False
    except Exception as e:
        logger.error(f"Excepción en ciclo de sincronización: {e}", exc_info=True)
        emit_status(config, status="ERROR", msg=str(e))
        return False


def emit_status(config: dict, status: str, msg: str, copiados: int = 0, duration: float = 0.0, free_gb: float = 0.0, unc_dest: str = ""):
    """Emite la telemetría JSON local y remotamente en el QNAP."""
    sede_nombre = config.get("sede_nombre", "Sede")
    sede_slug = sede_nombre.lower().strip().replace(" ", "_")

    payload = {
        "sede_nombre": sede_nombre,
        "timestamp": datetime.now().isoformat(),
        "status": status,
        "mensaje": msg,
        "archivos_copiados_ultimo_ciclo": copiados,
        "duracion_segundos": duration,
        "espacio_libre_local_gb": free_gb,
        "qnap_destino": f"\\\\{config.get('qnap_ip')}\\{config.get('qnap_share')}",
    }

    # Guardar local
    try:
        with open(STATUS_FILE, "w", encoding="utf-8") as f:
            json.dump(payload, f, indent=2, ensure_ascii=False)
    except Exception as e:
        logger.warning(f"No se pudo escribir status local: {e}")

    # Guardar en el QNAP si el destino está disponible
    if unc_dest and os.path.isdir(unc_dest):
        try:
            remote_status_path = os.path.join(unc_dest, f"status_sync_{sede_slug}.json")
            with open(remote_status_path, "w", encoding="utf-8") as f:
                json.dump(payload, f, indent=2, ensure_ascii=False)
        except Exception:
            pass


def main():
    parser = argparse.ArgumentParser(description="LIDiag Agente de Sincronización Continua de Imágenes PACS")
    parser.add_argument("--once", action="store_true", help="Ejecuta una sola pasada y finaliza (sin bucle continuo)")
    args = parser.parse_args()

    if not os.path.isfile(CONFIG_FILE):
        logger.critical(f"Archivo de configuración no encontrado: {CONFIG_FILE}")
        logger.critical("Crea config.json basándote en config.json.example antes de iniciar.")
        sys.exit(1)

    try:
        config = parse_tolerant_json(CONFIG_FILE)
    except Exception as e:
        logger.critical(f"Error al parsear config.json: {e}")
        sys.exit(1)

    interval = int(config.get("sync_interval_seconds", 180))
    sede_nombre = config.get("sede_nombre", "Sede")
    share_name = (config.get("qnap_share_imagenes") or config.get("qnap_share", "")).strip()
    logger.info(f"=== Iniciando Agente de Sincronización de Imágenes PACS — {sede_nombre} ===")
    logger.info(f"Origen local  : {config.get('local_images_dir')}")
    logger.info(f"Destino QNAP  : \\\\{config.get('qnap_ip')}\\{share_name}")
    logger.info(f"Intervalo     : {interval} segundos")
    logger.info(f"Hilos Robocopy: {config.get('threads', 8)}")

    if args.once:
        logger.info("Modo de ejecución: Pasada única (--once)")
        success = sync_cycle(config)
        sys.exit(0 if success else 1)

    logger.info("Modo de ejecución: Bucle continuo en segundo plano (Ctrl+C para detener)")
    while True:
        try:
            sync_cycle(config)
            time.sleep(interval)
        except KeyboardInterrupt:
            logger.info("Detención solicitada por el usuario. Cerrando agente de sincronización.")
            break
        except Exception as e:
            logger.error(f"Error imprevisto en bucle principal: {e}", exc_info=True)
            time.sleep(30)


if __name__ == "__main__":
    main()
