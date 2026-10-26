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
import hashlib
import json
import logging
from logging.handlers import RotatingFileHandler
import os
import re
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
except ImportError:
    print("[ERROR CRÍTICO] La librería 'pydicom' no está instalada. Ejecute: pip install pydicom")
    sys.exit(1)

try:
    import requests
    from requests.adapters import HTTPAdapter
    from urllib3.util.retry import Retry
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


def parse_arguments():
    parser = argparse.ArgumentParser(description="Agente Edge de Dosimetría Radiológica LIDiag")
    parser.add_argument("--config", "-c", help="Ruta al archivo config.json")
    parser.add_argument("--sede-id", type=int, help="ID de la sede (anula valor de config.json)")
    parser.add_argument("--dias", type=int, default=1, help="Ventana de días hacia atrás para consultar (default: 1)")
    parser.add_argument("--desde", type=str, help="Fecha inicio fija (formato YYYY-MM-DD)")
    parser.add_argument("--hasta", type=str, help="Fecha fin fija (formato YYYY-MM-DD)")
    parser.add_argument("--limit", type=int, default=200, help="Límite máximo de estudios a procesar por ciclo (default: 200)")
    parser.add_argument("--dry-run", action="store_true", help="Extrae métricas sin enviarlas a la API central")
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
    }

    return config


def get_db_connection(config: dict):
    """Establece conexión a MySQL pacsdb usando el conector disponible."""
    if not DB_CONNECTOR:
        logger.critical("No se encontró ningún conector MySQL de Python. Instale: pip install mysql-connector-python o pymysql")
        sys.exit(1)

    host = config["db_host"]
    port = int(config["db_port"])
    user = config["db_user"]
    password = config["db_password"]
    database = config["db_name"]

    if DB_CONNECTOR == "mysql.connector":
        conn = mysql.connector.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset="utf8",
            connect_timeout=15,
        )
    else:
        conn = pymysql.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset="utf8",
            connect_timeout=15,
        )

    # Ajustar sql_mode en la sesión para compatibilidad con ONLY_FULL_GROUP_BY en cualquier versión de MySQL
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


def cargar_cache_procesados() -> set:
    if os.path.isfile(CACHE_PROCESADOS_FILE):
        try:
            with open(CACHE_PROCESADOS_FILE, "r", encoding="utf-8") as f:
                data = json.load(f)
                return set(data)
        except Exception as e:
            logger.warning(f"No se pudo leer caché de procesados: {e}")
    return set()


def guardar_cache_procesados(cache: set):
    try:
        # Mantener solo los últimos 5000 estudios en caché
        lista = list(cache)[-5000:]
        with open(CACHE_PROCESADOS_FILE, "w", encoding="utf-8") as f:
            json.dump(lista, f)
    except Exception as e:
        logger.warning(f"No se pudo guardar caché de procesados: {e}")


def hash_patient_id(raw_patient_id: Optional[str]) -> str:
    """Anonimiza el identificador del paciente con SHA-256 (ISO/IEC 27001)."""
    if not raw_patient_id:
        raw_patient_id = "ANONYMOUS"
    salt = "LIDIAG_DOSIMETRIA_SALT_2026"
    return hashlib.sha256(f"{salt}_{str(raw_patient_id).strip()}".encode("utf-8")).hexdigest()


