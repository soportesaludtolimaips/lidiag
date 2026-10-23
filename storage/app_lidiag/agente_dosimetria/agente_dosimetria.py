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
import datetime
import hashlib
import json
import logging
import os
import re
import sys
import time
from typing import Any, Dict, List, Optional, Tuple

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
PARENT_BACKUP_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, "..", "agente_backups_dcm"))
DEFAULT_CONFIG_PATH = os.path.join(PARENT_BACKUP_DIR, "config.json")
LOCAL_CONFIG_PATH = os.path.join(SCRIPT_DIR, "config.json")
LOG_FILE = os.path.join(SCRIPT_DIR, "dosimetria_agent.log")
CACHE_PROCESADOS_FILE = os.path.join(SCRIPT_DIR, "estudios_procesados_cache.json")

# Configurar logging
logging.basicConfig(
    level=logging.INFO,
    format="[%(asctime)s] [%(levelname)s] %(message)s",
    handlers=[
        logging.FileHandler(LOG_FILE, encoding="utf-8"),
        logging.StreamHandler(sys.stdout),
    ],
)
logger = logging.getLogger("LIDiag_DosimetriaAgent")


def parse_arguments():
    parser = argparse.ArgumentParser(description="Agente Edge de Dosimetría Radiológica LIDiag")
    parser.add_argument("--config", "-c", help="Ruta al archivo config.json")
    parser.add_argument("--sede-id", type=int, help="ID de la sede (anula valor de config.json)")
    parser.add_argument("--dias", type=int, default=1, help="Ventana de días hacia atrás para consultar (default: 1)")
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
    candidates = [
        custom_path,
        LOCAL_CONFIG_PATH,
        DEFAULT_CONFIG_PATH,
        os.path.join(SCRIPT_DIR, "config.json.example"),
    ]
    resolved_path = None
    for p in candidates:
        if p and os.path.isfile(p):
            resolved_path = p
            break

    if not resolved_path:
        logger.critical(f"No se encontró archivo de configuración en: {candidates}")
        sys.exit(1)

    logger.info(f"Cargando configuración desde: {resolved_path}")
    with open(resolved_path, "r", encoding="utf-8") as f:
        config = parse_tolerant_json(f.read())

    # Valores por defecto
    config.setdefault("db_host", "127.0.0.1")
    config.setdefault("db_port", 3306)
    config.setdefault("db_name", "pacsdb")
    config.setdefault("db_user", "root")
    config.setdefault("db_password", "")
    config.setdefault("local_images_dir", "D:\\img_dcm")
    config.setdefault("api_url", "https://lidiag.test/api/dosimetria/registros")
    config.setdefault("api_token", "")
    config.setdefault("sede_id", 1)

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
        return mysql.connector.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset="utf8",
            connect_timeout=15,
        )
    else:
        return pymysql.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset="utf8",
            connect_timeout=15,
        )


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


def buscar_estudios_candidatos(conn, dias: int, limit: int) -> List[Dict[str, Any]]:
    """
    Localiza una instancia representativa por cada estudio reciente de modalidades
    que emiten radiación ionizante (CT, MG, DX, CR).
    """
    fecha_limite = (datetime.datetime.now() - datetime.timedelta(days=dias)).strftime("%Y-%m-%d %H:%M:%S")

    query = """
        SELECT 
            st.study_iuid,
            st.study_datetime,
            st.accession_no,
            st.study_desc,
            s.modality,
            s.station_name,
            s.src_aet,
            f.filepath,
            fs.dirpath
        FROM study st
        JOIN series s ON s.study_fk = st.pk
        JOIN instance i ON i.series_fk = s.pk
        JOIN files f ON f.instance_fk = i.pk
        JOIN filesystem fs ON fs.pk = f.filesystem_fk
        WHERE s.modality IN ('CT', 'MG', 'DX', 'CR')
          AND (st.created_time >= %s OR st.study_datetime >= %s)
        GROUP BY st.study_iuid, s.modality
        ORDER BY st.created_time DESC
        LIMIT %s
    """

    cursor = conn.cursor(dictionary=True) if DB_CONNECTOR == "mysql.connector" else conn.cursor(pymysql.cursors.DictCursor)
    try:
        cursor.execute(query, (fecha_limite, fecha_limite, limit))
        filas = cursor.fetchall()
        return filas
    finally:
        cursor.close()


