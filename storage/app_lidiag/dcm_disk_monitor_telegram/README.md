# 🤖 Monitor de Espacio en Discos DICOM con Bot de Telegram

Sistema autónomo en Python (100% librería estándar, **sin necesidad de `pip install`**) para vigilar el almacenamiento de los servidores de imágenes diagnósticas (PACS / dcm4chee) en las diferentes sedes hospitalarias de LIDiag.

---

## 📋 Características Principales

1. **Alertas Proactivas Automáticas:**
   - 🟡 **Advertencia:** Al bajar del 15% libre o menos de 50 GB.
   - 🔴 **Crítico:** Al bajar del 5% libre o menos de 20 GB (riesgo inminente de rechazo C-STORE en PACS).
   - ✅ **Recuperación:** Notificación automática cuando se libera espacio en el repositorio.
2. **Control Anti-Spam Inteligente (Cooldown):**
   - Si un disco entra en estado crítico, alerta de inmediato y luego solo recuerda periódicamente (por defecto cada 4 horas mientras persista la condición), evitando saturar el chat asistencial.
3. **Bot Interactivo de Telegram (Comandos en Tiempo Real):**
   - Consulta el estado en cualquier momento desde tu teléfono con `/discos` o `/estado`.
   - Recibe tarjetas visuales con barras de progreso, espacio usado, libre y porcentajes exactos.
4. **Multidestinatario (Personal y Grupos):**
   - Notifica simultáneamente a chats individuales (ingenieros/coordinadores) y a **Grupos de Telegram** de soporte asistencial o infraestructura TI.
5. **Reporte Diario Matutino:**
   - Resumen consolidado automático a las 08:00 AM con el estado de todas las unidades monitoreadas.
6. **Cero Dependencias Externas:**
   - Construido 100% sobre módulos nativos de Python 3.7+ (`urllib`, `shutil`, `json`, `threading`, `socket`). Funciona de inmediato en Windows Server sin compilar ni instalar paquetes adicionales.

---

## ⚡ Puesta en Marcha Rápida (En 3 Pasos)

### Paso 1: Crear el Bot en Telegram con @BotFather

1. En Telegram, busca a **`@BotFather`** (cuenta oficial verificada con insignia azul).
2. Envía el comando `/newbot`.
3. Asigna un nombre (ej. `LIDiag Monitor PACS Honda`) y un usuario que termine en `bot` (ej. `lidiag_pacs_honda_bot`).
4. BotFather te entregará un **Token HTTP API** con el formato:
   ```text
   1234567890:ABCdefGHIjklMNOpqrSTUvwxYZ_ejemplo
   ```
5. Guarda este Token.

### Paso 2: Ejecutar el Asistente Interactivo

En la carpeta del monitor, haz doble clic sobre:
```cmd
configurar.bat
```
*(O ejecuta en consola: `python configurar_interactivo.py`)*