def buscar_estudios_candidatos(conn, dias: int = 1, limit: int = 200, desde: Optional[str] = None, hasta: Optional[str] = None) -> List[Dict[str, Any]]:
    """
    Localiza una instancia representativa por cada estudio reciente de modalidades
    que emiten radiación ionizante (CT, MG, DX, CR).
    """
    if desde:
        fecha_desde = f"{desde} 00:00:00"
        fecha_hasta = f"{hasta} 23:59:59" if hasta else datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        where_fecha = "(st.study_datetime BETWEEN %s AND %s OR st.created_time BETWEEN %s AND %s)"
        params = (fecha_desde, fecha_hasta, fecha_desde, fecha_hasta, limit)
    else:
        fecha_limite = (datetime.datetime.now() - datetime.timedelta(days=dias)).strftime("%Y-%m-%d %H:%M:%S")
        where_fecha = "(st.created_time >= %s OR st.study_datetime >= %s)"
        params = (fecha_limite, fecha_limite, limit)

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
        GROUP BY st.study_iuid, s.modality
        ORDER BY created_time DESC
        LIMIT %s
    """

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
                desc = elem.name.lower()
                if "ctdi" in desc and isinstance(elem.value, (int, float)):
                    ctdivol = elem.value
                    break

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


def enviar_lote_a_central(api_url: str, api_token: str, sede_id: int, registros: List[Dict[str, Any]]) -> bool:
    """
    Envía los registros a la API central de LIDiag vía HTTPS con política de reintentos
    y backoff exponencial en caso de fallos transitorios de red.
    """
    if not registros:
        return True

    headers = {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Authorization": f"Bearer {api_token}",
        "X-Requested-With": "XMLHttpRequest",
    }

    payload = {
        "sede_id": sede_id,
        "registros": registros,
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
    chunks = [registros[i:i + chunk_size] for i in range(0, len(registros), chunk_size)]
    total_guardados = 0
    all_success = True

    logger.info(f"Transmitiendo {len(registros)} registro(s) de dosimetría hacia {api_url} en {len(chunks)} bloque(s)...")
    for idx, chunk in enumerate(chunks, 1):
        payload = {
            "sede_id": sede_id,
            "registros": chunk,
        }
        try:
            response = session.post(api_url, json=payload, headers=headers, timeout=35, verify=False)
            if response.status_code in (200, 201):
                res_json = response.json()
                g = res_json.get("guardados", len(chunk))
                total_guardados += g
                logger.info(f"      [OK] Bloque {idx}/{len(chunks)}: {g} registro(s) procesados correctamente.")
            else:
                logger.error(f"      [ERROR API] Bloque {idx}/{len(chunks)} HTTP {response.status_code}: {response.text[:300]}")
                all_success = False
        except requests.RequestException as e:
            logger.error(f"      [ERROR RED] Fallo al enviar bloque {idx}/{len(chunks)} a Central: {e}")
            all_success = False

    logger.info(f"[TOTAL TRANSMISIÓN] Central procesó exitosamente {total_guardados} de {len(registros)} registro(s).")
    return all_success


def main():
    if not acquire_process_lock():
        sys.exit(0)

    args = parse_arguments()
    config = load_config(args.config)

    if args.sede_id:
        config["sede_id"] = args.sede_id

    sede_id = config["sede_id"]
    api_url = config["api_url"]
    api_token = config["api_token"]
    local_images_dir = config["local_images_dir"]
    dias = args.dias
    desde = args.desde
    hasta = args.hasta
    limit = args.limit

    ventana_desc = f"Desde {desde} hasta {hasta or 'ahora'}" if desde else f"Últimos {dias} día(s)"

    logger.info("=================================================================")
    logger.info("      AGENTE EDGE DE DOSIMETRÍA RADIOLÓGICA — LIDiag            ")
    logger.info("=================================================================")
    logger.info(f" Sede ID          : {sede_id}")
    logger.info(f" Base de Datos    : {config['db_name']} @ {config['db_host']}:{config['db_port']}")
    logger.info(f" Directorio DICOM : {local_images_dir}")
    logger.info(f" Endpoint API     : {api_url}")
    logger.info(f" Ventana Temporal : {ventana_desc} (Límite: {limit})")
    logger.info("=================================================================")

    # 1. Conexión a la base de datos de dcm4chee
    try:
        conn = get_db_connection(config)
        logger.info("[1/4] Conexión establecida exitosamente con pacsdb de dcm4chee.")
    except Exception as e:
        logger.critical(f"[ERROR DB] No se pudo conectar a pacsdb: {e}")
        sys.exit(1)

    # 2. Consultar estudios recientes
    cache_procesados = cargar_cache_procesados()
    try:
        candidatos = buscar_estudios_candidatos(conn, dias=dias, limit=limit, desde=desde, hasta=hasta)
        logger.info(f"[2/4] Localizados {len(candidatos)} estudio(s) candidatos para dosimetría.")
    finally:
        conn.close()

    if not candidatos:
        logger.info("No hay estudios nuevos para procesar en la ventana seleccionada.")
        return

    # 3. Procesar y extraer métricas DICOM
    logger.info("[3/4] Extrayendo métricas de radiación ionizante...")
    registros_para_envio = []
    procesados_nuevos = []

    for item in candidatos:
        study_uid = item["study_iuid"]
        modality = item["modality"]
        cache_key = f"{study_uid}_{modality}"

        if cache_key in cache_procesados:
            continue

        dcm_path = resolver_ruta_archivo(item.get("dirpath") or "", item.get("filepath") or "", local_images_dir)
        metricas = extraer_metricas_dosis(dcm_path, modality)

        if metricas is None:
            # El archivo no se encuentra en disco o está inaccesible por el momento.
            # NO marcar en caché para permitir que sea procesado cuando el archivo exista.
            continue

        if not metricas:
            # Archivo leído exitosamente en disco pero sin tags de radiación ionizante (o dosis = 0)
            procesados_nuevos.append(cache_key)
            continue

        # Anonimización criptográfica del Patient ID
        try:
            ds_pat = pydicom.dcmread(dcm_path, stop_before_pixels=True, specific_tags=["PatientID"])
            raw_patient_id = getattr(ds_pat, "PatientID", "ANONYMOUS")
        except Exception:
            raw_patient_id = "ANONYMOUS"

        patient_hashed = hash_patient_id(raw_patient_id)
        fecha_estudio = item["study_datetime"]
        if isinstance(fecha_estudio, datetime.datetime):
            fecha_str = fecha_estudio.strftime("%Y-%m-%d %H:%M:%S")
        else:
            fecha_str = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")

        for m in metricas:
            registros_para_envio.append({
                "study_uid": study_uid,
                "patient_id_hashed": patient_hashed,
                "modalidad": modality,
                "aet_title": item.get("src_aet") or item.get("station_name") or "EQUIPO_LOCAL",
                "tipo_metrica": m["tipo_metrica"],
                "valor_dosis": m["valor_dosis"],
                "unidad_medida": m["unidad_medida"],
                "fecha_estudio": fecha_str,
                "accession_no": item.get("accession_no"),
                "study_desc": item.get("study_desc"),
            })

        procesados_nuevos.append(cache_key)

    logger.info(f"Total métricas de dosis extraídas: {len(registros_para_envio)}")

    # 4. Enviar a la API Central LIDiag
    if registros_para_envio:
        if args.dry_run:
            logger.info(f"[DRY-RUN] Modo prueba: Se omitió el POST. Registros:\n{json.dumps(registros_para_envio[:2], indent=2)}")
        else:
            exito = enviar_lote_a_central(api_url, api_token, sede_id, registros_para_envio)
            if exito:
                for k in procesados_nuevos:
                    cache_procesados.add(k)
                guardar_cache_procesados(cache_procesados)
            else:
                logger.warning("Fallo en la transmisión a la central. Los registros se reintentarán en el siguiente ciclo.")
    else:
        logger.info("Sin métricas nuevas por transmitir.")
        for k in procesados_nuevos:
            cache_procesados.add(k)
        guardar_cache_procesados(cache_procesados)

    logger.info("Ciclo de extracción de dosimetría finalizado.")

    update_unified_healthcheck(
        sede_id=sede_id,
        sede_nombre=config.get("sede_nombre", ""),
        service="dosimetria",
        info={
            "estado": "OK",
            "fecha": datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
            "estudios_candidatos": len(candidatos),
            "metricas_extraidas": len(registros_para_envio),
        },
    )


if __name__ == "__main__":
    main()