def resolver_ruta_archivo(dirpath_bd: str, filepath_bd: str, local_images_dir: str) -> str:
    """Resuelve la ruta absoluta del archivo DICOM en disco."""
    # En Windows, dirpath_bd puede ser D:\img_dcm o similar
    # Si dirpath_bd existe en el host, se usa; si no, se usa local_images_dir
    ruta_candidata = os.path.join(dirpath_bd, filepath_bd)
    if os.path.isfile(ruta_candidata):
        return ruta_candidata

    ruta_con_override = os.path.join(local_images_dir, filepath_bd)
    if os.path.isfile(ruta_con_override):
        return ruta_con_override

    # Normalizar separadores
    ruta_norm = os.path.normpath(ruta_candidata)
    if os.path.isfile(ruta_norm):
        return ruta_norm

    return ruta_con_override


def extraer_metricas_dosis(dcm_path: str, modalidad: str) -> List[Dict[str, Any]]:
    """
    Lee los tags DICOM de la imagen o RDSR y extrae las métricas numéricas.
    """
    if not os.path.isfile(dcm_path):
        logger.warning(f"Archivo no encontrado en disco: {dcm_path}")
        return []

    try:
        ds = pydicom.dcmread(dcm_path, stop_before_pixels=True, force=True)
    except Exception as e:
        logger.warning(f"Error al leer cabecera DICOM de {dcm_path}: {e}")
        return []

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
        # (0018,115E) Image and Fluoroscopy Area Dose Product
        dap = getattr(ds, "ImageAndFluoroscopyAreaDoseProduct", None)
        if dap is None and (0x0018, 0x115E) in ds:
            dap = ds[0x0018, 0x115E].value

        if dap is not None:
            try:
                val = float(dap)
                if val > 0:
                    metricas.append({"tipo_metrica": "DAP", "valor_dosis": round(val, 4), "unidad_medida": "Gy*cm2"})
            except (ValueError, TypeError):
                pass

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

    logger.info(f"Transmitiendo {len(registros)} registro(s) de dosimetría hacia {api_url}...")
    try:
        response = session.post(api_url, json=payload, headers=headers, timeout=30, verify=False)
        if response.status_code in (200, 201):
            res_json = response.json()
            guardados = res_json.get("guardados", len(registros))
            logger.info(f"[ÉXITO] Central confirmó {guardados} registro(s) procesados correctamente.")
            return True
        else:
            logger.error(f"[ERROR API] Respuesta HTTP {response.status_code}: {response.text[:300]}")
            return False
    except requests.RequestException as e:
        logger.error(f"[ERROR RED] No se pudo conectar con el servidor central LIDiag: {e}")
        return False


def main():
    args = parse_arguments()
    config = load_config(args.config)

    if args.sede_id:
        config["sede_id"] = args.sede_id

    sede_id = config.get("sede_id", 1)
    api_url = config.get("api_url", "https://lidiag.test/api/dosimetria/registros")
    api_token = config.get("api_token", "")
    local_images_dir = config.get("local_images_dir", "D:\\img_dcm")
    dias = args.dias
    limit = args.limit

    logger.info("=================================================================")
    logger.info("      AGENTE EDGE DE DOSIMETRÍA RADIOLÓGICA — LIDiag            ")
    logger.info("=================================================================")
    logger.info(f" Sede ID          : {sede_id}")
    logger.info(f" Base de Datos    : {config['db_name']} @ {config['db_host']}:{config['db_port']}")
    logger.info(f" Directorio DICOM : {local_images_dir}")
    logger.info(f" Endpoint API     : {api_url}")
    logger.info(f" Ventana Temporal : Últimos {dias} día(s)")
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
        candidatos = buscar_estudios_candidatos(conn, dias=dias, limit=limit)
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

        dcm_path = resolver_ruta_archivo(item["dirpath"], item["filepath"], local_images_dir)
        metricas = extraer_metricas_dosis(dcm_path, modality)

        if not metricas:
            # Marcar en caché para evitar reintentar archivos sin tags de dosis
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


if __name__ == "__main__":
    main()
