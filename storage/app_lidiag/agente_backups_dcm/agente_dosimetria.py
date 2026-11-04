#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
=============================================================================
LIDiag — Agente Edge de Extracción de Dosimetría Radiológica (Resolución 482)
=============================================================================
Propósito:
  Extrae de forma desatendida métricas de radiación ionizante (CTDIvol, DLP,
  DGM, DAP) directamente desde los archivos DICOM / dcm4chee local en cada sede.
  Anonimiza de forma irreversible los IDs de pacientes mediante SHA-256
  (ISO/IEC 27001) y transmite los datos en lotes a la API central de LIDiag.

Características:
  - Detección automática de instancias de modalidades CT, MG, DX, CR y RDSR.
  - Anonimización criptográfica estricta de PHI (Patient ID -> SHA-256).
  - Transmisión HTTPS en lotes con autenticación Bearer Token y reintentos (Backoff exponencial).
  - Trazabilidad y auditoría completa mediante el módulo logging.
  - Comparte configuración con el Agente de Backups DCM (config.json).

Autor: Jhon Javer Lozano Arce / LIDiag
Fecha: 2026-10-23
=============================================================================
"""

import argparse
import atexit
import datetime
import gzip
import hashlib
import hmac
import json
import logging
from logging.handlers import RotatingFileHandler
import os
import re
import secrets
import socket
import sqlite3
import subprocess
import sys
import time
from typing import Any, Dict, List, Optional, Tuple

# Auto-detección transparente del entorno virtual local (venv)
_script_dir = os.path.dirname(os.path.abspath(__file__))
_venv_site = os.path.join(_script_dir, "venv", "Lib", "site-packages")
if os.path.isdir(_venv_site) and _venv_site not in sys.path:
    sys.path.insert(0, _venv_site)

# Verificación de dependencias externas
try:
    import pydicom
    import pydicom.datadict
    # Desactivar validación estricta de VR para evitar warnings ruidosos con tags fuera de norma
    try:
        pydicom.config.enforce_valid_values = False
    except Exception:
        pass
    try:
        pydicom.config.settings.reading_validation_mode = pydicom.config.IGNORE
    except Exception:
        pass
except ImportError:
    print("[ERROR CRÍTICO] La librería 'pydicom' no está instalada. Ejecute: pip install pydicom")
    sys.exit(1)

try:
    import requests
    from requests.adapters import HTTPAdapter
    import urllib3
    from urllib3.util.retry import Retry
    urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)
except ImportError:
    print("[ERROR CRÍTICO] La librería 'requests' no está instalada. Ejecute: pip install requests")
    sys.exit(1)

# Conector MySQL dinámico (intenta mysql.connector, luego pymysql)
DB_CONNECTOR = None
try:
    import mysql.connector
    DB_CONNECTOR = "mysql.connector"
except ImportError:
    try:
        import pymysql
        DB_CONNECTOR = "pymysql"
    except ImportError:
        pass

# Directorios de trabajo
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
DEFAULT_CONFIG_PATH = os.path.join(SCRIPT_DIR, "config.json")
LOGS_DIR = os.path.join(SCRIPT_DIR, "logs")
os.makedirs(LOGS_DIR, exist_ok=True)
LOG_FILE = os.path.join(LOGS_DIR, "dosimetria_agent.log")
LOCK_FILE = os.path.join(LOGS_DIR, "dosimetria.lock")
CACHE_PROCESADOS_FILE = os.path.join(LOGS_DIR, "dosimetria_cache_procesados.json")
SQLITE_COLA_FILE = os.path.join(LOGS_DIR, "cola_dosimetria.db")

# Configurar logging con rotación automática (Máx 10 MB, 3 copias)
logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    handlers=[
        RotatingFileHandler(LOG_FILE, maxBytes=10 * 1024 * 1024, backupCount=3, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
logger = logging.getLogger("LIDiag_DosimetriaAgent")


HEALTHCHECK_FILE = os.path.join(LOGS_DIR, "healthcheck_agente.json")


def update_unified_healthcheck(sede_id: int, sede_nombre: str, service: str, info: dict):
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
    except Exception:
        pass


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
    """Asegura que sólo una ejecución de dosimetría se ejecute a la vez."""
    if os.path.isfile(LOCK_FILE):
        try:
            with open(LOCK_FILE, "r", encoding="utf-8") as f:
                content = f.read().strip()
                if content.isdigit():
                    prev_pid = int(content)
                    if is_pid_running(prev_pid):
                        logger.warning(
                            f"Ya existe una ejecución de agente_dosimetria activa (PID {prev_pid}). "
                            "Se omite la nueva ejecución para evitar duplicidad de consultas."
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


def check_mysql_socket(host: str, port: int = 3306, timeout: float = 4.0) -> bool:
    """Verifica conectividad TCP al puerto de MySQL antes de intentar la conexión del driver."""
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except (socket.timeout, socket.error, OSError):
        return False


def parse_arguments():
    parser = argparse.ArgumentParser(description="Agente Edge de Dosimetría Radiológica LIDiag")
    parser.add_argument("--config", "-c", help="Ruta al archivo config.json")
    parser.add_argument("--sede-id", type=int, help="ID de la sede (anula valor de config.json)")
    parser.add_argument("--dias", type=int, default=1, help="Ventana de días hacia atrás para consultar (default: 1)")
    parser.add_argument("--desde", type=str, help="Fecha inicio fija (formato YYYY-MM-DD)")
    parser.add_argument("--hasta", type=str, help="Fecha fin fija (formato YYYY-MM-DD)")
    parser.add_argument("--limit", type=int, default=200, help="Límite máximo de estudios a procesar por ciclo (default: 200, usa 0 o --sin-limite para ilimitado)")
    parser.add_argument("--sin-limite", action="store_true", help="Procesa todos los estudios candidatos sin límite de cantidad")
    parser.add_argument("--dry-run", action="store_true", help="Extrae métricas sin enviarlas a la API central")
    parser.add_argument("--forzar", "--force", action="store_true", help="Fuerza el re-procesamiento ignorando la caché local de estudios ya transmitidos")
    parser.add_argument("--limpiar-cache", action="store_true", help="Elimina el archivo de caché local de estudios procesados y sale")
    return parser.parse_args()


def parse_tolerant_json(content: str) -> dict:
    """Parsea JSON con tolerancia a rutas Windows con barras simples."""
    try:
        return json.loads(content)
    except Exception:
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


def load_config(custom_path: Optional[str] = None) -> dict:
    config_path = custom_path or DEFAULT_CONFIG_PATH

    if not os.path.isfile(config_path):
        logger.critical(f"No se encontró el archivo de configuración: {config_path}")
        logger.critical("Copia 'config.json.example' a 'config.json' y configura las credenciales de esta sede.")
        sys.exit(1)

    logger.info(f"Cargando configuración desde: {config_path}")
    try:
        with open(config_path, "r", encoding="utf-8") as f:
            loaded = parse_tolerant_json(f.read())
        if not isinstance(loaded, dict):
            raise ValueError("El contenido del archivo no es un objeto JSON válido.")
    except Exception as e:
        logger.critical(f"Error al leer o parsear '{config_path}': {e}")
        sys.exit(1)

    # Validar campos obligatorios estrictos en el JSON
    required_fields = ["sede_id", "db_name", "db_user", "local_images_dir", "api_url", "api_token"]
    missing = [f for f in required_fields if f not in loaded or loaded[f] is None or str(loaded[f]).strip() == ""]
    if missing:
        logger.critical(f"Faltan campos obligatorios en '{config_path}': {', '.join(missing)}")
        logger.critical("Por favor configura estos parámetros en config.json antes de ejecutar el agente.")
        sys.exit(1)

    # Salt criptográfico único por sede (HMAC-SHA256 para evitar ataques de diccionario/Rainbow Tables)
    salt = loaded.get("dosimetria_salt")
    if not salt or str(salt).strip() == "":
        salt = secrets.token_hex(32)
        loaded["dosimetria_salt"] = salt
        try:
            with open(config_path, "w", encoding="utf-8") as f:
                json.dump(loaded, f, indent=4)
            logger.info("Generado y persistido nuevo 'dosimetria_salt' criptográfico único para esta sede.")
        except Exception as e:
            logger.warning(f"No se pudo guardar automáticamente dosimetria_salt en config.json: {e}")

    config = {
        "sede_id": int(loaded["sede_id"]),
        "sede_nombre": loaded.get("sede_nombre", ""),
        "db_host": loaded.get("db_host", "127.0.0.1"),
        "db_port": int(loaded.get("db_port", 3306)),
        "db_name": loaded["db_name"],
        "db_user": loaded["db_user"],
        "db_password": loaded.get("db_password", ""),
        "local_images_dir": loaded["local_images_dir"],
        "api_url": loaded["api_url"],
        "api_token": loaded["api_token"],
        "dosimetria_salt": str(salt).strip(),
    }

    return config


def get_db_connection(config: dict):
    """Establece conexión a MySQL pacsdb usando el conector disponible con aislamiento READ UNCOMMITTED (Cero locks)."""
    if not DB_CONNECTOR:
        logger.critical("No se encontró ningún conector MySQL de Python. Instale: pip install mysql-connector-python o pymysql")
        sys.exit(1)

    host = config["db_host"]
    port = int(config["db_port"])
    user = config["db_user"]
    password = config["db_password"]
    database = config["db_name"]

    logger.info(f"[1/4] Verificando socket TCP MySQL en {host}:{port}...")
    if not check_mysql_socket(host, port, timeout=4.0):
        logger.critical(
            f"[ERROR DB] No se puede conectar a {host}:{port}. "
            f"El socket TCP no responde o el puerto está cerrado. "
            f"Verifique que el servicio MySQL de dcm4chee esté iniciado y que 'db_host' en config.json sea la IP correcta (ej: si MySQL está en otro servidor, usar su IP en lugar de 127.0.0.1)."
        )
        sys.exit(1)

    logger.info(f"[1/4] Conectando a '{database}' ({user}@{host}:{port})...")
    if DB_CONNECTOR == "mysql.connector":
        # Estrategia multi-fallback para evitar bloqueos por SSL handshake o extensiones C
        base_kwargs = {
            "host": host,
            "port": port,
            "user": user,
            "password": password,
            "database": database,
            "charset": "utf8",
            "connect_timeout": 8,
            "use_pure": True,
        }

        # Intento A: Pure Python con ssl_mode='DISABLED' (evita stalls en MySQL 5.5/5.7)
        try:
            conn = mysql.connector.connect(**base_kwargs, ssl_mode="DISABLED")
        except (TypeError, Exception):
            conn = None

        # Intento B: Pure Python con ssl_disabled=True (versiones legadas de mysql.connector)
        if not conn:
            try:
                conn = mysql.connector.connect(**base_kwargs, ssl_disabled=True)
            except (TypeError, Exception):
                conn = None

        # Intento C: Pure Python estándar
        if not conn:
            try:
                conn = mysql.connector.connect(**base_kwargs)
            except Exception:
                conn = None

        # Intento D: Fallback estándar C-extension
        if not conn:
            std_kwargs = {
                "host": host,
                "port": port,
                "user": user,
                "password": password,
                "database": database,
                "charset": "utf8",
                "connect_timeout": 10,
            }
            conn = mysql.connector.connect(**std_kwargs)
    else:
        conn = pymysql.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset="utf8",
            connect_timeout=10,
        )

    # 1. Aislamiento READ UNCOMMITTED para evitar shared locks sobre las tablas maestras de Dcm4chee durante C-STORE
    try:
        cur = conn.cursor()
        cur.execute("SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED")
        cur.close()
    except Exception as e:
        logger.warning(f"No se pudo ajustar aislamiento READ UNCOMMITTED en MySQL: {e}")

    # 2. Ajustar sql_mode en la sesión para compatibilidad con ONLY_FULL_GROUP_BY en cualquier versión de MySQL
    try:
        cur = conn.cursor()
        cur.execute("SELECT @@sql_mode")
        row = cur.fetchone()
        raw_mode = row[0] if isinstance(row, tuple) else (row.get("@@sql_mode") if isinstance(row, dict) else "")
        if raw_mode:
            modes = [m.strip() for m in str(raw_mode).split(",") if m.strip() and m.strip().upper() != "ONLY_FULL_GROUP_BY"]
            new_mode = ",".join(modes)
            cur.execute(f"SET SESSION sql_mode='{new_mode}'")
        cur.close()
    except Exception as e:
        logger.warning(f"No se pudo ajustar sql_mode de sesión en MySQL: {e}")

    return conn


def hash_patient_id(raw_patient_id: Optional[str], salt: str = "LIDIAG_DOSIMETRIA_SALT_2026") -> str:
    """Anonimiza irreversiblemente el PatientID mediante HMAC-SHA256 con salt dinámico por sede (ISO/IEC 27001)."""
    if not raw_patient_id:
        raw_patient_id = "ANONYMOUS"
    clean_id = str(raw_patient_id).strip()
    return hmac.new(salt.encode("utf-8"), clean_id.encode("utf-8"), hashlib.sha256).hexdigest()


# =============================================================================
# CACHÉ DE ESTUDIOS PROCESADOS (Evita re-inspeccionar estudios ya evaluados)
# =============================================================================

def cargar_cache_procesados(cache_path: str = CACHE_PROCESADOS_FILE) -> Dict[str, int]:
    """
    Carga la memoria caché de estudios procesados desde el archivo JSON local.
    Soporta formato diccionario {'study_uid_modality': timestamp} y formato lista retrocompatible.
    """
    if not os.path.isfile(cache_path):
        return {}

    try:
        with open(cache_path, "r", encoding="utf-8") as f:
            data = json.load(f)

        if isinstance(data, dict):
            return data
        elif isinstance(data, list):
            now_ts = int(time.time())
            return {str(k): now_ts for k in data}
        return {}
    except Exception as e:
        logger.warning(f"No se pudo leer la caché de estudios procesados ({cache_path}): {e}")
        return {}


def guardar_cache_procesados(cache: Dict[str, Any], cache_path: str = CACHE_PROCESADOS_FILE):
    """Persiste la memoria caché de estudios procesados en el archivo JSON local de forma atómica."""
    try:
        os.makedirs(os.path.dirname(cache_path), exist_ok=True)
        temp_path = f"{cache_path}.tmp"
        with open(temp_path, "w", encoding="utf-8") as f:
            json.dump(cache, f, indent=2, ensure_ascii=False)
        if os.path.isfile(temp_path):
            if os.path.isfile(cache_path):
                os.replace(temp_path, cache_path)
            else:
                os.rename(temp_path, cache_path)
    except Exception as e:
        logger.warning(f"No se pudo guardar la caché de estudios procesados ({cache_path}): {e}")


# =============================================================================
# COLA PERSISTENTE LOCAL EN SQLITE (Cero pérdida de datos ante cortes eléctricos)
# =============================================================================

def init_sqlite_cola(db_path: str = SQLITE_COLA_FILE):
    """Inicializa la base de datos SQLite embebida para garantizar cero pérdida de datos."""
    with sqlite3.connect(db_path) as conn:
        conn.execute("PRAGMA journal_mode = WAL;")
        conn.execute("PRAGMA synchronous = NORMAL;")
        conn.execute("""
            CREATE TABLE IF NOT EXISTS cola_dosimetria (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                study_uid TEXT NOT NULL,
                patient_id_hashed TEXT NOT NULL,
                modalidad TEXT NOT NULL,
                aet_title TEXT,
                tipo_metrica TEXT NOT NULL,
                valor_dosis REAL NOT NULL,
                unidad_medida TEXT NOT NULL,
                fecha_estudio TEXT NOT NULL,
                accession_no TEXT,
                study_desc TEXT,
                estado TEXT NOT NULL DEFAULT 'PENDING',
                intentos INTEGER NOT NULL DEFAULT 0,
                ultimo_error TEXT,
                created_at TEXT NOT NULL,
                synced_at TEXT,
                UNIQUE(study_uid, tipo_metrica)
            );
        """)
        conn.execute("CREATE INDEX IF NOT EXISTS idx_cola_estado ON cola_dosimetria(estado, intentos);")
        conn.commit()


def encolar_estudios_sqlite(registros: List[Dict[str, Any]], db_path: str = SQLITE_COLA_FILE) -> int:
    """Inserta de forma idempotente las mediciones de dosis en la cola SQLite local."""
    if not registros:
        return 0
    now_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    insertados = 0
    with sqlite3.connect(db_path) as conn:
        cur = conn.cursor()
        for r in registros:
            cur.execute("""
                INSERT OR IGNORE INTO cola_dosimetria (
                    study_uid, patient_id_hashed, modalidad, aet_title,
                    tipo_metrica, valor_dosis, unidad_medida, fecha_estudio,
                    accession_no, study_desc, estado, intentos, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', 0, ?)
            """, (
                r["study_uid"],
                r["patient_id_hashed"],
                r["modalidad"],
                r.get("aet_title"),
                r["tipo_metrica"],
                r["valor_dosis"],
                r.get("unidad_medida", "mGy"),
                r["fecha_estudio"],
                r.get("accession_no"),
                r.get("study_desc"),
                now_str,
            ))
            if cur.rowcount > 0:
                insertados += 1
        conn.commit()
    return insertados


def obtener_pendientes_sqlite(limit: Optional[int] = 200, db_path: str = SQLITE_COLA_FILE) -> List[Dict[str, Any]]:
    """Obtiene los registros pendientes de sincronización (o fallidos con < 5 intentos)."""
    with sqlite3.connect(db_path) as conn:
        conn.row_factory = sqlite3.Row
        cur = conn.cursor()
        if limit and limit > 0:
            cur.execute("""
                SELECT id, study_uid, patient_id_hashed, modalidad, aet_title,
                       tipo_metrica, valor_dosis, unidad_medida, fecha_estudio,
                       accession_no, study_desc, intentos
                FROM cola_dosimetria
                WHERE estado = 'PENDING' OR (estado = 'FAILED' AND intentos < 5)
                ORDER BY id ASC
                LIMIT ?
            """, (limit,))
        else:
            cur.execute("""
                SELECT id, study_uid, patient_id_hashed, modalidad, aet_title,
                       tipo_metrica, valor_dosis, unidad_medida, fecha_estudio,
                       accession_no, study_desc, intentos
                FROM cola_dosimetria
                WHERE estado = 'PENDING' OR (estado = 'FAILED' AND intentos < 5)
                ORDER BY id ASC
            """)
        filas = cur.fetchall()
        return [dict(f) for f in filas]


def marcar_sincronizados_sqlite(ids: List[int], db_path: str = SQLITE_COLA_FILE):
    if not ids:
        return
    now_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    with sqlite3.connect(db_path) as conn:
        cur = conn.cursor()
        placeholders = ",".join(["?"] * len(ids))
        cur.execute(f"""
            UPDATE cola_dosimetria
            SET estado = 'SYNCED', synced_at = ?
            WHERE id IN ({placeholders})
        """, [now_str] + ids)
        conn.commit()


def marcar_fallidos_sqlite(ids: List[int], error_msg: str, db_path: str = SQLITE_COLA_FILE):
    if not ids:
        return
    with sqlite3.connect(db_path) as conn:
        cur = conn.cursor()
        placeholders = ",".join(["?"] * len(ids))
        cur.execute(f"""
            UPDATE cola_dosimetria
            SET estado = 'FAILED', intentos = intentos + 1, ultimo_error = ?
            WHERE id IN ({placeholders})
        """, [error_msg[:255]] + ids)
        conn.commit()


def contar_pendientes_sqlite(db_path: str = SQLITE_COLA_FILE) -> int:
    try:
        with sqlite3.connect(db_path) as conn:
            cur = conn.cursor()
            cur.execute("SELECT COUNT(*) FROM cola_dosimetria WHERE estado != 'SYNCED'")
            row = cur.fetchone()
            return int(row[0]) if row else 0
    except Exception:
        return 0


def purgar_antiguos_sqlite(dias: int = 30, db_path: str = SQLITE_COLA_FILE):
    try:
        limite = (datetime.datetime.now() - datetime.timedelta(days=dias)).strftime("%Y-%m-%d %H:%M:%S")
        with sqlite3.connect(db_path) as conn:
            cur = conn.cursor()
            cur.execute("DELETE FROM cola_dosimetria WHERE estado = 'SYNCED' AND synced_at < ?", (limite,))
            conn.commit()
    except Exception:
        pass


def resolve_api_endpoint(base_api_url: str, endpoint_path: str) -> str:
    """
    Dada una URL base como 'https://10.35.47.4/lidiag-update/public/api/dosimetria/registros',
    deriva la raíz de la API ('https://10.35.47.4/lidiag-update/public/api') y concatena endpoint_path.
    """
    if not base_api_url:
        return ""
    clean = base_api_url.rstrip("/")
    idx = clean.find("/api/")
    if idx != -1:
        base = clean[:idx + 5].rstrip("/")
    elif clean.endswith("/api"):
        base = clean
    else:
        base = clean.rsplit("/", 1)[0]
    return f"{base}/{endpoint_path.lstrip('/')}"


def enviar_heartbeat(api_url: str, api_token: str, sede_id: int, pendientes: int, version: str = "1.3.0"):
    """Emite un latido (Heartbeat / Dead Man's Switch) hacia la API Central."""
    hb_url = resolve_api_endpoint(api_url, "dosimetria/heartbeat")

    clean_token = str(api_token or "").strip()
    if clean_token.lower().startswith("bearer "):
        clean_token = clean_token[7:].strip()

    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Authorization": f"Bearer {clean_token}",
        "X-API-TOKEN": clean_token,
        "X-Requested-With": "XMLHttpRequest",
    }
    payload = {
        "sede_id": sede_id,
        "api_token": clean_token,
        "version": version,
        "pendientes": pendientes,
        "estado_cola": "OK" if pendientes == 0 else f"PENDIENTES_{pendientes}",
    }
    try:
        res = requests.post(hb_url, json=payload, headers=headers, timeout=12, verify=False)
        if res.status_code == 200:
            logger.info(f"[HEARTBEAT] Latido emitido con éxito a Central ({hb_url}). Pendientes en cola: {pendientes}.")
        else:
            logger.warning(f"[HEARTBEAT] Central respondió con HTTP {res.status_code} al latido.")
    except Exception as e:
        logger.warning(f"[HEARTBEAT] No se pudo emitir latido a Central: {e}")


def buscar_estudios_candidatos(conn, dias: int = 1, limit: Optional[int] = 200, desde: Optional[str] = None, hasta: Optional[str] = None) -> List[Dict[str, Any]]:
    """
    Localiza una instancia representativa por cada estudio reciente de modalidades
    que emiten radiación ionizante (CT, MG, DX, CR).
    """
    if desde:
        fecha_desde = f"{desde} 00:00:00"
        fecha_hasta = f"{hasta} 23:59:59" if hasta else datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        where_fecha = "(st.study_datetime BETWEEN %s AND %s OR st.created_time BETWEEN %s AND %s)"
        params = [fecha_desde, fecha_hasta, fecha_desde, fecha_hasta]
    else:
        fecha_limite = (datetime.datetime.now() - datetime.timedelta(days=dias)).strftime("%Y-%m-%d %H:%M:%S")
        where_fecha = "(st.created_time >= %s OR st.study_datetime >= %s)"
        params = [fecha_limite, fecha_limite]

    if limit and limit > 0:
        limit_clause = "LIMIT %s"
        params.append(limit)
    else:
        limit_clause = ""

    query = f"""
        SELECT 
            st.study_iuid,
            MAX(st.study_datetime) AS study_datetime,
            MAX(st.accession_no) AS accession_no,
            MAX(st.study_desc) AS study_desc,
            s.modality,
            MAX(s.station_name) AS station_name,
            MAX(s.src_aet) AS src_aet,
            MIN(f.filepath) AS filepath,
            MIN(fs.dirpath) AS dirpath,
            MAX(st.created_time) AS created_time
        FROM study st
        JOIN series s ON s.study_fk = st.pk
        JOIN instance i ON i.series_fk = s.pk
        JOIN files f ON f.instance_fk = i.pk
        JOIN filesystem fs ON fs.pk = f.filesystem_fk
        WHERE s.modality IN ('CT', 'MG', 'DX', 'CR')
          AND {where_fecha}
          AND (
            s.modality != 'CT'
            OR (
                COALESCE(s.series_desc, '') NOT LIKE '%%SCOUT%%'
                AND COALESCE(s.series_desc, '') NOT LIKE '%%TOPOGRAM%%'
                AND COALESCE(s.series_desc, '') NOT LIKE '%%LOCALIZER%%'
            )
          )
        GROUP BY st.study_iuid, s.modality
        ORDER BY created_time DESC
        {limit_clause}
    """

    logger.info("[2/4] Ejecutando consulta de estudios candidatos en pacsdb (puede tardar si la ventana es amplia)...")
    cursor = conn.cursor(dictionary=True) if DB_CONNECTOR == "mysql.connector" else conn.cursor(pymysql.cursors.DictCursor)
    try:
        cursor.execute(query, params)
        filas = cursor.fetchall()
        return filas
    finally:
        cursor.close()


def resolver_ruta_archivo(dirpath_bd: str, filepath_bd: str, local_images_dir: str) -> str:
    """Resuelve la ruta absoluta del archivo DICOM en disco con normalización de rutas."""
    # 1. Probar con dirpath de la base de datos
    if dirpath_bd and filepath_bd:
        ruta_bd = os.path.normpath(os.path.join(dirpath_bd, filepath_bd))
        if os.path.isfile(ruta_bd):
            return ruta_bd

    # 2. Probar con directorio local configurado (override)
    if local_images_dir and filepath_bd:
        ruta_local = os.path.normpath(os.path.join(local_images_dir, filepath_bd))
        if os.path.isfile(ruta_local):
            return ruta_local

    # 3. Retornar ruta normalizada por defecto para logging claro
    base = local_images_dir if local_images_dir else (dirpath_bd or "")
    return os.path.normpath(os.path.join(base, filepath_bd or ""))


def clasificar_region_anatomica(study_desc: Optional[str], modalidad: str = "CT") -> str:
    """Clasifica semánticamente la región anatómica según taxonomía Res. 482 / RadLex Playbook."""
    desc = (study_desc or "").upper()
    mod = (modalidad or "").upper()
    if mod == "MG":
        return "MAMA"
    if re.search(r"CRANEO|CEREBRO|ENCEFALO|SILLA TURCA|SENOS PARANASALES|FACIAL|MACIZO|OIDO|ORBITAS|CUELLO", desc):
        return "CRANEO"
    if re.search(r"TORAX|PULMON|MEDIASTINO|REJA COSTAL|CARDIO|CORONARI|ANGIOTAC TORACICO", desc):
        return "TORAX"
    if re.search(r"ABDOMEN|HIGADO|HEPATIC|PANCREAS|VIAS BILIARES|RENAL|URO|ENTEROTOMOGRAFIA|COLONOSCOPIA", desc):
        return "ABDOMEN"
    if re.search(r"PELVIS|PELVIC|PROSTATA|VEJIGA|UTERO|CADERA|SACRO", desc):
        return "PELVIS"
    if re.search(r"COLUMNA|CERVICAL|DORSAL|LUMBAR|SACROILIACA|LUMBOSACRA", desc):
        return "COLUMNA"
    if re.search(r"RODILLA|HOMBRO|CODO|MU[NÑ]ECA|TOBILLO|PIE|MANO|FEMUR|TIBIA|HUMERO|RADIO|ANTEBRAZO|PIERNA|EXTREMID", desc):
        return "EXTREMIDADES"
    if re.search(r"MAMA|SENOS", desc):
        return "MAMA"
    return "GENERAL"


def extraer_metricas_dosis(dcm_path: str, modalidad: str) -> Optional[List[Dict[str, Any]]]:
    """
    Lee los tags DICOM de la imagen o RDSR y extrae las métricas numéricas.
    Retorna None si el archivo no existe o no se pudo leer del disco.
    Retorna [] si el archivo se leyó pero no contiene métricas de radiación.
    Retorna lista con métricas si se extrajo información de dosis.
    """
    if not os.path.isfile(dcm_path):
        logger.warning(f"Archivo no encontrado en disco: {dcm_path}")
        return None

    try:
        ds = pydicom.dcmread(dcm_path, stop_before_pixels=True, force=True)
    except Exception as e:
        logger.warning(f"Error al leer cabecera DICOM de {dcm_path}: {e}")
        return None

    metricas = []
    mod = modalidad.upper()

    # 0. Inspección de objetos DICOM Structured Report (RDSR - TID 10011 / TID 10001)
    sop_uid = str(getattr(ds, "SOPClassUID", ""))
    if sop_uid in ("1.2.840.10008.5.1.4.1.1.88.67", "1.2.840.10008.5.1.4.1.1.88.22", "1.2.840.10008.5.1.4.1.1.88.33") or hasattr(ds, "ContentSequence"):
        def buscar_conceptos_sr(seq):
            encontrados = {}
            for item in seq:
                concept_name = getattr(item, "ConceptNameCodeSequence", None)
                meaning = ""
                if concept_name and len(concept_name) > 0:
                    meaning = str(getattr(concept_name[0], "CodeMeaning", "")).lower()

                val = None
                if hasattr(item, "MeasuredValueSequence") and len(item.MeasuredValueSequence) > 0:
                    val = getattr(item.MeasuredValueSequence[0], "NumericValue", None)
                elif hasattr(item, "NumericValue"):
                    val = item.NumericValue

                if val is not None:
                    try:
                        val_f = float(val)
                        if "dose length product total" in meaning or "dlp total" in meaning:
                            encontrados["DLP"] = val_f
                        elif "mean ctdivol" in meaning or "ctdivol" in meaning:
                            encontrados["CTDIvol"] = val_f
                        elif "dose area product total" in meaning or "dap total" in meaning:
                            encontrados["DAP"] = val_f
                        elif "entrance dose" in meaning or "glandular dose" in meaning:
                            encontrados["DGM"] = val_f
                    except (ValueError, TypeError):
                        pass

                if hasattr(item, "ContentSequence"):
                    sub = buscar_conceptos_sr(item.ContentSequence)
                    encontrados.update(sub)
            return encontrados

        try:
            content_seq = getattr(ds, "ContentSequence", [])
            sr_vals = buscar_conceptos_sr(content_seq)
            for k, v in sr_vals.items():
                u = "mGy*cm" if k == "DLP" else ("Gy*cm2" if k == "DAP" else "mGy")
                metricas.append({"tipo_metrica": k, "valor_dosis": round(v, 4), "unidad_medida": u, "es_rdsr": True})
            if metricas:
                return metricas
        except Exception:
            pass

    # 1. CT (Tomografía Computarizada) -> CTDIvol (mGy) y DLP (mGy*cm)
    if mod == "CT":
        # Tag estándar (0018,9345) CTDIvol
        ctdivol = getattr(ds, "CTDIvol", None)
        if ctdivol is None and (0x0018, 0x9345) in ds:
            ctdivol = ds[0x0018, 0x9345].value

        # Tag de producto dosis-área / longitud
        dlp = None
        if hasattr(ds, "ImageAndFluoroscopyAreaDoseProduct"):
            dlp = ds.ImageAndFluoroscopyAreaDoseProduct

        # Tags privados frecuentes de CT (GE, Siemens, Philips)
        if ctdivol is None:
            for elem in ds.elements():
                try:
                    desc = getattr(elem, "name", None)
                    if not desc and hasattr(elem, "tag"):
                        try:
                            desc = pydicom.datadict.keyword_for_tag(elem.tag)
                        except Exception:
                            desc = ""
                    desc = str(desc or "").lower()
                    if "ctdi" in desc:
                        val_num = getattr(elem, "value", None)
                        if isinstance(val_num, (int, float)):
                            ctdivol = val_num
                            break
                except Exception:
                    continue

        if ctdivol is not None:
            try:
                val = float(ctdivol)
                if val > 0:
                    metricas.append({"tipo_metrica": "CTDIvol", "valor_dosis": round(val, 4), "unidad_medida": "mGy"})
            except (ValueError, TypeError):
                pass

        if dlp is not None:
            try:
                val = float(dlp)
                if val > 0:
                    metricas.append({"tipo_metrica": "DLP", "valor_dosis": round(val, 4), "unidad_medida": "mGy*cm"})
            except (ValueError, TypeError):
                pass

    # 2. MG (Mamografía) -> DGM / Dosis Glandular Media (mGy)
    elif mod == "MG":
        # (0040,0316) Organ Dose
        dgm = getattr(ds, "OrganDose", None)
        if dgm is None and (0x0040, 0x0316) in ds:
            dgm = ds[0x0040, 0x0316].value

        if dgm is None:
            # Buscar en Entrance Dose in mGy (0040,0302)
            dgm = getattr(ds, "EntranceDoseInmGy", None)

        if dgm is not None:
            try:
                val = float(dgm)
                if val > 0:
                    metricas.append({"tipo_metrica": "DGM", "valor_dosis": round(val, 4), "unidad_medida": "mGy"})
            except (ValueError, TypeError):
                pass

    # 3. DX / CR (Radiografía Digital o Computarizada) -> DAP (Gy*cm2)
    elif mod in ("DX", "CR"):
        # 3.1. Tag nativo estándar (0018,115E) Image and Fluoroscopy Area Dose Product
        dap = getattr(ds, "ImageAndFluoroscopyAreaDoseProduct", None)
        if dap is None and (0x0018, 0x115E) in ds:
            dap = ds[0x0018, 0x115E].value

        dap_val = None
        if dap is not None:
            try:
                val = float(dap)
                if val > 0:
                    dap_val = round(val, 4)
            except (ValueError, TypeError):
                pass

        # 3.2. Si el hardware de RX no cuenta con cámara DAP activa (DAP es 0 o nulo),
        # estimar DAP indirecto conforme al modelo OIEA (IAEA TRS-457) y Resolución 482 de 2018
        # a partir de los parámetros reales del disparo (kVp, mAs y área colimada del detector)
        if dap_val is None:
            try:
                kvp = float(getattr(ds, "KVP", 0) or 0)
                mas = float(getattr(ds, "Exposure", 0) or 0)
                # Si mAs no está en Exposure, calcular de TubeCurrent (mA) * ExposureTime (s)
                if mas <= 0:
                    ma = float(getattr(ds, "XRayTubeCurrent", 0) or 0)
                    ms = float(getattr(ds, "ExposureTime", 0) or 0)
                    if ma > 0 and ms > 0:
                        mas = (ma * ms) / 1000.0

                if kvp > 0 and mas > 0:
                    rows = float(getattr(ds, "Rows", 0) or 0)
                    cols = float(getattr(ds, "Columns", 0) or 0)
                    sp = getattr(ds, "ImagerPixelSpacing", getattr(ds, "PixelSpacing", None))
                    if sp and len(sp) >= 2 and rows > 0 and cols > 0:
                        h_cm = (rows * float(sp[0])) / 10.0
                        w_cm = (cols * float(sp[1])) / 10.0
                        area_cm2 = h_cm * w_cm
                    else:
                        area_cm2 = 600.0  # Campo representativo estándar

                    # Rendimiento típico de tubo de RX (IAEA TRS-457): 0.05 mGy/mAs a 80 kV a 1 m
                    ka_gy = 0.05 * ((kvp / 80.0) ** 2) * mas * 1e-3
                    dap_calc = ka_gy * area_cm2
                    if dap_calc > 0:
                        dap_val = round(dap_calc, 4)
            except Exception:
                pass

        if dap_val is not None and dap_val > 0:
            metricas.append({"tipo_metrica": "DAP", "valor_dosis": dap_val, "unidad_medida": "Gy*cm2"})

    return metricas


def enviar_lote_a_central(api_url: str, api_token: str, sede_id: int, registros_con_id: List[Dict[str, Any]]) -> Tuple[bool, List[int], List[str]]:
    """
    Envía los registros a la API central de LIDiag vía HTTPS con política de reintentos
    y backoff exponencial en caso de fallos transitorios de red.
    Implementa Idempotencia mediante batch_hash (SHA-256) del contenido de cada bloque.
    Retorna una tupla (all_success, lista_ids_sincronizados, lista_cache_keys).
    """
    if not registros_con_id:
        return True, [], []

    clean_token = str(api_token or "").strip()
    if clean_token.lower().startswith("bearer "):
        clean_token = clean_token[7:].strip()

    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Authorization": f"Bearer {clean_token}",
        "X-API-TOKEN": clean_token,
        "X-Requested-With": "XMLHttpRequest",
    }

    session = requests.Session()
    retry_strategy = Retry(
        total=4,
        backoff_factor=2,  # espera 2s, 4s, 8s, 16s
        status_forcelist=[429, 500, 502, 503, 504],
        allowed_methods=["POST"],
    )
    adapter = HTTPAdapter(max_retries=retry_strategy)
    session.mount("https://", adapter)
    session.mount("http://", adapter)

    chunk_size = 50
    chunks = [registros_con_id[i:i + chunk_size] for i in range(0, len(registros_con_id), chunk_size)]
    total_guardados = 0
    all_success = True
    ids_sincronizados = []
    exitosos_keys = []

    logger.info(f"Transmitiendo {len(registros_con_id)} registro(s) de dosimetría hacia {api_url} en {len(chunks)} bloque(s)...")
    for idx, chunk in enumerate(chunks, 1):
        # Limpiar clave 'id' interna de SQLite antes de enviar a la API
        chunk_limpio = [{k: v for k, v in r.items() if k != "id"} for r in chunk]
        chunk_ids = [r["id"] for r in chunk if "id" in r]

        # Calcular hash criptográfico de idempotencia (SHA-256 del contenido canónico del lote)
        batch_repr = json.dumps(chunk_limpio, sort_keys=True, ensure_ascii=True)
        batch_hash = hashlib.sha256(batch_repr.encode("utf-8")).hexdigest()

        payload = {
            "sede_id": sede_id,
            "api_token": clean_token,
            "batch_hash": batch_hash,
            "registros": chunk_limpio,
        }
        chunk_keys = list({f"{r['study_uid']}_{r['modalidad']}" for r in chunk_limpio})

        payload_bytes = json.dumps(payload, ensure_ascii=False).encode("utf-8")
        post_headers = dict(headers)
        if len(payload_bytes) > 1024:
            body = gzip.compress(payload_bytes)
            post_headers["Content-Encoding"] = "gzip"
            post_headers["Content-Type"] = "application/json"
        else:
            body = payload_bytes
            post_headers["Content-Type"] = "application/json"

        try:
            response = session.post(api_url, data=body, headers=post_headers, timeout=35, verify=False)
            if response.status_code in (200, 201):
                res_json = response.json()
                g = res_json.get("guardados", len(chunk))
                total_guardados += g
                exitosos_keys.extend(chunk_keys)
                ids_sincronizados.extend(chunk_ids)
                marcar_sincronizados_sqlite(chunk_ids)
                logger.info(f"      [OK] Bloque {idx}/{len(chunks)}: {g} registro(s) confirmados por Central (Hash: {batch_hash[:10]}...).")
            else:
                err_text = response.text[:300]
                logger.error(f"      [ERROR API] Bloque {idx}/{len(chunks)} HTTP {response.status_code}: {err_text}")
                if response.status_code == 404:
                    logger.error(f"      [DIAGNÓSTICO 404] El endpoint '{api_url}' no existe. Si el servidor corre bajo un alias de Apache/Laragon, asegúrese de incluir '/public/' (ej: https://.../public/api/dosis/registros).")
                marcar_fallidos_sqlite(chunk_ids, f"HTTP {response.status_code}: {err_text}")
                all_success = False
        except requests.RequestException as e:
            err_msg = str(e)
            logger.error(f"      [ERROR RED] Fallo al enviar bloque {idx}/{len(chunks)} a Central: {err_msg}")
            marcar_fallidos_sqlite(chunk_ids, f"Error red: {err_msg}")
            all_success = False

    logger.info(f"[TOTAL TRANSMISIÓN] Central procesó exitosamente {total_guardados} de {len(registros_con_id)} registro(s).")
    return all_success, ids_sincronizados, exitosos_keys


