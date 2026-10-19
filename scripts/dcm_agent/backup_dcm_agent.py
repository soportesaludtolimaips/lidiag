#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
backup_dcm_agent.py — Agente de Backup Local DCM para LIDiag y Sincronización QNAP (Qsync)

Este script se ejecuta localmente en el servidor de cada sede/hospital.
Realiza el volcado local de MySQL con mysqldump a alta velocidad de disco (SSD/NVMe),
lo comprime a formato ZIP en la carpeta destino local (C:\\backups_dcm) y purga los
archivos antiguos que superen los días de retención (30 días).

El cliente Qsync de QNAP instalado en el servidor detecta los archivos generados
y los sincroniza automáticamente en segundo plano con el almacenamiento NAS central.

Requisitos:
- Python 3.7+ (Solo utiliza librerías estándar de Python: no requiere pip install).
- Acceso local al comando mysqldump (MySQL Server o MariaDB).

Uso:
  python backup_dcm_agent.py
  python backup_dcm_agent.py --config config.json
  python backup_dcm_agent.py --sede 1 --nombre espinal --destination-dir "C:\\backups_dcm"

Autor: Jhon Javer Lozano Arce
Fecha: 2026-10-19
"""

import argparse
import datetime
import glob
import json
import os
import re
import shutil
import subprocess
import sys
import time
import zipfile


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
    return parser.parse_args()


def load_config(config_path, args):
    config = {
        "sede_id": 1,
        "sede_nombre": "",
        "db_host": "127.0.0.1",
        "db_port": 3306,
        "db_name": "pacsdb",
        "db_user": "root",
        "db_password": "",
        "mysqldump_path": "",
        "destination_dir": "C:\\backups_dcm",
        "retention_days": 30,
        "temp_dir": "./temp_backups",
    }

    # Cargar archivo JSON si existe
    if os.path.exists(config_path):
        try:
            with open(config_path, "r", encoding="utf-8") as f:
                loaded = json.load(f)
                config.update(loaded)
                # Compatibilidad de claves alternativas
                if "mysqldump_bin" in loaded and not loaded.get("mysqldump_path"):
                    config["mysqldump_path"] = loaded["mysqldump_bin"]
                if "sede_slug" in loaded and not loaded.get("sede_nombre"):
                    config["sede_nombre"] = loaded["sede_slug"]
        except Exception as e:
            print(f"[ADVERTENCIA] No se pudo leer {config_path}: {e}. Usando valores por defecto/argumentos.")

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


def find_mysqldump(custom_path):
    if custom_path:
        clean_path = custom_path.strip("\"' \t\r\n")
        clean_path = os.path.expandvars(os.path.expanduser(clean_path))
        if os.path.isfile(clean_path):
            return clean_path
        if shutil.which(clean_path):
            return shutil.which(clean_path)
        print(f"[ADVERTENCIA] La ruta configurada 'mysqldump_path' ({custom_path}) no existe. Buscando automáticamente...")

    # 1. Búsqueda en PATH del sistema
    which_path = shutil.which("mysqldump")
    if which_path:
        return which_path

    # 2. Búsqueda dinámica en instalaciones de MySQL Server en Windows
    search_patterns = [
        r"C:\Program Files\MySQL\MySQL Server *\bin\mysqldump.exe",
        r"C:\Program Files (x86)\MySQL\MySQL Server *\bin\mysqldump.exe",
        r"C:\Program Files\MariaDB *\bin\mysqldump.exe",
        r"C:\laragon\bin\mysql\*\bin\mysqldump.exe",
        r"C:\xampp\mysql\bin\mysqldump.exe",
        r"D:\Program Files\MySQL\MySQL Server *\bin\mysqldump.exe",
    ]
    for pattern in search_patterns:
        matches = glob.glob(pattern)
        if matches:
            matches.sort(reverse=True)
            return matches[0]

    # 3. Rutas comunes en Linux
    linux_paths = ["/usr/bin/mysqldump", "/usr/local/bin/mysqldump"]
    for p in linux_paths:
        if os.path.exists(p):
            return p

    return "mysqldump"


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
        except Exception as e:
            print(f"      [ADVERTENCIA] No se pudo purgar {file_path}: {e}")

    return purged_count, purged_bytes


def main():
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
    print(f" Carpeta Destino  : {config['destination_dir']} (Sincronizada por Qsync)")
    print(f" Días Retención   : {config['retention_days']} días")
    print("=========================================================")

    # Asegurar existencia de directorios
    dest_dir = os.path.abspath(config.get("destination_dir", "C:\\backups_dcm"))
    temp_dir = os.path.abspath(config.get("temp_dir", "./temp_backups"))
    os.makedirs(dest_dir, exist_ok=True)
    os.makedirs(temp_dir, exist_ok=True)

    timestamp = datetime.datetime.now().strftime("%Y-%m-%d_%H%M%S")
    sede_tag = f"sede_{config['sede_id']}"
    if config.get("sede_nombre"):
        slug = sanitize_filename_part(config["sede_nombre"])
        if slug:
            sede_tag = f"sede_{config['sede_id']}_{slug}"

    sql_filename = f"database_dcm_{sede_tag}_{config['db_name']}_{timestamp}.sql"
    sql_path = os.path.join(temp_dir, sql_filename)
    zip_filename = f"backup_dcm_{sede_tag}_{timestamp}.zip"
    zip_path = os.path.join(dest_dir, zip_filename)

    mysqldump_bin = find_mysqldump(config.get("mysqldump_path"))
    print(f"[1/3] Ejecutable mysqldump: {mysqldump_bin}")

    # Paso 1: Dump local
    print(f"[2/3] Generando volcado MySQL en disco local ({sql_filename})...")
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
        "--column-statistics=0",
        f"--result-file={sql_path}",
        config["db_name"],
    ]

    env = os.environ.copy()
    if config.get("db_password"):
        env["MYSQL_PWD"] = config["db_password"]

    try:
        proc = subprocess.run(cmd, env=env, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, check=False)
        if proc.returncode != 0:
            print(f"[ERROR] mysqldump falló con código {proc.returncode}: {proc.stderr.strip()}")
            sys.exit(1)
    except FileNotFoundError:
        print(f"[ERROR] No se pudo ejecutar mysqldump en '{mysqldump_bin}'. Verifica la ruta configurada.")
        sys.exit(1)

    if not os.path.exists(sql_path) or os.path.getsize(sql_path) == 0:
        print("[ERROR] El archivo .sql generado no existe o está vacío.")
        sys.exit(1)

    dump_size = os.path.getsize(sql_path)
    dump_duration = round(time.time() - start_dump, 2)
    print(f"      [OK] Dump completado en {dump_duration}s (Tamaño sin comprimir: {format_bytes(dump_size)})")

    # Paso 2: Compresión directa en carpeta destino
    print(f"[3/3] Comprimiendo directamente en destino Qsync ({zip_filename})...")
    start_zip = time.time()

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        zf.write(sql_path, arcname=sql_filename)

    # Eliminar archivo SQL crudo temporal para liberar espacio de inmediato
    try:
        os.remove(sql_path)
    except Exception:
        pass

    zip_size = os.path.getsize(zip_path)
    zip_duration = round(time.time() - start_zip, 2)
    ratio = round(dump_size / zip_size, 1) if zip_size > 0 else 1
    print(f"      [OK] Archivo ZIP generado en {zip_duration}s: {zip_path}")
    print(f"      Tamaño final: {format_bytes(zip_size)} (Compresión {ratio}x)")

    # Paso 3: Purga automática por retención
    retention_days = int(config.get("retention_days", 30))
    print(f"[Purga] Verificando backups mayores a {retention_days} días en {dest_dir}...")
    purged_count, purged_bytes = purge_old_backups(dest_dir, retention_days)
    if purged_count > 0:
        print(f"      [OK] Purgados {purged_count} archivo(s) antiguo(s), liberados {format_bytes(purged_bytes)}.")
    else:
        print("      [OK] No hay archivos que superen los días de retención.")

    total_duration = round(dump_duration + zip_duration, 2)
    print("=========================================================")
    print(f" BACKUP DCM FINALIZADO CON ÉXITO EN {total_duration}s")
    print(f" Destino: {zip_path}")
    print(" Qsync sincronizará automáticamente el archivo con el NAS QNAP.")
    print("=========================================================")


if __name__ == "__main__":
    main()
