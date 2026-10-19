#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
backup_dcm_agent.py — Agente de Backup Local y Transmisión Webhook para LIDiag

Este script se ejecuta localmente en el servidor de cada sede/hospital.
Realiza el volcado local de MySQL con mysqldump a alta velocidad de disco (SSD/NVMe),
lo comprime a formato ZIP y lo envía mediante HTTP/HTTPS POST al servidor central de LIDiag.

Requisitos:
- Python 3.7+ (Solo utiliza librerías estándar de Python: no requiere pip install).
- Acceso local al comando mysqldump.

Uso:
  python backup_dcm_agent.py
  python backup_dcm_agent.py --config /ruta/a/config.json
  python backup_dcm_agent.py --sede 1 --db-name pacsdb --api-url https://lidiag.test/api/backups/upload-dcm

Autor: Jhon Javer Lozano Arce
Fecha: 2026-10-19
"""

import argparse
import datetime
import json
import mimetypes
import os
import shutil
import subprocess
import sys
import time
import urllib.error
import urllib.request
import uuid
import zipfile


def parse_arguments():
    parser = argparse.ArgumentParser(description="Agente de Backup Local DCM para LIDiag")
    parser.add_argument("--config", "-c", default="config.json", help="Ruta al archivo de configuración JSON (por defecto: config.json)")
    parser.add_argument("--sede", type=int, help="ID de la sede (anula valor del config.json)")
    parser.add_argument("--db-host", help="Host de MySQL (anula config)")
    parser.add_argument("--db-port", type=int, help="Puerto de MySQL (anula config)")
    parser.add_argument("--db-name", help="Nombre de la base de datos (anula config)")
    parser.add_argument("--db-user", help="Usuario de MySQL (anula config)")
    parser.add_argument("--db-password", help="Contraseña de MySQL (anula config)")
    parser.add_argument("--api-url", help="URL del endpoint de LIDiag (anula config)")
    parser.add_argument("--api-token", help="Token Bearer de autenticación (anula config)")
    parser.add_argument("--mysqldump", help="Ruta ejecutable a mysqldump")
    parser.add_argument("--keep-local", action="store_true", help="Conservar el archivo .zip localmente tras subirlo")
    return parser.parse_args()


def load_config(config_path, args):
    config = {
        "sede_id": 1,
        "db_host": "127.0.0.1",
        "db_port": 3306,
        "db_name": "pacsdb",
        "db_user": "root",
        "db_password": "",
        "api_url": "https://lidiag.test/api/backups/upload-dcm",
        "api_token": "lidiag_backup_secure_token_2026",
        "mysqldump_bin": "mysqldump",
        "temp_dir": "./temp_backups",
        "keep_local": False,
        "timeout_upload": 1800,  # 30 minutos máximo para subida
    }

    # Cargar archivo JSON si existe
    if os.path.exists(config_path):
        try:
            with open(config_path, "r", encoding="utf-8") as f:
                loaded = json.load(f)
                config.update(loaded)
        except Exception as e:
            print(f"[ADVERTENCIA] No se pudo leer {config_path}: {e}. Usando valores por defecto/argumentos.")

    # Sobreescribir con argumentos de línea de comandos si fueron suministrados
    if args.sede is not None:
        config["sede_id"] = args.sede
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
    if args.api_url:
        config["api_url"] = args.api_url
    if args.api_token:
        config["api_token"] = args.api_token
    if args.mysqldump:
        config["mysqldump_bin"] = args.mysqldump
    if args.keep_local:
        config["keep_local"] = True

    return config


def format_bytes(bytes_num):
    for unit in ["B", "KB", "MB", "GB", "TB"]:
        if bytes_num < 1024.0:
            return f"{bytes_num:.2f} {unit}"
        bytes_num /= 1024.0
    return f"{bytes_num:.2f} PB"


def find_mysqldump(custom_bin):
    if custom_bin and (os.path.exists(custom_bin) or shutil.which(custom_bin)):
        return custom_bin

    which_path = shutil.which("mysqldump")
    if which_path:
        return which_path

    # Rutas comunes en Windows (Laragon / XAMPP / MySQL Server)
    windows_paths = [
        r"C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe",
        r"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
        r"C:\xampp\mysql\bin\mysqldump.exe",
    ]
    for p in windows_paths:
        if os.path.exists(p):
            return p

    # Rutas comunes en Linux
    linux_paths = ["/usr/bin/mysqldump", "/usr/local/bin/mysqldump"]
    for p in linux_paths:
        if os.path.exists(p):
            return p

    return "mysqldump"


def create_multipart_request(url, fields, files, token, timeout=1800):
    boundary = f"----WebKitFormBoundary{uuid.uuid4().hex}"
    body = bytearray()

    # Campos simples
    for name, value in fields.items():
        body.extend(f"--{boundary}\r\n".encode("utf-8"))
        body.extend(f'Content-Disposition: form-data; name="{name}"\r\n\r\n'.encode("utf-8"))
        body.extend(f"{value}\r\n".encode("utf-8"))

    # Archivo
    for name, filepath in files.items():
        filename = os.path.basename(filepath)
        mime_type = mimetypes.guess_type(filepath)[0] or "application/octet-stream"
        body.extend(f"--{boundary}\r\n".encode("utf-8"))
        body.extend(f'Content-Disposition: form-data; name="{name}"; filename="{filename}"\r\n'.encode("utf-8"))
        body.extend(f"Content-Type: {mime_type}\r\n\r\n".encode("utf-8"))

        with open(filepath, "rb") as f:
            while chunk := f.read(1024 * 1024):  # Chunks de 1MB
                body.extend(chunk)
        body.extend(b"\r\n")

    body.extend(f"--{boundary}--\r\n".encode("utf-8"))

    req = urllib.request.Request(url, data=body, method="POST")
    req.add_header("Content-Type", f"multipart/form-data; boundary={boundary}")
    req.add_header("Authorization", f"Bearer {token}")
    req.add_header("User-Agent", "LIDiag-DCM-Agent/1.0")

    return req


def main():
    args = parse_arguments()
    config = load_config(args.config, args)

    print("=========================================================")
    print("      AGENTE LOCAL DE BACKUPS DCM — LIDiag               ")
    print("=========================================================")
    print(f" Sede ID          : {config['sede_id']}")
    print(f" Base de Datos    : {config['db_name']} @ {config['db_host']}:{config['db_port']}")
    print(f" Usuario MySQL    : {config['db_user']}")
    print(f" Endpoint LIDiag  : {config['api_url']}")
    print("=========================================================")

    temp_dir = os.path.abspath(config.get("temp_dir", "./temp_backups"))
    os.makedirs(temp_dir, exist_ok=True)

    timestamp = datetime.datetime.now().strftime("%Y-%m-%d_%H%M%S")
    sql_filename = f"database_dcm_sede_{config['sede_id']}_{config['db_name']}_{timestamp}.sql"
    sql_path = os.path.join(temp_dir, sql_filename)
    zip_filename = f"backup_dcm_sede_{config['sede_id']}_{timestamp}.zip"
    zip_path = os.path.join(temp_dir, zip_filename)

    mysqldump_bin = find_mysqldump(config.get("mysqldump_bin"))
    print(f"[1/4] Ejecutable mysqldump detectado: {mysqldump_bin}")

    # Paso 1: Dump local
    print(f"[2/4] Generando volcado MySQL en disco local ({sql_filename})...")
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
        print(f"[ERROR] No se pudo ejecutar mysqldump en '{mysqldump_bin}'. Verifica que esté instalado o configúralo en config.json.")
        sys.exit(1)

    if not os.path.exists(sql_path) or os.path.getsize(sql_path) == 0:
        print("[ERROR] El archivo .sql generado no existe o está vacío.")
        sys.exit(1)

    dump_size = os.path.getsize(sql_path)
    dump_duration = round(time.time() - start_dump, 2)
    print(f"      [OK] Dump local completado en {dump_duration}s (Tamaño sin comprimir: {format_bytes(dump_size)})")

    # Paso 2: Compresión local en ZIP
    print(f"[3/4] Comprimiendo archivo a formato ZIP ({zip_filename})...")
    start_zip = time.time()

    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        zf.write(sql_path, arcname=sql_filename)

    # Eliminar archivo SQL crudo de inmediato para liberar espacio en disco del hospital
    try:
        os.remove(sql_path)
    except Exception:
        pass

    zip_size = os.path.getsize(zip_path)
    zip_duration = round(time.time() - start_zip, 2)
    ratio = round(dump_size / zip_size, 1) if zip_size > 0 else 1
    print(f"      [OK] Compresión lista en {zip_duration}s: {format_bytes(zip_size)} (Ratio {ratio}x)")

    # Paso 3: Transmisión Webhook hacia LIDiag
    print(f"[4/4] Transmitiendo archivo comprimido al servidor LIDiag ({config['api_url']})...")
    start_upload = time.time()

    fields = {
        "sede_id": str(config["sede_id"]),
    }
    files = {
        "backup_file": zip_path,
    }

    try:
        req = create_multipart_request(config["api_url"], fields, files, config["api_token"], timeout=config.get("timeout_upload", 1800))

        # En caso de servidores locales con certificado autofirmado (ignorar SSL verify en local si se requiere)
        import ssl
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE

        with urllib.request.urlopen(req, context=ctx, timeout=config.get("timeout_upload", 1800)) as response:
            status_code = response.getcode()
            response_data = response.read().decode("utf-8")

            if status_code in (200, 201):
                upload_duration = round(time.time() - start_upload, 2)
                print(f"      [OK] ¡Subida exitosa en {upload_duration}s! (HTTP {status_code})")
                try:
                    res_json = json.loads(response_data)
                    print(f"      Respuesta servidor: {res_json.get('message', 'Completado')}")
                except Exception:
                    print(f"      Respuesta servidor: {response_data[:120]}")
            else:
                print(f"[ERROR] Servidor LIDiag respondió con código HTTP {status_code}: {response_data}")
                sys.exit(1)

    except urllib.error.HTTPError as e:
        err_msg = e.read().decode("utf-8") if e.fp else str(e)
        print(f"[ERROR] HTTP Error {e.code}: {err_msg}")
        sys.exit(1)
    except Exception as e:
        print(f"[ERROR] Error de conexión durante la subida al servidor LIDiag: {e}")
        sys.exit(1)

    # Limpieza final del archivo .zip si no se solicitó keep_local
    if not config.get("keep_local", False):
        try:
            os.remove(zip_path)
            print("      [OK] Archivo temporal local eliminado.")
        except Exception:
            pass
    else:
        print(f"      [INFO] Archivo conservado en: {zip_path}")

    total_duration = round(dump_duration + zip_duration + (time.time() - start_upload), 2)
    print("=========================================================")
    print(f" BACKUP COMPLETADO EXITOSAMENTE EN {total_duration}s")
    print("=========================================================")


if __name__ == "__main__":
    main()
