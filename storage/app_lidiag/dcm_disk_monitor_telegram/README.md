# 🤖 Monitor de Espacio en Discos DICOM con Bot de Telegram

Sistema autónomo en Python (100% librería estándar, **sin necesidad de `pip install`**) para vigilar el almacenamiento de los servidores de imágenes diagnósticas (PACS / dcm4chee) en las diferentes sedes hospitalarias.

---

## 📋 Características Principales

1. **Alertas Proactivas Automáticas:**
   - 🟡 **Advertencia:** Al bajar del 15% libre o menos de 50 GB.
   - 🔴 **Crítico:** Al bajar del 5% libre o menos de 20 GB (riesgo de rechazo C-STORE en PACS).
   - ✅ **Recuperación:** Notificación automática cuando se libera espacio en el repositorio.
2. **Control Anti-Spam (Cooldown):**
   - Si un disco entra en estado crítico, alerta de inmediato y luego solo recuerda cada 4 horas mientras siga lleno, evitando saturar el chat.
3. **Bot Interactivo de Telegram (Comandos):**
   - Escribe `/discos` o `/estado` desde tu celular para recibir el reporte visual con barras de progreso y porcentaje de uso.
   - `/alertas` para ver umbrales y estado de riesgo.
   - `/info` para ver IP, hostname y datos técnicos del servidor.
4. **Multidestinatario:**
   - Puede notificar simultáneamente a tu chat personal y a un **Grupo de Telegram** donde esté el equipo de TI e ingeniería.
5. **Cero Dependencias Externas:**
   - Utiliza exclusivamente módulos nativos de Python 3.7+ (`urllib`, `shutil`, `json`, `threading`). Corre en cualquier Windows Server sin compilar nada.

---

## 🚀 Guía Paso a Paso para Ponerlo en Marcha

### Paso 1: Crear el Bot en Telegram con @BotFather

1. Abre la aplicación de **Telegram** en tu celular o computador.
2. En la barra de búsqueda busca a **`@BotFather`** (el bot oficial con la insignia azul de verificado).
3. Escribe el comando:
   ```text
   /newbot
   ```
4. BotFather te pedirá un nombre amigable para tu bot. Por ejemplo:
   ```text
   Monitor PACS LIDiag
   ```
5. Luego te pedirá un **username** único que termine en `bot`. Por ejemplo:
   ```text
   lidiag_pacs_monitor_bot
   ```
6. **¡Listo!** BotFather te entregará un mensaje con el **Token de la API** (algo similar a: `789654123:AAFnk9s-AbcDeFgHiJkLmNoPqRsTuVwXyZ`). **Copia este Token**.
```
Done! Congratulations on your new bot. You will find it at t.me/salud_tolima_bot. You can now add a description, about section and profile picture for your bot, see /help for a list of commands. By the way, when you've finished creating your cool bot, ping our Bot Support if you want a better username for it. Just make sure the bot is fully operational before you do this.

Use this token to access the HTTP API:
8853202216:AAENnF7kYv9j0pgRDLMgeQuICAnIN44ltKs
Keep your token secure and store it safely, it can be used by anyone to control your bot.
```
---

### Paso 2: Crear el Archivo de Configuración

En la carpeta del monitor:
1. Haz una copia del archivo `config.json.example` y nómbrala **`config.json`**.
2. Ábrelo con un editor de texto (Bloc de Notas, VS Code, etc.).
3. Pega tu Token en la propiedad `bot_token`:
   ```json
   "telegram": {
     "bot_token": "789654123:AAFnk9s-AbcDeFgHiJkLmNoPqRsTuVwXyZ",
     "chat_ids": []
   }
   ```

---

### Paso 3: Obtener tu Chat ID (o el del Grupo)

1. Abre tu bot en Telegram (búscalo por su `@username` o haz clic en el enlace que te dio BotFather).
2. Presiona el botón **INICIAR** (o envíale un mensaje cualquiera, ej: *"hola"*).
   - *(Si vas a usar un Grupo de Telegram: crea el grupo, agrega al bot como miembro y escribe un mensaje en el grupo)*.
