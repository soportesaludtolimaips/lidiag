#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
dcm_disk_monitor.py — Monitor de Espacio en Discos DICOM con Bot de Telegram

Herramienta autónoma en Python (solo librería estándar, sin dependencias pip)
diseñada para servidores de imágenes diagnósticas (dcm4chee / PACS / Windows Server).

Modos de ejecución:
1. Bot Residente / Servicio (Daemon):
   python dcm_disk_monitor.py
   python dcm_disk_monitor.py --daemon
   Escucha comandos interactivos por Telegram (/discos, /estado, /info)
   y ejecuta un hilo de fondo que revisa los discos periódicamente.

2. Verificación Única (Para Windows Task Scheduler):
   python dcm_disk_monitor.py --check-once
   Evalúa el espacio, envía alertas si hay discos en riesgo y finaliza.

3. Utilidades:
   python dcm_disk_monitor.py --status           # Imprime estado en consola
   python dcm_disk_monitor.py --test-telegram    # Envía mensaje de prueba a Telegram
   python dcm_disk_monitor.py --get-chat-ids     # Muestra los Chat IDs que han escrito al bot

Autor: LIDiag Medical Systems
Fecha: 2026-10-19
"""

import argparse
import datetime
import json
import os
import re
import shutil
import socket
import sys
import threading
import time
import urllib.error
import urllib.parse
import urllib.request

# Soporte robusto para pythonw.exe (sin consola) y codificación UTF-8
base_script_dir = os.path.dirname(os.path.abspath(__file__))
log_file_path = os.path.join(base_script_dir, "dcm_monitor.log")

if sys.stdout is None or sys.stderr is None:
    try:
        log_file = open(log_file_path, "a", encoding="utf-8", buffering=1)
        sys.stdout = log_file
        sys.stderr = log_file
    except Exception:
        pass
elif sys.platform.startswith("win"):
    try:
        if hasattr(sys.stdout, "reconfigure"):
            sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        if hasattr(sys.stderr, "reconfigure"):
            sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass


# =====================================================================
# GESTIÓN DE CONFIGURACIÓN Y PERSISTENCIA
# =====================================================================

def parse_tolerant_json(content: str) -> dict:
    r"""
    Parsea JSON con tolerancia a rutas Windows con barras invertidas simples (\).
    En Windows, rutas como 'C:\Program Files' o 'D:\img_dcm' causan errores en json.loads.
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


def load_config(config_path: str = "config.json") -> dict:
    """Carga y valida el archivo de configuración JSON."""
    if not os.path.exists(config_path):
        # Buscar en el mismo directorio del script
        base_dir = os.path.dirname(os.path.abspath(__file__))
        alt_path = os.path.join(base_dir, config_path)
        if os.path.exists(alt_path):
            config_path = alt_path
        else:
            print("==================================================================")
            print(f"[ERROR] No se encontró el archivo de configuración: {config_path}")
            print("Copia 'config.json.example' a 'config.json' y configura tus datos.")
            print("==================================================================")
            sys.exit(1)

    with open(config_path, "r", encoding="utf-8") as f:
        content = f.read()

    config = parse_tolerant_json(content)

    # Valores por defecto seguros (soporta variable SEDE o sede_nombre)
    nombre_sede = config.get("SEDE") or config.get("sede_nombre") or "Sede Hospitalaria"
    config["SEDE"] = nombre_sede
    config["sede_nombre"] = nombre_sede
    config.setdefault("servidor_identificador", socket.gethostname())
    config.setdefault("telegram", {})
    config["telegram"].setdefault("bot_token", "")
    config["telegram"].setdefault("chat_ids", [])
    config.setdefault("rutas_monitoreo", [
        {"nombre": "Sistema (C:)", "ruta": "C:\\"},
        {"nombre": "Imágenes DICOM", "ruta": "D:\\img_dcm"}
    ])
    config.setdefault("umbrales", {
        "advertencia_porcentaje_libre": 15.0,
        "advertencia_gb_libres": 50.0,
        "critico_porcentaje_libre": 5.0,
        "critico_gb_libres": 20.0
    })
    config.setdefault("intervalo_revision_minutos", 15)
    config.setdefault("cooldown_alerta_horas", 4)
    config.setdefault("reporte_diario", {"activo": True, "hora": "08:00"})

    return config


