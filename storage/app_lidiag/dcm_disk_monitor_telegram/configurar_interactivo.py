#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
configurar_interactivo.py — Asistente de Configuración Rápida para Servidores Hospitalarios

Guía paso a paso en consola para configurar el Monitor de Discos DICOM:
1. Detecta automáticamente las unidades de disco presentes en el servidor (C:\, D:\, etc.).
2. Solicita el nombre de la sede y el token de Telegram entregado por @BotFather.
3. Valida la conexión con Telegram y descubre automáticamente tu Chat ID.
4. Genera el archivo config.json listo para operar y envía un mensaje de confirmación.

Autor: LIDiag Medical Systems
Fecha: 2026-10-19
"""

import datetime
import json
import os
import shutil
import socket
import string
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

# Forzar salida UTF-8 en consola de Windows
if sys.platform.startswith("win"):
    try:
        if hasattr(sys.stdout, "reconfigure"):
            sys.stdout.reconfigure(encoding="utf-8", errors="replace")
        if hasattr(sys.stderr, "reconfigure"):
            sys.stderr.reconfigure(encoding="utf-8", errors="replace")
    except Exception:
        pass


def get_available_drives():
    """Detecta las unidades de disco locales disponibles en Windows."""
    drives = []
    if sys.platform.startswith("win"):
        import ctypes
        bitmask = ctypes.windll.kernel32.GetLogicalDrives()
        for letter in string.ascii_uppercase:
            if bitmask & 1:
                drive_str = f"{letter}:\\"
                try:
                    total, used, free = shutil.disk_usage(drive_str)
                    drives.append({
                        "drive": drive_str,
                        "total_gb": round(total / (1024**3), 1),
                        "free_gb": round(free / (1024**3), 1)
                    })
                except Exception:
                    pass
            bitmask >>= 1
    else:
        drives.append({"drive": "/", "total_gb": 100.0, "free_gb": 50.0})
    return drives


def test_telegram_token(token):
    """Verifica si el token de Telegram es válido."""
    url = f"https://api.telegram.org/bot{token.strip()}/getMe"
    try:
        with urllib.request.urlopen(url, timeout=10) as response:
            data = json.loads(response.read().decode("utf-8"))
            if data.get("ok"):
                return True, data.get("result", {}).get("username", "")
            return False, data.get("description", "Error desconocido")
    except Exception as e:
        return False, str(e)


def get_recent_chat_ids(token):
    """Consulta la API de Telegram para obtener los Chat IDs de mensajes recientes."""
    url = f"https://api.telegram.org/bot{token.strip()}/getUpdates?timeout=5"
    try:
        with urllib.request.urlopen(url, timeout=10) as response:
            data = json.loads(response.read().decode("utf-8"))
            if not data.get("ok"):
                return []
            updates = data.get("result", [])
            encontrados = {}
            for item in updates:
                msg = item.get("message", {})
                chat = msg.get("chat", {})
                cid = chat.get("id")
                if cid:
                    encontrados[str(cid)] = {
                        "id": str(cid),
                        "nombre": chat.get("first_name", "") or chat.get("title", "Chat"),
                        "tipo": chat.get("type", "privado")
                    }
            return list(encontrados.values())
    except Exception:
        return []


def send_welcome_message(token, chat_id, sede_nombre):
    """Envía mensaje de bienvenida al chat confirmado."""
    url = f"https://api.telegram.org/bot{token.strip()}/sendMessage"
    payload = json.dumps({
        "chat_id": chat_id,
        "text": (
            f"🎉 <b>¡CONFIGURACIÓN EXITOSA!</b>\n\n"
            f"🏥 <b>Sede:</b> {sede_nombre}\n"
            f"🖥️ <b>Host:</b> <code>{socket.gethostname()}</code>\n"
            f"🕒 <b>Fecha:</b> {datetime.datetime.now().strftime('%d/%m/%Y %H:%M:%S')}\n\n"
            f"<i>Este chat recibirá las alertas de espacio en disco de este servidor. Escribe /discos para probar los comandos.</i>"
        ),
        "parse_mode": "HTML"
    }).encode("utf-8")

    req = urllib.request.Request(url, data=payload, headers={"Content-Type": "application/json"})
    try:
        with urllib.request.urlopen(req, timeout=10) as res:
            return True
    except Exception:
        return False


def main():
    print("==================================================================")
    print("  ASISTENTE DE CONFIGURACIÓN RÁPIDA — MONITOR PACS TELEGRAM")
    print("==================================================================")
    print("Este asistente te guiará para configurar el monitor en 1 minuto.")
    print("")

    # 1. Nombre de la Sede
    default_sede = "Espinal - Hospital San Rafael"
    sede_input = input(f"1. Nombre de la Sede [{default_sede}]: ").strip()
    sede_nombre = sede_input if sede_input else default_sede

    # 2. Identificador del Servidor
    default_host = socket.gethostname()
    host_input = input(f"2. Identificador o Host del Servidor [{default_host}]: ").strip()
    servidor_id = host_input if host_input else default_host

    # 3. Token de Telegram
    print("\n------------------------------------------------------------------")
    print("3. Token de Telegram (entregado por @BotFather)")
    print("   Ejemplo: 123456789:ABCdefGhIJKlmNoPQRstuVWXyz")
    print("------------------------------------------------------------------")

    bot_username = ""
    token = ""
    while True:
        token = input("Pega tu Bot Token: ").strip()
        if not token:
            print("El token no puede estar vacío. Intenta de nuevo.")
            continue
        print("Verificando token con Telegram...")
        ok, res = test_telegram_token(token)
        if ok:
            bot_username = res
            print(f"[OK] Token válido. Bot conectado: @{bot_username}")
            break
        else:
            print(f"[ERROR] Token inválido: {res}. Intenta de nuevo.")

    # 4. Obtención de Chat ID
    print("\n------------------------------------------------------------------")
    print("4. Vinculación de Chat ID (Tu usuario o Grupo de Telegram)")
    print(f"   Por favor abre Telegram, busca a @{bot_username}")
    print("   y presiona 'INICIAR' o envíale un mensaje (ej. 'hola').")
    print("   (Si es un grupo, agrega al bot al grupo y escribe algo)")
    print("------------------------------------------------------------------")
    input("Presiona ENTER cuando ya hayas enviado el mensaje al bot...")

    chat_ids = []
    intentos = 0
    while intentos < 5:
        chats = get_recent_chat_ids(token)
        if chats:
            print("\nChats detectados que han escrito al bot:")
            for idx, c in enumerate(chats, 1):
                print(f"  [{idx}] ID: {c['id']} | Nombre: {c['nombre']} ({c['tipo']})")

            sel = input("\nIngresa el número del chat que deseas usar (o 'T' para todos) [1]: ").strip()
            if sel.upper() == "T":
                chat_ids = [c["id"] for c in chats]
            else:
                try:
                    idx_sel = int(sel) - 1 if sel else 0
                    chat_ids = [chats[idx_sel]["id"]]
                except Exception:
                    chat_ids = [chats[0]["id"]]
            break
        else:
            print("Aún no detectamos mensajes. Asegúrate de haberle escrito a @", bot_username)
            reintentar = input("¿Deseas volver a comprobar? (S/N) [S]: ").strip().upper()
            if reintentar == "N":
                manual_id = input("Ingresa tu Chat ID manualmente si ya lo conoces: ").strip()
                if manual_id:
                    chat_ids = [manual_id]
                break
            time.sleep(2)
            intentos += 1

    if not chat_ids:
        print("[AVISO] No se pudo vincular un Chat ID automáticamente. Podrás editarlo luego en config.json.")
        chat_ids = ["PEGA_AQUI_TU_CHAT_ID"]

    # 5. Detección de Discos
    print("\n------------------------------------------------------------------")
    print("5. Selección de Unidades de Disco a Monitorear")
    print("------------------------------------------------------------------")
    unidades = get_available_drives()
    rutas_config = []

    for u in unidades:
        letra = u["drive"]
        resp = input(f"¿Deseas monitorear la unidad {letra} (Total: {u['total_gb']} GB, Libre: {u['free_gb']} GB)? (S/N) [S]: ").strip().upper()
        if resp != "N":
            nombre_unidad = "Disco del Sistema (OS)" if letra.startswith("C") else f"Almacenamiento ({letra[:2]})"
            ruta_especifica = letra
            if not letra.startswith("C"):
                sub = input(f"  ¿Tiene una subcarpeta específica para DICOM? (ej. {letra}img_dcm) [dejar vacío para toda la unidad]: ").strip()
                if sub:
                    ruta_especifica = sub
                    nombre_unidad = "Repositorio Imágenes DICOM"

            rutas_config.append({
                "nombre": nombre_unidad,
                "ruta": ruta_especifica
            })

    if not rutas_config:
        rutas_config = [
            {"nombre": "Disco del Sistema", "ruta": "C:\\"},
            {"nombre": "Repositorio Imágenes DICOM", "ruta": "D:\\img_dcm"}
        ]

    # 6. Guardar config.json
    base_dir = os.path.dirname(os.path.abspath(__file__))
    target_config = os.path.join(base_dir, "config.json")

    config_data = {
        "SEDE": sede_nombre,
        "servidor_identificador": servidor_id,
        "telegram": {
            "bot_token": token,
            "chat_ids": chat_ids
        },
        "rutas_monitoreo": rutas_config,
        "umbrales": {
            "advertencia_porcentaje_libre": 15.0,
            "advertencia_gb_libres": 50.0,
            "critico_porcentaje_libre": 5.0,
            "critico_gb_libres": 20.0
        },
        "intervalo_revision_minutos": 15,
        "cooldown_alerta_horas": 4,
        "reporte_diario": {
            "activo": True,
            "hora": "08:00"
        }
    }

    with open(target_config, "w", encoding="utf-8") as f:
        json.dump(config_data, f, indent=2, ensure_ascii=False)

    print("\n==================================================================")
    print(f"[EXITO] Archivo 'config.json' generado en:")
    print(f"        {target_config}")
    print("==================================================================")

    # 7. Enviar mensaje de bienvenida
    if chat_ids and chat_ids[0] != "PEGA_AQUI_TU_CHAT_ID":
        for cid in chat_ids:
            send_welcome_message(token, cid, sede_nombre)
        print("Se ha enviado un mensaje de confirmación a tu Telegram.")

    print("\n¿Cómo deseas ejecutar el monitor ahora?")
    print("  1. Iniciar en pantalla para ver los registros (iniciar_bot.bat)")
    print("  2. Iniciar en segundo plano sin ventana (pythonw)")
    print("  3. Salir y ejecutar más tarde")
    opc = input("Selecciona una opción [1]: ").strip()

    if opc == "2":
        import subprocess
        python_dir = os.path.dirname(sys.executable)
        pythonw_exe = os.path.join(python_dir, "pythonw.exe")
        exe = pythonw_exe if os.path.exists(pythonw_exe) else "pythonw"
        subprocess.Popen([exe, os.path.join(base_dir, "dcm_disk_monitor.py"), "--daemon"])
        print("[OK] Monitor iniciado en segundo plano con pythonw.")
    elif opc != "3":
        os.system(f'python "{os.path.join(base_dir, "dcm_disk_monitor.py")}" --daemon')


if __name__ == "__main__":
    main()