El asistente automático:
- 🔍 Detectará las unidades de disco presentes en el servidor (`C:\`, `D:\`, etc.).
- 🏥 Te solicitará el nombre de la sede y el identificador del servidor.
- 🔑 Validará tu Token de Telegram en tiempo real.
- 💬 Detectará automáticamente tu **Chat ID** (o el de un grupo).
- 💾 Creará el archivo `config.json` y enviará un mensaje de prueba a Telegram.

### Paso 3: Poner en Marcha el Monitoreo

Elige una de las formas de ejecución según la necesidad del servidor:
- **Para dejarlo corriendo 24/7 en segundo plano:** Doble clic en `iniciar_segundo_plano.bat`.
- **Para registrarlo como servicio del sistema:** Clic derecho en `instalar_tarea_programada.bat` → *Ejecutar como Administrador*.

---

## 🛠️ Configuración Manual (`config.json`)

Si prefieres configurar sin el asistente interactivo:
1. Copia `config.json.example` y renómbralo como **`config.json`**.
2. Estructura recomendada:

```json
{
  "SEDE": "Honda - Hospital San Juan de Dios de Honda",
  "servidor_identificador": "HONDA-PACS-01",
  "telegram": {
    "bot_token": "TU_TOKEN_DE_TELEGRAM_AQUI",
    "chat_ids": [
      "123456789",
      "-100987654321"
    ]
  },
  "rutas_monitoreo": [
    {
      "nombre": "Disco del Sistema (OS)",
      "ruta": "C:\\"
    },
    {
      "nombre": "Repositorio Imágenes DICOM (PACS)",
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

> [!TIP]
> Para obtener el Chat ID de tu cuenta o grupo manualmente:
> 1. Escribe cualquier mensaje al bot en Telegram.
> 2. Ejecuta `obtener_chat_id.bat` (o `python dcm_disk_monitor.py --get-chat-ids`).
> 3. Copia el número resultante en la lista `chat_ids`.

---

## 📂 Utilidades y Scripts Disponibles

| Archivo `.bat` | Descripción |
| :--- | :--- |
| **`configurar.bat`** | Asistente interactivo paso a paso para configurar sede, discos y Telegram. |
| **`verificar_estado.bat`** | Muestra de inmediato en consola el estado, barras y gigabytes libres de cada disco. |
| **`iniciar_bot.bat`** | Inicia el monitor interactivo en una ventana de consola visible (útil para pruebas). |
| **`iniciar_segundo_plano.bat`** | Inicia el bot como demonio residente silencioso (utiliza `pythonw.exe`). |
| **`detener_bot.bat`** | Detiene de forma segura cualquier proceso del monitor en ejecución. |
| **`instalar_tarea_programada.bat`** | Registra la tarea programada `LIDiag_DCM_Disk_Monitor` en Windows Task Scheduler. |
| **`obtener_chat_id.bat`** | Consulta la API de Telegram y lista los usuarios y grupos que han escrito al bot. |

---

## 📱 Comandos del Bot en Telegram

Puedes enviar estos comandos en cualquier momento desde un chat privado con el bot o en un grupo autorizado:

| Comando | Alias | Descripción |
| :--- | :--- | :--- |
| `/discos` | `/disco`, `/dd`, `/estado` | Muestra tarjeta visual detallada con barras de uso, espacio libre y estado de cada disco del PACS. |
| `/alertas` | `/dd_alertas` | Muestra los umbrales configurados (advertencia/crítico) y el tiempo de cooldown. |
| `/info` | `/dd_info` | Muestra información del servidor: Hostname, IP local, SO y versión de Python. |
| `/ayuda` | `/help`, `/start` | Muestra el menú de bienvenida y los comandos disponibles. |

---

## 🔄 Formas de Ejecución en Servidores de Producción

### Opción A: Bot Residente 24/7 (Recomendado)
- **Ejecución:** Doble clic en `iniciar_segundo_plano.bat`.
- **Comportamiento:**
  - Responde de inmediato a comandos `/discos` o `/estado` enviados desde Telegram.
  - Monitorea los discos cada 15 minutos en segundo plano.
  - Registra eventos y actividad en el archivo `dcm_monitor.log`.
  - Dispara alertas instantáneas ante cruces de umbrales críticos o de advertencia.
  - Emite el reporte diario a las 08:00 AM.
- **Detención:** Doble clic en `detener_bot.bat`.

### Opción B: Tarea Programada de Windows (Task Scheduler)
- **Ejecución:** Clic derecho sobre `instalar_tarea_programada.bat` → **"Ejecutar como Administrador"**.
- **Comportamiento:**
  - Registra una tarea programada llamada `LIDiag_DCM_Disk_Monitor` en Windows.
  - Se ejecuta periódicamente en modo silencioso (`--check-once`).
  - Si el espacio de un disco está en umbral crítico o de advertencia, envía la alerta a Telegram y finaliza de inmediato sin consumir memoria de fondo.

---

## 🩺 Diagnóstico y Solución de Problemas (Troubleshooting)

### 1. El script indica error de conexión o timeout con Telegram
- **Causa:** En algunas redes hospitalarias cerradas con firewall estricto, las conexiones salientes por el puerto 443 a `api.telegram.org` pueden requerir salida IPv4 forzada o proxy corporativo.
- **Verificación:** Ejecuta en la consola:
  ```cmd
  python dcm_disk_monitor.py --test-telegram
  ```
- Si tu red utiliza proxy corporativo, puedes definir las variables estándar de Windows antes de iniciar:
  ```cmd
  set HTTP_PROXY=http://proxy.hospital.local:8080
  set HTTPS_PROXY=http://proxy.hospital.local:8080
  ```

### 2. `obtener_chat_id.bat` no muestra ningún mensaje
- Abre Telegram, busca tu bot por su username y presiona el botón **INICIAR** (o envíale una palabra cualquiera como *"hola"*).
- Si es para un grupo, asegúrate de que el bot haya sido añadido al grupo y que alguien haya escrito un mensaje en el grupo recientemente.
- Vuelve a ejecutar `obtener_chat_id.bat`.

### 3. Revisión de logs en segundo plano
- Cuando el monitor corre en segundo plano con `pythonw.exe`, cualquier error o evento se guarda automáticamente en:
  ```text
  storage\app_lidiag\dcm_disk_monitor_telegram\dcm_monitor.log
  ```

---

## 📦 Despliegue en Nuevas Sedes

Para instalar este monitor en un nuevo servidor PACS:
1. Copia toda la carpeta `dcm_disk_monitor_telegram` al servidor de la sede (ej. en `C:\servicios\dcm_disk_monitor_telegram\`).
2. Abre la carpeta y ejecuta `configurar.bat`.
3. Sigue las instrucciones del asistente en pantalla (2 minutos).
4. Ejecuta `iniciar_segundo_plano.bat` o `instalar_tarea_programada.bat`.
5. ¡Listo! El servidor quedará vigilado y enviará reportes y alertas a Telegram.