3. En la carpeta del monitor, ejecuta el archivo:
   ```cmd
   obtener_chat_id.bat
   ```
   *(O por consola: `python dcm_disk_monitor.py --get-chat-ids`)*
4. El script leerá el mensaje que enviaste y te mostrará en pantalla tu **Chat ID** (ej: `123456789` o un número negativo para grupos: `-100987654321`).
5. Cópialo y pégalo en la lista `chat_ids` dentro de tu `config.json`:
   ```json
   "chat_ids": [
     "123456789",
     "-100987654321"
   ]
   ```

---

### Paso 4: Configurar los Discos de la Sede

Dentro de `config.json`, ajusta el nombre de la sede y las rutas que apliquen a ese servidor:

```json
{
  "SEDE": "Espinal - Hospital San Rafael",
  "servidor_identificador": "SR-PACS-01",
  "rutas_monitoreo": [
    {
      "nombre": "Disco del Sistema (OS)",
      "ruta": "C:\\"
    },
    {
      "nombre": "Repositorio Imágenes DICOM",
      "ruta": "D:\\img_dcm"
    }
  ],
  "umbrales": {
    "advertencia_porcentaje_libre": 15.0,
    "advertencia_gb_libres": 50.0,
    "critico_porcentaje_libre": 5.0,
    "critico_gb_libres": 20.0
  },
  "intervalo_revision_minutos": 15,
  "cooldown_alerta_horas": 4,
  "reporte_diario": {
    "activo": true,
    "hora": "08:00"
  }
}
```

---

### Paso 5: Probar el Envío

Ejecuta en consola:
```cmd
python dcm_disk_monitor.py --test-telegram
```
Te llegará inmediatamente una notificación a Telegram confirmando que el bot está conectado y listo.

---

## ⚙️ Formas de Ejecución en los Servidores

### Opción 1: Bot Residente 24/7 (Recomendado)
- **Con ventana visible:** Ejecuta `iniciar_bot.bat` (o `python dcm_disk_monitor.py --daemon`).
- **En segundo plano (sin ventana CMD):** Ejecuta `iniciar_segundo_plano.bat` (usa nativamente `pythonw.exe`).
- **Para detener el servicio:** Ejecuta `detener_bot.bat`.

**¿Qué hace este modo?**
- Permite que desde Telegram le puedas pedir el estado en cualquier momento con `/discos`.
- Revisa los discos cada 15 minutos en segundo plano y dispara la alerta al instante si detecta peligro.
- Envía un reporte diario matutino automático a las 08:00 AM.

---

### Opción 2: Tarea Programada de Windows (Task Scheduler)
Si prefieres que no haya ninguna ventana abierta de fondo y solo revise los discos puntualmente:
1. Haz clic derecho sobre **`instalar_tarea_programada.bat`** y selecciona **"Ejecutar como Administrador"**.
2. El instalador registrará la tarea `LIDiag_DCM_Disk_Monitor` en el Programador de Tareas de Windows para ejecutarse cada 1 hora en silencio (`--check-once`).

---

## 📱 Comandos del Bot en Telegram

### 💾 Módulo de Discos Duros (PACS)
| Comando | Descripción |
| :--- | :--- |
| `/dd` o `/disco` | Muestra tarjeta detallada con barras de uso, espacio libre y estado de cada disco del PACS |
| `/dd_alertas` | Consulta los umbrales de alerta de disco configurados y el tiempo de cooldown |
| `/dd_info` | Información técnica del servidor PACS (Hostname, IP local, versión de Python) |
| `/ayuda` | Menú interactivo de ayuda general del bot |

*(Nota: Los comandos asistenciales de estadísticas de estudios y citas se integrarán en este mismo bot agrupados por su respectivo prefijo).*

---

## 📦 Distribución a las Sedes

Para llevar esta herramienta a los servidores de cada hospital:
1. Copia la carpeta `dcm_disk_monitor_telegram` en una memoria USB o descárgala en el servidor.
2. Copia `config.json.example` a `config.json` y personaliza el nombre de la sede y las letras de unidad (`C:\`, `D:\img_dcm`, `E:\`, etc.).
3. Ejecuta `iniciar_bot.bat` o `instalar_tarea_programada.bat`.