def get_state_file_path() -> str:
    """Ruta al archivo donde se persiste el último estado para control de cooldown."""
    base_dir = os.path.dirname(os.path.abspath(__file__))
    return os.path.join(base_dir, "last_alert_state.json")


def load_state() -> dict:
    path = get_state_file_path()
    if os.path.exists(path):
        try:
            with open(path, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            return {}
    return {}


def save_state(state: dict) -> None:
    path = get_state_file_path()
    try:
        with open(path, "w", encoding="utf-8") as f:
            json.dump(state, f, indent=2, ensure_ascii=False)
    except Exception as e:
        print(f"[ADVERTENCIA] No se pudo guardar estado: {e}")


# =====================================================================
# MEDICIÓN Y ANÁLISIS DE DISCOS
# =====================================================================

def format_bytes(bytes_val: int) -> str:
    """Convierte bytes a formato humano legible (GB o TB)."""
    tb = bytes_val / (1024 ** 4)
    if tb >= 1.0:
        return f"{tb:.2f} TB"
    gb = bytes_val / (1024 ** 3)
    return f"{gb:.2f} GB"


def make_progress_bar(pct: float, length: int = 10) -> str:
    """Genera una barra visual de progreso ASCII (ej. [████████░░])."""
    filled = int(round((pct / 100.0) * length))
    filled = max(0, min(length, filled))
    bar = "█" * filled + "░" * (length - filled)
    return f"[{bar}]"


def inspect_disks(config: dict) -> list:
    """
    Inspecciona todas las rutas configuradas y calcula métricas y estado de salud.
    Retorna una lista con la información de cada disco.
    """
    umbrales = config.get("umbrales", {})
    warn_pct = float(umbrales.get("advertencia_porcentaje_libre", 15.0))
    warn_gb = float(umbrales.get("advertencia_gb_libres", 50.0))
    crit_pct = float(umbrales.get("critico_porcentaje_libre", 5.0))
    crit_gb = float(umbrales.get("critico_gb_libres", 20.0))

    resultados = []

    for item in config.get("rutas_monitoreo", []):
        nombre = item.get("nombre", "Disco")
        ruta = item.get("ruta", "")

        # Normalizar para Windows
        ruta_check = ruta
        if not os.path.exists(ruta_check):
            # Intentar solo con la letra de unidad si la subcarpeta no existe
            drive, _ = os.path.splitdrive(ruta)
            if drive and os.path.exists(drive + "\\"):
                ruta_check = drive + "\\"

        if not os.path.exists(ruta_check):
            resultados.append({
                "nombre": nombre,
                "ruta": ruta,
                "existe": False,
                "estado": "ERROR_NO_EXISTE",
                "icono": "⚪",
                "mensaje": "Ruta o unidad no accesible en el servidor"
            })
            continue

        try:
            total, used, free = shutil.disk_usage(ruta_check)
            total_gb = total / (1024 ** 3)
            used_gb = used / (1024 ** 3)
            free_gb = free / (1024 ** 3)
            used_pct = (used / total) * 100.0 if total > 0 else 0.0
            free_pct = (free / total) * 100.0 if total > 0 else 0.0

            # Determinar estado
            if free_pct <= crit_pct or free_gb <= crit_gb:
                estado = "CRITICO"
                icono = "🔴"
            elif free_pct <= warn_pct or free_gb <= warn_gb:
                estado = "ADVERTENCIA"
                icono = "🟡"
            else:
                estado = "OK"
                icono = "🟢"

            resultados.append({
                "nombre": nombre,
                "ruta": ruta,
                "ruta_medida": ruta_check,
                "existe": True,
                "total_bytes": total,
                "used_bytes": used,
                "free_bytes": free,
                "total_formato": format_bytes(total),
                "used_formato": format_bytes(used),
                "free_formato": format_bytes(free),
                "total_gb": round(total_gb, 2),
                "used_gb": round(used_gb, 2),
                "free_gb": round(free_gb, 2),
                "used_pct": round(used_pct, 1),
                "free_pct": round(free_pct, 1),
                "barra": make_progress_bar(used_pct, length=10),
                "estado": estado,
                "icono": icono
            })
        except Exception as e:
            resultados.append({
                "nombre": nombre,
                "ruta": ruta,
                "existe": False,
                "estado": "ERROR",
                "icono": "❌",
                "mensaje": str(e)
            })

    return resultados


# =====================================================================
# CLIENTE TELEGRAM (SOLO URLLIB ESTÁNDAR)
# =====================================================================

class TelegramClient:
    """Cliente HTTP ligero para la API de Bots de Telegram usando urllib."""

    def __init__(self, bot_token: str):
        self.bot_token = bot_token.strip()
        self.base_url = f"https://api.telegram.org/bot{self.bot_token}"

    def is_configured(self) -> bool:
        return bool(self.bot_token and "PEGA_AQUI" not in self.bot_token)

    def _post(self, method: str, data: dict, timeout: int = 15) -> dict:
        if not self.is_configured():
            return {"ok": False, "description": "Token de Telegram no configurado."}

        url = f"{self.base_url}/{method}"
        payload = json.dumps(data).encode("utf-8")
        req = urllib.request.Request(
            url,
            data=payload,
            headers={"Content-Type": "application/json; charset=utf-8"}
        )

        try:
            with urllib.request.urlopen(req, timeout=timeout) as response:
                body = response.read().decode("utf-8")
                return json.loads(body)
        except urllib.error.HTTPError as e:
            error_body = e.read().decode("utf-8")
            try:
                return json.loads(error_body)
            except Exception:
                return {"ok": False, "description": f"HTTP {e.code}: {e.reason}"}
        except Exception as e:
            return {"ok": False, "description": str(e)}

    def send_message(self, chat_id: str, text: str, parse_mode: str = "HTML") -> dict:
        """Envía un mensaje a un chat o grupo de Telegram."""
        data = {
            "chat_id": str(chat_id).strip(),
            "text": text,
            "parse_mode": parse_mode,
            "disable_web_page_preview": True
        }
        res = self._post("sendMessage", data)
        # Fallback a texto plano si falla por parse_mode HTML
        if not res.get("ok") and "can't parse entities" in res.get("description", "").lower():
            data["parse_mode"] = ""
            # Quitar tags HTML
            data["text"] = re.sub(r"<[^>]+>", "", text)
            res = self._post("sendMessage", data)
        return res

    def broadcast(self, chat_ids: list, text: str, parse_mode: str = "HTML") -> list:
        """Envía el mismo mensaje a múltiples destinatarios."""
        respuestas = []
        for cid in chat_ids:
            if not cid or "PEGA_AQUI" in str(cid):
                continue
            r = self.send_message(cid, text, parse_mode=parse_mode)
            respuestas.append({"chat_id": cid, "resultado": r})
        return respuestas

    def get_updates(self, offset: int = 0, timeout: int = 30) -> dict:
        """Consulta actualizaciones (mensajes entrantes) usando Long-Polling."""
        data = {
            "offset": offset,
            "timeout": timeout,
            "allowed_updates": ["message"]
        }
        return self._post("getUpdates", data, timeout=timeout + 10)

    def get_me(self) -> dict:
        """Verifica la validez del token obteniendo la información del bot."""
        return self._post("getMe", {}, timeout=10)


# =====================================================================
# FORMATEADORES DE MENSAJES PARA TELEGRAM
# =====================================================================

def build_status_message(config: dict, discos: list) -> str:
    """Genera el mensaje completo de estado para el comando /discos o /estado."""
    sede = config.get("sede_nombre", "Sede")
    servidor = config.get("servidor_identificador", socket.gethostname())
    now_str = datetime.datetime.now().strftime("%d/%m/%Y %H:%M:%S")

    lineas = [
        f"📊 <b>ESTADO DE DISCOS Y REPOSITORIOS</b>",
        f"🏥 <b>Sede:</b> {sede}",
        f"🖥️ <b>Servidor:</b> <code>{servidor}</code>",
        f"🕒 <b>Fecha:</b> {now_str}",
        "────────────────────────"
    ]

    for d in discos:
        nombre = d.get("nombre", "Disco")
        ruta = d.get("ruta", "")
        icono = d.get("icono", "⚪")
        estado = d.get("estado", "DESCONOCIDO")

        if not d.get("existe", False):
            lineas.append(f"{icono} <b>{nombre}</b> (<code>{ruta}</code>)")
            lineas.append(f"   ⚠️ <i>{d.get('mensaje', 'Inaccesible')}</i>\n")
            continue

        used_pct = d.get("used_pct", 0)
        free_pct = d.get("free_pct", 0)
        free_fmt = d.get("free_formato", "0 GB")
        total_fmt = d.get("total_formato", "0 GB")
        used_fmt = d.get("used_formato", "0 GB")
        barra = d.get("barra", "")

        lineas.append(f"{icono} <b>{nombre}</b>")
        lineas.append(f"   📁 <code>{ruta}</code>")
        lineas.append(f"   {barra} <b>{used_pct}% Usado</b>")
        lineas.append(f"   💾 <b>Libre:</b> {free_fmt} ({free_pct}%) de {total_fmt}")
        lineas.append(f"   📊 <b>Usado:</b> {used_fmt} | Estado: <b>{estado}</b>\n")

    lineas.append("────────────────────────")
    lineas.append("ℹ️ <i>Escribe /dd o /disco para refrescar. Ver ayuda con /ayuda.</i>")
    return "\n".join(lineas)


def build_alert_message(config: dict, disco: dict, tipo_alerta: str) -> str:
    """Genera mensaje urgente para alertas automáticas (CRÍTICO o ADVERTENCIA)."""
    sede = config.get("sede_nombre", "Sede")
    servidor = config.get("servidor_identificador", socket.gethostname())
    now_str = datetime.datetime.now().strftime("%d/%m/%Y %H:%M:%S")

    if tipo_alerta == "CRITICO":
        header = "🚨 <b>¡ALERTA CRÍTICA DE ALMACENAMIENTO!</b> 🚨"
        icono = "🔴"
        pie = "⚠️ <b>PELIGRO:</b> El servidor PACS está a punto de quedarse sin espacio. Las modalidades (TAC, RX, RM) podrían comenzar a rechazar el envío de imágenes."
    else:
        header = "⚠️ <b>ADVERTENCIA DE ESPACIO EN DISCO</b> ⚠️"
        icono = "🟡"
        pie = "ℹ️ <i>Recomendación: Planificar depuración de archivos o ampliación de almacenamiento.</i>"

    nombre = disco.get("nombre", "Disco")
    ruta = disco.get("ruta", "")
    free_fmt = disco.get("free_formato", "0 GB")
    free_pct = disco.get("free_pct", 0)
    used_pct = disco.get("used_pct", 0)
    total_fmt = disco.get("total_formato", "0 GB")
    barra = disco.get("barra", "")

    return (
        f"{header}\n\n"
        f"🏥 <b>Sede:</b> {sede}\n"
        f"🖥️ <b>Servidor:</b> <code>{servidor}</code>\n"
        f"📁 <b>Repositorio:</b> <code>{ruta}</code> ({nombre})\n\n"
        f"{icono} <b>Estado:</b> {tipo_alerta}\n"
        f"📊 <b>Uso:</b> {barra} <b>{used_pct}%</b>\n"
        f"💾 <b>Espacio Libre:</b> <b>{free_fmt}</b> ({free_pct}% libre)\n"
        f"📦 <b>Capacidad Total:</b> {total_fmt}\n"
        f"🕒 <b>Hora:</b> {now_str}\n\n"
        f"{pie}"
    )


def build_recovery_message(config: dict, disco: dict) -> str:
    """Genera mensaje de recuperación cuando un disco vuelve a estado normal."""
    sede = config.get("sede_nombre", "Sede")
    now_str = datetime.datetime.now().strftime("%d/%m/%Y %H:%M:%S")
    return (
        f"✅ <b>ESPACIO RECUPERADO EN REPOSITORIO</b>\n\n"
        f"🏥 <b>Sede:</b> {sede}\n"
        f"📁 <b>Ruta:</b> <code>{disco.get('ruta')}</code> ({disco.get('nombre')})\n"
        f"🟢 <b>Estado Actual:</b> NORMAL (OK)\n"
        f"💾 <b>Espacio Libre:</b> {disco.get('free_formato')} ({disco.get('free_pct')}%)\n"
        f"🕒 <b>Hora:</b> {now_str}\n\n"
        f"<i>El repositorio vuelve a operar dentro de los parámetros seguros.</i>"
    )


# =====================================================================
# EVALUACIÓN Y NOTIFICACIÓN PERIÓDICA (BACKGROUND WORKER)
# =====================================================================

def evaluate_and_notify(config: dict, telegram: TelegramClient, es_reporte_diario: bool = False) -> None:
    """
    Evalúa el estado de los discos y envía alertas si es necesario respetando
    el cooldown configurado para evitar saturación de mensajes.
    """
    chat_ids = config.get("telegram", {}).get("chat_ids", [])
    if not chat_ids or not telegram.is_configured():
        return

    discos = inspect_disks(config)
    state = load_state()
    ahora_ts = time.time()
    cooldown_seg = float(config.get("cooldown_alerta_horas", 4)) * 3600.0

    state.setdefault("discos", {})
    state.setdefault("ultimo_reporte_diario", "")

    # Si es reporte diario matutino
    hoy_str = datetime.date.today().isoformat()
    if es_reporte_diario and state.get("ultimo_reporte_diario") != hoy_str:
        mensaje_diario = (
            f"☀️ <b>REPORTE DIARIO MATUTINO</b>\n"
            + build_status_message(config, discos)
        )
        telegram.broadcast(chat_ids, mensaje_diario)
        state["ultimo_reporte_diario"] = hoy_str
        save_state(state)

    # Evaluar alertas por cada disco
    for d in discos:
        ruta = d.get("ruta", "desconocida")
        estado_actual = d.get("estado", "OK")
        disco_state = state["discos"].setdefault(ruta, {
            "ultimo_estado": "OK",
            "ultimo_alerta_ts": 0
        })

        ultimo_estado = disco_state.get("ultimo_estado", "OK")
        ultimo_alerta_ts = disco_state.get("ultimo_alerta_ts", 0)

        # Caso 1: Recuperación a OK
        if estado_actual == "OK" and ultimo_estado in ["CRITICO", "ADVERTENCIA"]:
            msg_recuperado = build_recovery_message(config, d)
            telegram.broadcast(chat_ids, msg_recuperado)
            disco_state["ultimo_estado"] = "OK"
            disco_state["ultimo_alerta_ts"] = ahora_ts
            save_state(state)
            continue

        # Caso 2: Entró o permanece en CRÍTICO o ADVERTENCIA
        if estado_actual in ["CRITICO", "ADVERTENCIA"]:
            debe_alertar = False

            # Transición de estado (ej: OK -> ADVERTENCIA, o ADVERTENCIA -> CRITICO)
            if estado_actual != ultimo_estado:
                debe_alertar = True
            # Mismo estado pero ya pasó el tiempo de cooldown
            elif (ahora_ts - ultimo_alerta_ts) >= cooldown_seg:
                debe_alertar = True

            if debe_alertar:
                msg_alerta = build_alert_message(config, d, estado_actual)
                telegram.broadcast(chat_ids, msg_alerta)
                disco_state["ultimo_estado"] = estado_actual
                disco_state["ultimo_alerta_ts"] = ahora_ts
                save_state(state)


def periodic_monitor_loop(config: dict, telegram: TelegramClient, stop_event: threading.Event) -> None:
    """Hilo de fondo que verifica los discos cada X minutos."""
    intervalo_min = max(1, int(config.get("intervalo_revision_minutos", 15)))
    intervalo_seg = intervalo_min * 60

    print(f"[MONITOR] Hilo de revisión periódica activo. Intervalo: cada {intervalo_min} minutos.")

    # Ejecutar primera verificación a los 5 segundos de iniciar
    time.sleep(5)
    while not stop_event.is_set():
        try:
            # Comprobar si coincide con la hora de reporte diario
            rep_cfg = config.get("reporte_diario", {})
            es_hora_reporte = False
            if rep_cfg.get("activo", True):
                hora_objetivo = rep_cfg.get("hora", "08:00")
                hora_actual = datetime.datetime.now().strftime("%H:%M")
                if hora_actual == hora_objetivo:
                    es_hora_reporte = True

            evaluate_and_notify(config, telegram, es_reporte_diario=es_hora_reporte)
        except Exception as e:
            print(f"[ERROR MONITOR] Fallo en evaluación periódica: {e}")

        # Dormir en bloques de 1 segundo para permitir salida limpia
        for _ in range(intervalo_seg):
            if stop_event.is_set():
                break
            time.sleep(1)


# =====================================================================
# POLLING DE COMANDOS ENTRANTE DE TELEGRAM
# =====================================================================

def handle_telegram_command(command: str, chat_id: str, config: dict, telegram: TelegramClient) -> None:
    """Procesa y responde a los comandos enviados por los usuarios."""
    cmd = command.strip().lower()

    # Quitar mención al bot si viene en grupos (ej: /discos@MiBot -> /discos)
    if "@" in cmd:
        cmd = cmd.split("@")[0]

    if cmd in ["/start", "/help", "/ayuda"]:
        sede = config.get("SEDE") or config.get("sede_nombre", "Sede")
        servidor = config.get("servidor_identificador", socket.gethostname())
        texto = (
            f"👋 <b>¡Hola! Bot Asistencial y de Telemetría — Salud Tolima</b>\n\n"
            f"🏥 <b>Sede:</b> {sede}\n"
            f"🖥️ <b>Servidor:</b> <code>{servidor}</code>\n\n"
            f"💾 <b>COMANDOS DE DISCOS DUROS (PACS):</b>\n"
            f"🔹 <code>/dd</code> o <code>/disco</code> — Muestra el espacio libre y uso de repositorios.\n"
            f"🔹 <code>/dd_alertas</code> o <code>/disco_alertas</code> — Umbrales configurados y estado de riesgo.\n"
            f"🔹 <code>/dd_info</code> o <code>/disco_info</code> — Datos técnicos de red y hardware del servidor.\n\n"
            f"📈 <b>COMANDOS ASISTENCIALES (En preparación):</b>\n"
            f"🔹 <i>Estudios tomados, citas agendadas y lecturas pendientes.</i>\n\n"
            f"<i>Este bot te notificará automáticamente si un repositorio entra en estado crítico o de advertencia.</i>"
        )
        telegram.send_message(chat_id, texto)

    elif cmd in ["/dd", "/disco", "/discos", "/dd_estado", "/disco_estado", "/estado"]:
        discos = inspect_disks(config)
        texto = build_status_message(config, discos)
        telegram.send_message(chat_id, texto)

    elif cmd in ["/dd_alertas", "/disco_alertas", "/dd_alerta", "/disco_alerta", "/alertas"]:
        umbrales = config.get("umbrales", {})
        warn_pct = umbrales.get("advertencia_porcentaje_libre", 15.0)
        warn_gb = umbrales.get("advertencia_gb_libres", 50.0)
        crit_pct = umbrales.get("critico_porcentaje_libre", 5.0)
        crit_gb = umbrales.get("critico_gb_libres", 20.0)
        cooldown = config.get("cooldown_alerta_horas", 4)
        intervalo = config.get("intervalo_revision_minutos", 15)

        texto = (
            f"⚙️ <b>CONFIGURACIÓN DE ALERTAS DE DISCOS (PACS)</b>\n\n"
            f"🟡 <b>Advertencia:</b> Menor a {warn_pct}% libre o menos de {warn_gb} GB\n"
            f"🔴 <b>Crítico:</b> Menor a {crit_pct}% libre o menos de {crit_gb} GB\n\n"
            f"⏱️ <b>Frecuencia de escaneo:</b> Cada {intervalo} minutos\n"
            f"⏳ <b>Silenciador (Anti-spam):</b> Alerta repetida cada {cooldown} horas\n"
            f"💡 <i>Consulta el estado en vivo con /dd o /disco</i>"
        )
        telegram.send_message(chat_id, texto)

    elif cmd in ["/dd_info", "/disco_info", "/info"]:
        sede = config.get("SEDE") or config.get("sede_nombre", "Sede")
        servidor = config.get("servidor_identificador", socket.gethostname())
        now_str = datetime.datetime.now().strftime("%d/%m/%Y %H:%M:%S")
        try:
            ip_local = socket.gethostbyname(socket.gethostname())
        except Exception:
            ip_local = "127.0.0.1"

        texto = (
            f"🖥️ <b>INFORMACIÓN DEL SERVIDOR PACS</b>\n\n"
            f"🏥 <b>Sede:</b> {sede}\n"
            f"🏷️ <b>Host:</b> <code>{servidor}</code>\n"
            f"🌐 <b>IP Local:</b> <code>{ip_local}</code>\n"
            f"🐍 <b>Python:</b> {sys.version.split()[0]}\n"
            f"🕒 <b>Hora Servidor:</b> {now_str}\n"
        )
        telegram.send_message(chat_id, texto)


def run_telegram_polling(config: dict, telegram: TelegramClient, stop_event: threading.Event) -> None:
    """Bucle principal de Long-Polling para recibir comandos de Telegram."""
    print("[BOT] Iniciando escucha de mensajes de Telegram (Long-Polling)...")
    offset = 0

    while not stop_event.is_set():
        try:
            updates = telegram.get_updates(offset=offset, timeout=20)
            if not updates.get("ok"):
                time.sleep(3)
                continue

            for item in updates.get("result", []):
                update_id = item.get("update_id", 0)
                offset = max(offset, update_id + 1)

                message = item.get("message", {})
                chat = message.get("chat", {})
                chat_id = str(chat.get("id", ""))
                text = message.get("text", "").strip()

                if text.startswith("/"):
                    print(f"[COMANDO] Recibido '{text}' de chat ID {chat_id}")
                    handle_telegram_command(text, chat_id, config, telegram)

        except Exception as e:
            if not stop_event.is_set():
                print(f"[AVISO BOT] Error temporal en polling: {e}")
                time.sleep(5)


# =====================================================================
# ACCIONES CLI Y PUNTO DE ENTRADA
# =====================================================================

def cmd_status(config: dict) -> None:
    """Imprime el estado de los discos en la consola."""
    discos = inspect_disks(config)
    sede = config.get("sede_nombre", "Sede")
    servidor = config.get("servidor_identificador", socket.gethostname())

    print("==================================================================")
    print(f"ESTADO DE DISCOS — {sede} ({servidor})")
    print(f"Fecha: {datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("==================================================================")
    for d in discos:
        if not d.get("existe"):
            print(f"[{d.get('estado')}] {d.get('nombre')} ({d.get('ruta')}): {d.get('mensaje')}")
            continue
        print(f"[{d.get('estado')}] {d.get('nombre')} ({d.get('ruta')}):")
        print(f"  Capacidad: {d.get('total_formato')}")
        print(f"  Usado:     {d.get('used_formato')} ({d.get('used_pct')}%) {d.get('barra')}")
        print(f"  Libre:     {d.get('free_formato')} ({d.get('free_pct')}%)")
        print("------------------------------------------------------------------")


def cmd_test_telegram(config: dict) -> None:
    """Envía un mensaje de prueba a todos los chat IDs configurados."""
    token = config.get("telegram", {}).get("bot_token", "")
    chat_ids = config.get("telegram", {}).get("chat_ids", [])
    sede = config.get("sede_nombre", "Sede")

    telegram = TelegramClient(token)
    if not telegram.is_configured():
        print("[ERROR] El bot_token no está configurado en config.json.")
        sys.exit(1)

    print(f"Verificando bot con Telegram...")
    me = telegram.get_me()
    if not me.get("ok"):
        print(f"[ERROR] Token inválido o problema de red: {me.get('description')}")
        sys.exit(1)

    bot_user = me.get("result", {}).get("username", "Desconocido")
    print(f"[OK] Conectado exitosamente como @{bot_user}")

    if not chat_ids or "PEGA_AQUI" in str(chat_ids[0]):
        print("[ADVERTENCIA] No hay chat_ids configurados en config.json.")
        print("Para obtener tu chat_id:")
        print("1. Abre Telegram y escribe cualquier mensaje a tu bot.")
        print("2. Ejecuta: python dcm_disk_monitor.py --get-chat-ids")
        sys.exit(1)

    texto = (
        f"🔔 <b>PRUEBA DE CONEXIÓN EXITOSA</b>\n\n"
        f"🏥 <b>Sede:</b> {sede}\n"
        f"🤖 <b>Bot:</b> @{bot_user}\n"
        f"🕒 <b>Fecha:</b> {datetime.datetime.now().strftime('%d/%m/%Y %H:%M:%S')}\n\n"
        f"<i>El sistema de alertas de discos DICOM está listo para operar.</i>"
    )

    print(f"Enviando mensaje de prueba a {len(chat_ids)} destinatario(s)...")
    respuestas = telegram.broadcast(chat_ids, texto)
    for r in respuestas:
        cid = r.get("chat_id")
        ok = r.get("resultado", {}).get("ok", False)
        if ok:
            print(f"  [OK] Mensaje entregado a Chat ID: {cid}")
        else:
            desc = r.get("resultado", {}).get("description", "Error desconocido")
            print(f"  [FALLO] No se pudo entregar a {cid}: {desc}")


def cmd_get_chat_ids(config: dict) -> None:
    """Consulta la API de Telegram para mostrar quiénes le han escrito al bot."""
    token = config.get("telegram", {}).get("bot_token", "")
    telegram = TelegramClient(token)
    if not telegram.is_configured():
        print("[ERROR] Configura primero tu bot_token en config.json.")
        sys.exit(1)

    print("Consultando mensajes recientes en el bot...")
    res = telegram.get_updates(offset=0, timeout=5)
    if not res.get("ok"):
        print(f"[ERROR] No se pudo consultar Telegram: {res.get('description')}")
        sys.exit(1)

    updates = res.get("result", [])
    if not updates:
        print("------------------------------------------------------------------")
        print("No se encontraron mensajes recientes.")
        print("Para que aparezca tu Chat ID:")
        print("1. Abre Telegram y busca tu bot.")
        print("2. Presiona 'INICIAR' o escríbele cualquier mensaje (ej. 'hola').")
        print("3. Vuelve a ejecutar este comando: python dcm_disk_monitor.py --get-chat-ids")
        print("------------------------------------------------------------------")
        return

    encontrados = {}
    for item in updates:
        msg = item.get("message", {})
        chat = msg.get("chat", {})
        cid = chat.get("id")
        tipo = chat.get("type", "privado")
        nombre = chat.get("first_name", "") or chat.get("title", "")
        username = chat.get("username", "")

        if cid:
            encontrados[cid] = {
                "tipo": tipo,
                "nombre": nombre,
                "username": f"@{username}" if username else "(Sin @username)"
            }

    print("==================================================================")
    print("CHAT IDs ENCONTRADOS (Copia el ID a tu config.json):")
    print("==================================================================")
    for cid, info in encontrados.items():
        print(f"ID: {cid} | Tipo: {info['tipo']} | Nombre: {info['nombre']} | Usuario: {info['username']}")
    print("==================================================================")


def main():
    parser = argparse.ArgumentParser(description="Monitor de Espacio en Discos DICOM con Bot de Telegram")
    parser.add_argument("--config", "-c", default="config.json", help="Ruta al archivo config.json")
    parser.add_argument("--daemon", "-d", action="store_true", help="Ejecutar como servicio residente (Bot + Monitoreo)")
    parser.add_argument("--check-once", action="store_true", help="Ejecutar una sola revisión y salir (para Task Scheduler)")
    parser.add_argument("--status", action="store_true", help="Mostrar estado de discos en consola")
    parser.add_argument("--test-telegram", action="store_true", help="Enviar mensaje de prueba a Telegram")
    parser.add_argument("--get-chat-ids", action="store_true", help="Descubrir Chat IDs de usuarios que escribieron al bot")

    args = parser.parse_args()
    config = load_config(args.config)

    if args.status:
        cmd_status(config)
        return

    if args.test_telegram:
        cmd_test_telegram(config)
        return

    if args.get_chat_ids:
        cmd_get_chat_ids(config)
        return

    token = config.get("telegram", {}).get("bot_token", "")
    telegram = TelegramClient(token)

    # Modo 1: Verificación única (Task Scheduler)
    if args.check_once:
        print(f"[{datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Ejecutando verificación de discos única...")
        evaluate_and_notify(config, telegram, es_reporte_diario=False)
        print("Verificación completada.")
        return

    # Modo 2: Servicio Residente (Daemon con Polling + Timer)
    print("==================================================================")
    print("  DCM DISK MONITOR — SERVICIO INICIADO")
    print(f"  Sede:     {config.get('sede_nombre')}")
    print(f"  Servidor: {config.get('servidor_identificador')}")
    print("==================================================================")

    if not telegram.is_configured():
        print("[ADVERTENCIA] Bot de Telegram no configurado o token de ejemplo.")
        print("El monitor evaluará los discos localmente, pero no podrá enviar mensajes a Telegram.")

    stop_event = threading.Event()

    # Hilo 1: Monitoreo periódico
    monitor_thread = threading.Thread(
        target=periodic_monitor_loop,
        args=(config, telegram, stop_event),
        daemon=True
    )
    monitor_thread.start()

    # Hilo 2: Telegram Polling (en el hilo principal o secundario)
    try:
        if telegram.is_configured():
            run_telegram_polling(config, telegram, stop_event)
        else:
            # Si no hay token, solo esperar el hilo de monitoreo
            while not stop_event.is_set():
                time.sleep(1)
    except KeyboardInterrupt:
        print("\n[DETENIENDO] Señal de interrupción recibida. Finalizando servicio...")
        stop_event.set()
        time.sleep(1)
        print("Servicio finalizado correctamente.")


if __name__ == "__main__":
    main()
