#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
scheduler_runner.py — Supervisor en segundo plano de Laravel Scheduler y Backups LIDiag.

Ejecuta 'php artisan schedule:run' de forma totalmente silenciosa, sin abrir consolas negras,
con auto-detección del binario de PHP en Laragon / Sistema y registro rotativo de logs.

Uso:
  python scheduler_runner.py          -> Ejecuta una sola ronda (ideal para Programador de Tareas)
  python scheduler_runner.py --daemon -> Corre en bucle continuo cada 60 segundos como servicio

Autor: Jhon Javer Lozano Arce
Fecha: 2026-10-27
"""

import glob
import logging
import os
import shutil
import subprocess
import sys
import time

PROJECT_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_DIR = os.path.join(PROJECT_DIR, "storage", "logs")
os.makedirs(LOG_DIR, exist_ok=True)
LOG_FILE = os.path.join(LOG_DIR, "scheduler_runner.log")

logging.basicConfig(
    filename=LOG_FILE,
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
    encoding="utf-8"
)


def find_php_binary() -> str:
    """
    Localiza el binario de PHP en el entorno de Laragon o en el PATH del sistema.
    """
    # 1. Intentar rutas estándar de Laragon
    laragon_phps = glob.glob(r"C:\laragon\bin\php\php-*\php.exe")
    if laragon_phps:
        laragon_phps.sort(reverse=True)
        return laragon_phps[0]

    # 2. Intentar enlace simbólico current de Laragon
    current_php = r"C:\laragon\bin\php\current\php.exe"
    if os.path.isfile(current_php):
        return current_php

    # 3. Intentar PATH del sistema operativo
    system_php = shutil.which("php")
    if system_php:
        return system_php

    # 4. Fallback directo a php
    return "php"


def run_scheduler_once() -> int:
    """
    Ejecuta una iteración de 'php artisan schedule:run'.
    """
    php_bin = find_php_binary()
    artisan_file = os.path.join(PROJECT_DIR, "artisan")

    if not os.path.isfile(artisan_file):
        logging.error("No se encontró el archivo artisan en %s", artisan_file)
        return 1

    # CREATE_NO_WINDOW (0x08000000) en Windows evita destellos de consola
    creation_flags = 0x08000000 if os.name == "nt" else 0

    try:
        proc = subprocess.run(
            [php_bin, artisan_file, "schedule:run"],
            cwd=PROJECT_DIR,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            creationflags=creation_flags,
            text=True,
            timeout=300,
            encoding="utf-8",
            errors="replace"
        )

        stdout = proc.stdout.strip() if proc.stdout else ""
        stderr = proc.stderr.strip() if proc.stderr else ""

        if stdout and "No scheduled commands are ready to run" not in stdout:
            logging.info("Salida Scheduler:\n%s", stdout)

        if proc.returncode != 0:
            logging.warning("Scheduler terminó con código %s. Stderr: %s", proc.returncode, stderr)

        return proc.returncode

    except subprocess.TimeoutExpired:
        logging.error("Timeout de 300 segundos superado al ejecutar schedule:run.")
        return 124
    except Exception as exc:
        logging.error("Excepción inesperada al ejecutar schedule:run: %s", exc)
        return 1


if __name__ == "__main__":
    if "--daemon" in sys.argv:
        logging.info("=== Daemon de Laravel Scheduler iniciado en segundo plano ===")
        try:
            while True:
                run_scheduler_once()
                time.sleep(60)
        except KeyboardInterrupt:
            logging.info("Daemon de Laravel Scheduler detenido por el usuario.")
    else:
        exit_code = run_scheduler_once()
        sys.exit(exit_code)