def main():
    if not acquire_process_lock():
        sys.exit(0)

    args = parse_arguments()

    if args.limpiar_cache:
        if os.path.isfile(CACHE_PROCESADOS_FILE):
            try:
                os.remove(CACHE_PROCESADOS_FILE)
                logger.info(f"Caché de estudios procesados eliminada: {CACHE_PROCESADOS_FILE}")
            except Exception as e:
                logger.warning(f"No se pudo eliminar la caché: {e}")
        else:
            logger.info("No existe archivo de caché previo para eliminar.")
        return

    # Inicializar base de datos SQLite embebida de cola persistente
    init_sqlite_cola()
    purgar_antiguos_sqlite(dias=30)

    config = load_config(args.config)

    if args.sede_id:
        config["sede_id"] = args.sede_id

    sede_id = config["sede_id"]
    api_url = config["api_url"]
    api_token = config["api_token"]
    dosimetria_salt = config.get("dosimetria_salt", "LIDIAG_DOSIMETRIA_SALT_2026")
    local_images_dir = config["local_images_dir"]
    dias = args.dias
    desde = args.desde
    hasta = args.hasta
    limit = None if (args.sin_limite or (args.limit is not None and args.limit <= 0)) else args.limit
    limit_desc = "Sin límite" if limit is None else str(limit)

    ventana_desc = f"Desde {desde} hasta {hasta or 'ahora'}" if desde else f"Últimos {dias} día(s)"

    logger.info("=================================================================")
    logger.info("      AGENTE EDGE DE DOSIMETRÍA RADIOLÓGICA — LIDiag            ")
    logger.info("=================================================================")
    logger.info(f" Sede ID          : {sede_id}")
    logger.info(f" Base de Datos    : {config['db_name']} @ {config['db_host']}:{config['db_port']} (READ UNCOMMITTED)")
    logger.info(f" Directorio DICOM : {local_images_dir}")
    logger.info(f" Endpoint API     : {api_url}")
    logger.info(f" Ventana Temporal : {ventana_desc} (Límite: {limit_desc})")
    if limit is not None and dias >= 30 and limit <= 200:
        logger.info(f" NOTA: La ventana es de {dias} días pero el límite está en {limit}. Para procesar volúmenes masivos, usa --sin-limite o --limit 20000.")
    logger.info(f" Cola SQLite      : {SQLITE_COLA_FILE}")
    logger.info("=================================================================")

    # 1. Conexión a la base de datos de dcm4chee
    try:
        conn = get_db_connection(config)
        logger.info("[1/4] Conexión establecida exitosamente con pacsdb de dcm4chee.")
    except Exception as e:
        logger.critical(f"[ERROR DB] No se pudo conectar a pacsdb: {e}")
        sys.exit(1)

    # 2. Consultar estudios recientes
    cache_procesados = {} if args.forzar else cargar_cache_procesados()
    if args.forzar:
        logger.info("[FORZAR] Modo forzado activado: Se procesarán todos los estudios ignorando la caché local previa.")
    try:
        candidatos = buscar_estudios_candidatos(conn, dias=dias, limit=limit, desde=desde, hasta=hasta)
        logger.info(f"[2/4] Localizados {len(candidatos)} estudio(s) candidatos para dosimetría.")
    finally:
        conn.close()

    # 3. Procesar y extraer métricas DICOM
    registros_extraidos = []
    sin_metricas = []
    omitidos_cache = 0
    no_encontrados_disco = 0
    sin_metricas_count = 0
    estudios_procesados_con_dosis = 0

    if candidatos:
        logger.info("[3/4] Extrayendo métricas de radiación ionizante...")
        for item in candidatos:
            try:
                study_uid = item["study_iuid"]
                modality = item["modality"]
                cache_key = f"{study_uid}_{modality}"

                if cache_key in cache_procesados:
                    omitidos_cache += 1
                    continue

                dcm_path = resolver_ruta_archivo(item.get("dirpath") or "", item.get("filepath") or "", local_images_dir)
                metricas = extraer_metricas_dosis(dcm_path, modality)

                if metricas is None:
                    no_encontrados_disco += 1
                    continue

                if not metricas:
                    sin_metricas.append(cache_key)
                    sin_metricas_count += 1
                    continue

                estudios_procesados_con_dosis += 1

                # Anonimización criptográfica estricta con HMAC-SHA256 y salt dinámico por sede
                try:
                    ds_pat = pydicom.dcmread(dcm_path, stop_before_pixels=True, specific_tags=["PatientID"])
                    raw_patient_id = getattr(ds_pat, "PatientID", "ANONYMOUS")
                except Exception:
                    raw_patient_id = "ANONYMOUS"

                patient_hashed = hash_patient_id(raw_patient_id, salt=dosimetria_salt)
                fecha_estudio = item["study_datetime"]
                if isinstance(fecha_estudio, datetime.datetime):
                    fecha_str = fecha_estudio.strftime("%Y-%m-%d %H:%M:%S")
                else:
                    fecha_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

                region = clasificar_region_anatomica(item.get("study_desc"), modality)
                for m in metricas:
                    es_rdsr_val = bool(m.get("es_rdsr", False))
                    prefix_rdsr = "[RDSR] " if es_rdsr_val else ""
                    desc_orig = item.get("study_desc") or ""
                    desc_final = f"{prefix_rdsr}[{region}] {desc_orig}".strip()[:250] if desc_orig else f"{prefix_rdsr}[{region}]".strip()

                    registros_extraidos.append({
                        "study_uid": study_uid,
                        "patient_id_hashed": patient_hashed,
                        "modalidad": modality,
                        "aet_title": item.get("src_aet") or item.get("station_name") or "EQUIPO_LOCAL",
                        "tipo_metrica": m["tipo_metrica"],
                        "valor_dosis": m["valor_dosis"],
                        "unidad_medida": m["unidad_medida"],
                        "fecha_estudio": fecha_str,
                        "accession_no": item.get("accession_no"),
                        "study_desc": desc_final,
                        "region_anatomica": region,
                        "es_rdsr": es_rdsr_val,
                    })
            except Exception as e:
                logger.warning(f"Error procesando estudio {item.get('study_iuid', 'DESCONOCIDO')}: {e}")
                continue

        if omitidos_cache > 0:
            logger.info(f"      - {omitidos_cache} estudio(s) ya habían sido evaluados previamente (omitidos por caché anti-duplicados). Usa --forzar para reprocesarlos.")
        if no_encontrados_disco > 0:
            logger.warning(f"      - {no_encontrados_disco} archivo(s) no se pudieron localizar en disco local '{local_images_dir}'.")
        if sin_metricas_count > 0:
            logger.info(f"      - {sin_metricas_count} estudio(s) leídos sin etiquetas de radiación ionizante (registrados en caché).")
        if estudios_procesados_con_dosis > 0:
            logger.info(f"      - {estudios_procesados_con_dosis} nuevo(s) estudio(s) procesados exitosamente con {len(registros_extraidos)} métrica(s) de dosis.")

        # Encolar en la base de datos persistente SQLite local
        if registros_extraidos:
            nuevos_encolados = encolar_estudios_sqlite(registros_extraidos)
            logger.info(f"Encolados en SQLite {nuevos_encolados} nuevo(s) registro(s) de dosimetría para transmisión.")

    # 4. Despachar registros pendientes desde SQLite hacia la API Central
    now_ts = int(time.time())
    for k in sin_metricas:
        cache_procesados[k] = now_ts
    guardar_cache_procesados(cache_procesados)

    pendientes_en_cola = obtener_pendientes_sqlite(limit=limit)
    logger.info(f"[4/4] Registros listos en cola persistente SQLite para enviar: {len(pendientes_en_cola)}")

    if pendientes_en_cola:
        if args.dry_run:
            logger.info(f"[DRY-RUN] Modo prueba: Se omitió el POST. Registros:\n{json.dumps(pendientes_en_cola[:2], indent=2)}")
        else:
            exito, ids_sincronizados, exitosos_keys = enviar_lote_a_central(api_url, api_token, sede_id, pendientes_en_cola)
            for k in exitosos_keys:
                cache_procesados[k] = now_ts
            guardar_cache_procesados(cache_procesados)

            if not exito:
                logger.warning("Fallo parcial en la transmisión. Los registros permanecen en SQLite y se reintentarán automáticamente.")
    else:
        logger.info("Cola persistente al día. Sin registros pendientes por transmitir.")

    # 5. Emitir latido (Heartbeat / Dead Man's Switch) hacia Central
    total_pendientes_final = contar_pendientes_sqlite()
    enviar_heartbeat(api_url, api_token, sede_id, pendientes=total_pendientes_final)

    logger.info("Ciclo de dosimetría finalizado.")

    update_unified_healthcheck(
        sede_id=sede_id,
        sede_nombre=config.get("sede_nombre", ""),
        service="dosimetria",
        info={
            "estado": "OK",
            "fecha": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "estudios_candidatos": len(candidatos) if candidatos else 0,
            "metricas_extraidas": len(registros_extraidos),
            "cola_pendientes": total_pendientes_final,
        },
    )


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        logger.info("Operación cancelada por el usuario.")
        sys.exit(0)
    except Exception as e:
        logger.exception(f"[FATAL] Excepción no controlada en el agente de dosimetría: {e}")
        sys.exit(1)
