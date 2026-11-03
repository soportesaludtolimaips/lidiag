# LIDiag — Agente Unificado de Backups PACS DCM y Dosimetría por Sede

Solución autónoma y de grado clínico para servidores hospitalarios de PACS / DCM (dcm4chee).
Combina en un solo paquete y bajo un único archivo de configuración (`config.json`):

1. **Respaldo de Base de Datos MySQL (PACS DB):** Volcado nocturno con `mysqldump`, compresión ZIP de alta velocidad, prueba de integridad CRC32, cálculo de huella criptográfica SHA-256, purga automática por retención local y **transmisión inmediata directa a la NAS QNAP vía SMB (`\\<qnap_ip>\<qnap_share_backups>`) con purga remota automática**.
2. **Sincronización Continua de Imágenes DICOM a QNAP (Modelo Dropbox / Google Drive):** Monitoreo continuo en segundo plano con motor nativo multi-hilo `robocopy` que solo transfiere archivos `.dcm` nuevos hacia el QNAP NAS, con modo backup seguro (nunca borra en el NAS) y registro detallado en vivo del escaneo en `robocopy_scan.log`.
3. **Módulo de Dosimetría y Control de Radiación (Resolución 482 de 2018 / ISO 27001):** Extracción Edge desatendida de dosis ionizantes (`CTDIvol`, `DLP`, `DGM`, `DAP`) directamente de cabeceras DICOM locales, anonimización criptográfica SHA-256 de identificadores de pacientes y reporte centralizado hacia la API de LIDiag.

---

## Archivos del Paquete Unificado

| Archivo | Descripción |
| :--- | :--- |
| `config.json.example` | Plantilla unificada de configuración (sede, QNAP, credenciales MySQL, storage DICOM y API Central). |
| `install.bat` | **Instalador de Entorno:** Autodetecta Python, crea entorno virtual aislado (`venv`) e instala dependencias clínicas. |
| `requirements.txt` | Lista oficial de paquetes requeridos (`pydicom`, `requests`, `mysql-connector-python`, `urllib3`). |
| `backup_dcm_agent.py` | Script que realiza el volcado, compresión ZIP, CRC32 y SHA-256 de la BD. |
| `sync_dcm_images.py` | Script que ejecuta la sincronización continua e incremental de imágenes DICOM hacia el QNAP. |
| `agente_dosimetria.py` | Script que extrae las métricas de radiación ionizante, anonimiza y transmite a LIDiag Central. |
| `ejecutar_backup_db.bat` | Lanzador del respaldo de base de datos MySQL para tareas programadas. |
| `ejecutar_sync_imagenes.bat` | Lanzador del servicio continuo de sincronización de imágenes. |
| `ejecutar_dosimetria.bat` | Lanzador del ciclo horario de extracción de dosimetría radiológica. |
| `instalar_tareas_sede.bat` | **Instalador de 1 Clic:** Registra las 3 tareas en el Programador de Tareas de Windows bajo `SYSTEM`. |
| `restore_dcm_images.py` | Script de **Recuperación ante Desastres**: Restaura imágenes desde la NAS en orden cronológico descendente (año actual primero). |
| `ejecutar_restore_imagenes.bat` | Lanzador interactivo de restauración de emergencia ante reemplazo o reinstalación del servidor PACS. |
| `README.md` | Este manual técnico de instalación y operación. |

---

## Guía Rápida de Despliegue en el Servidor PACS de la Sede

### 1. Preparación del Entorno (1 Clic)
- Asegúrate de tener Python 3.10+ instalado en el servidor.
- Ejecuta **`install.bat`**: Detectará Python, creará el entorno virtual aislado (`venv`) e instalará automáticamente `pydicom`, `requests`, `mysql-connector-python` y `urllib3` desde `requirements.txt`.

### 2. Copiar y Descomprimir
Descomprime el paquete en una ruta fija del servidor del hospital (por ejemplo en `C:\agente_backups_dcm\`).

### 3. Configurar `config.json`

El archivo `config.json` contiene los parámetros de red, base de datos local y credenciales de acceso hacia LIDiag Central. Puedes generarlo y descargarlo automáticamente desde la plataforma web o configurarlo manualmente a partir de la plantilla `config.json.example`.

#### 🔐 Paso a Paso: Generar Token y Descargar `config.json` desde LIDiag Central

Para garantizar la seguridad de la red y evitar tokens desactualizados, LIDiag cuenta con un módulo centralizado de emisión de credenciales Sanctum con rotación automática (1 token activo por sede):

1. **Ingresar al Panel de Seguridad:**
   - Inicia sesión en LIDiag con un usuario administrador.
   - En el menú lateral izquierdo, dirígete a **Administración → Seguridad → Tokens de Sedes** (o navega directamente a `https://<servidor_central>/seguridad-tokens`).

2. **Identificar la Sede Hospitalaria:**
   - En la tabla principal, ubica la fila correspondiente al hospital donde se desplegará el agente (ej. *Espinal - Hospital San Rafael*).
   - Observa el estado actual del token:
     - <span style="color:#28a745">**Activo**</span>: Ya existe una credencial emitida previamente.
     - <span style="color:#dc3545">**Sin Token**</span>: Aún no se ha generado credencial para esta sede.

3. **Emitir o Rotar la Credencial:**
   - Si la sede no tiene credencial, haz clic en el botón verde **`Generar`** (ícono de llave).
   - Si la sede ya tiene credencial y deseas renovarla, haz clic en el botón amarillo **`Rotar`** (ícono de recarga).
   - Aparecerá una ventana de confirmación SweetAlert advirtiendo que, en caso de rotación, el token previo quedará revocado inmediatamente. Haz clic en **Aceptar**.

4. **Obtención del Token y Descarga Directa:**
   - Se abrirá el modal **"Token de Acceso — Sede [Nombre]"**.
   - **Opción A (Recomendada):** Haz clic en el botón verde **`Descargar config.json`**. El sistema descargará automáticamente el archivo `config_sede_{id}.json` con todos los parámetros de red institucionales y el nuevo Bearer Token incrustado.
   - **Opción B (Manual):** Haz clic en **`Copiar Token`** para copiar la cadena del Bearer Token al portapapeles y pegarla manualmente en el campo `"api_token"` de tu `config.json`.
   > [!WARNING]
   > Por estrictos motivos de seguridad criptográfica en Laravel Sanctum (hash irreversible en base de datos), **el valor en texto plano del token no podrá volver a ser visualizado jamás** una vez cerrado este modal. Descárgalo o cópialo antes de presionar *Cerrar*.

5. **Instalación del Archivo en el Servidor Edge del Hospital:**
   - Transfiere el archivo descargado al servidor local del hospital.
   - Renómbralo exactamente a **`config.json`** y colócalo en la raíz de la carpeta del agente:
     ```text
     C:\agente_backups_dcm\config.json
     ```
   - Si se requiere, ajusta las contraseñas locales de la base de datos (`db_password`) y de la NAS QNAP (`qnap_password`).

6. **Verificación de Transmisión en Vivo:**
   - Ejecuta una prueba de dosimetría o backup en la consola del servidor:
     ```powershell
     python agente_dosimetria.py --dias 7 --limit 100
     ```
   - Regresa a `https://<servidor_central>/seguridad-tokens` y verifica que la columna correspondiente (**DB DCM**, **Img Dicom** o **Dosi**) cambie de *Sin datos* a **`OK`** en verde con la hora actual.

---

#### 📄 Estructura del Archivo `config.json`

```json
{
    "sede_id": 1,
    "sede_nombre": "Espinal",
    "qnap_ip": "10.35.47.21",
    "qnap_user": "sede_espinal",
    "qnap_password": "PASSWORD_AQUI",
    "qnap_share_backups": "backups_espinal",
    "qnap_share_imagenes": "imagenes_espinal",

    "db_host": "127.0.0.1",
    "db_port": 3306,
    "db_name": "pacsdb",
    "db_user": "root",
    "db_password": "",
    "mysqldump_path": "C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe",
    "destination_dir": "C:\\backups_dcm",
    "retention_days": 30,

    "local_images_dir": "D:\\img_dcm",
    "sync_interval_seconds": 180,
    "threads": 8,
    "min_free_space_gb": 10,

    "api_url": "https://10.35.47.4/lidiag/public/api/dosimetria/registros",
    "api_token": "TOKEN_BEARER_LIDIAG_AQUI"
}
```

#### 🏥 Catálogo Oficial de Sedes Hospitalarias e Identificadores (`sede_id`)

En la red asistencial de **LIDiag**, cada sede hospitalaria cuenta con un identificador unívoco (`sede_id`) registrado en la tabla `config_sedes` de la base de datos central. Es indispensable configurar el `sede_id` correspondiente para que los respaldos se vinculen correctamente con el panel de telemetría de LIDiag (`/seguridad-backups`) y la dosimetría (Res. 482) sea atribuida adecuadamente:

| `sede_id` | Código | Municipio / Sede | Institución Hospitalaria | `sede_nombre` Sugerido | Recurso QNAP Backups | Recurso QNAP Imágenes |
| :---: | :---: | :--- | :--- | :--- | :--- | :--- |
| **`1`** | `ESP` | Espinal | Hospital San Rafael E.S.E. | `"Espinal"` | `backups-db-espinal` | `img-dicom-espinal` |
| **`2`** | `LAD` | La Dorada | Hospital San Félix E.S.E. | `"La Dorada"` | `backups-db-ladorada` | `img-dicom-ladorada` |
| **`3`** | `CHA` | Chaparral | Hospital San Juan Bautista E.S.E. | `"Chaparral"` | `backups-db-chaparral` | `img-dicom-chaparral` |
| **`4`** | `IBA` | Ibagué | Hospital Federico Lleras Acosta E.S.E. | `"Ibague"` | `backups-db-ibague` | `img-dicom-ibague` |
| **`5`** | `LIB` | Líbano | Hospital Regional Alfonso Jaramillo Salazar E.S.E. | `"Libano"` | `backups-db-libano` | `img-dicom-libano` |
| **`6`** | `HON` | Honda | Hospital San Juan de Dios de Honda E.S.E. | `"Honda"` | `backups-db-honda` | `img-dicom-honda` |
| **`7`** | `ROV` | Rovira | Hospital San Vicente E.S.E. | `"Rovira"` | `backups-db-rovira` | `img-dicom-rovira` |

> [!IMPORTANT]
> - **Coincidencia Exacta:** El valor numérico de `sede_id` debe existir obligatoriamente en `config_sedes`. De lo contrario, la API central de LIDiag rechazará la recepción de backups con error `422 Unprocessable Entity` o `404 Not Found`.
> - **Nomenclatura Automática de Archivos:** El script `backup_dcm_agent.py` generará automáticamente el archivo ZIP con el prefijo estandarizado `backup_dcm_sede_{sede_id}_{slug}_{timestamp}.zip`, lo cual permite que el monitor central de `/seguridad-backups` calcule de forma inmediata el semáforo de salud de cada sede.


### 4. Guía Rápida de Comandos por Agente

Puedes operar cada servicio mediante sus **lanzadores por lotes `.bat`** (1 clic / tareas programadas) o directamente mediante la **consola de comandos** (PowerShell o CMD con Python):

#### ⚡ Matriz Resumen de Agentes y Lanzadores

| Agente / Servicio | Archivo Script | Lanzador `.bat` | Modo Simulación | Frecuencia Sugerida | Tarea de Windows |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **1. Respaldo BD MySQL (`pacsdb`)** | `backup_dcm_agent.py` | `ejecutar_backup_db.bat` | `--dry-run` | Diaria (02:00 AM) | `LIDiag_Backup_DB_DCM` |
| **2. Sincronización Imágenes DICOM** | `sync_dcm_images.py` | `ejecutar_sync_imagenes.bat` | `--dry-run` | Continua (cada 3 min) | `LIDiag_Sync_Imagenes_DCM` |
| **3. Extracción Dosimetría (Res. 482)** | `agente_dosimetria.py` | `ejecutar_dosimetria.bat` | `--dry-run` | Horaria (cada 1h) | `LIDiag_Dosimetria_Edge` |
| **4. Recuperación de Desastres (Restore)** | `restore_dcm_images.py` | `ejecutar_restore_imagenes.bat` | `--dry-run` | Manual interactivo | N/A (Emergencias) |
| **5. Instalador de Automatización** | N/A | `instalar_tareas_sede.bat` | N/A | 1 Sola vez al desplegar | Registra las 3 tareas |

---

## Catálogo Detallado de Agentes, Parámetros y Comandos

A continuación se detalla la función clínica, arquitectura, tabla completa de argumentos CLI, comandos de prueba y diagnóstico para cada uno de los agentes:

### 1. Agente de Respaldo de Base de Datos MySQL (`backup_dcm_agent.py`)

* **Lanzador por lotes:** `.\ejecutar_backup_db.bat` (Admite argumentos adicionales y los transfiere al script).
* **Función:** Volcado consistente de `pacsdb` con `mysqldump`, compresión ZIP en tiempo real, validación de integridad CRC32, cálculo SHA-256, purga por retención local y transmisión directa a la NAS QNAP vía SMB (`\\<qnap_ip>\<share>`).

#### 📋 Tabla de Argumentos de Línea de Comandos (`backup_dcm_agent.py`)

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON. |
| `--dry-run` | `flag` | `False` | **Modo Simulación:** Valida puertos MySQL (3306), QNAP (445/139), espacio en disco y binario `mysqldump` sin alterar datos ni generar volcado. |
| `--sede` | `int` | Desde config | ID numérico de la sede hospitalaria (anula el valor de `config.json`). |
| `--nombre`, `--sede-nombre` | `str` | Desde config | Nombre o slug de la sede (ej. `espinal`, `chaparral`, `ibague`). |
| `--db-host` | `str` | `127.0.0.1` | Host o dirección IP de la base de datos MySQL local. |
| `--db-port` | `int` | `3306` | Puerto de escucha de MySQL. |
| `--db-name` | `str` | `pacsdb` | Nombre de la base de datos PACS a respaldar. |
| `--db-user` | `str` | `root` | Usuario con permisos `SELECT, LOCK TABLES, SHOW VIEW` en MySQL. |
| `--db-password` | `str` | Desde config | Contraseña del usuario MySQL. |
| `--mysqldump-path`, `--mysqldump` | `str` | Autodetectado | Ruta absoluta al ejecutable `mysqldump.exe`. |
| `--destination-dir`, `--dest` | `str` | `C:\backups_dcm` | Carpeta local donde se genera el archivo `.zip`. |
| `--retention-days` | `int` | `30` | Días de retención local antes de purga automática. |
| `--min-free-gb` | `float` | `5.0` | Espacio libre mínimo en disco requerido para operar. |
| `--qnap-ip` | `str` | Desde config | Dirección IP de la NAS QNAP (ej. `10.35.47.21`). |
| `--qnap-share` | `str` | Desde config | Nombre del recurso compartido SMB en QNAP (ej. `backups_espinal`). |
| `--api-url` | `str` | Desde config | URL base de la API Central de LIDiag. |
| `--api-token` | `str` | Desde config | Token Bearer de autenticación Sanctum de la sede. |

#### 💻 Comandos de Ejecución y Pruebas

```powershell
# 1. Ejecución estándar utilizando la configuración de config.json
python backup_dcm_agent.py
# O mediante su lanzador .bat:
.\ejecutar_backup_db.bat

# 2. Modo Simulación / Pre-vuelo (comprueba MySQL, QNAP y disco sin generar archivos)
python backup_dcm_agent.py --dry-run

# 3. Utilizar un archivo de configuración alternativo
python backup_dcm_agent.py --config C:\ruta\otro_config.json

# 4. Sobrescribir temporalmente credenciales de MySQL (ej: puerto o usuario diferente)
python backup_dcm_agent.py --db-host 127.0.0.1 --db-port 3307 --db-user root --db-password "MiPassword"

# 5. Sobrescribir la carpeta destino y los días de retención
python backup_dcm_agent.py --destination-dir D:\mis_backups --retention-days 15

# 6. Forzar ID y nombre de sede específicos desde la línea de comandos
python backup_dcm_agent.py --sede 1 --nombre espinal
```

#### 🔍 Diagnóstico y Monitoreo en Vivo

```powershell
# Probar socket TCP local de MySQL (puerto 3306):
Test-NetConnection -ComputerName 127.0.0.1 -Port 3306

# Probar disponibilidad de puertos SMB en la NAS QNAP (evalúa 139 NetBIOS y 445 SMB):
Test-NetConnection -ComputerName 10.35.47.21 -Port 139
Test-NetConnection -ComputerName 10.35.47.21 -Port 445

# Probar autenticación y montaje manual del recurso SMB en Windows:
net use \\10.35.47.21\backups_espinal /user:sede_espinal PASSWORD_AQUI
# Desmontar recurso tras la prueba:
net use \\10.35.47.21\backups_espinal /delete /y

# Monitorear la bitácora del agente en tiempo real:
Get-Content .\logs\backup_db.log -Wait -Tail 30

# Consultar el estado del último volcado generado (manifiesto JSON):
Get-Content .\backup_status_sede_*.json | ConvertFrom-Json | Format-List
```

---

### 2. Agente de Sincronización Continua de Imágenes DICOM (`sync_dcm_images.py`)

* **Lanzador por lotes:** `.\ejecutar_sync_imagenes.bat` (Servicio persistente en bucle continuo).
* **Función:** Monitoreo y replicación continua de archivos `.dcm` desde el disco local (`D:\img_dcm`) hacia la NAS QNAP vía `robocopy` multi-hilo, bajo modelo seguro que jamás borra en el destino.

#### 📋 Tabla de Argumentos de Línea de Comandos (`sync_dcm_images.py`)

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON. |
| `--once` | `flag` | `False` | **Pasada Única:** Ejecuta un solo ciclo de sincronización de cambios pendientes y finaliza de inmediato. |
| `--dry-run` | `flag` | `False` | **Modo Simulación:** Utiliza el flag `/L` de Robocopy para listar y cuantificar imágenes pendientes sin copiar ningún archivo. |
| `--threads`, `-t` | `int` | `8` (desde config) | Número de hilos paralelos de Robocopy (`/MT:8` a `/MT:32`). |
| `--interval`, `-i` | `int` | `180` (desde config) | Intervalo de espera en segundos entre ciclos sucesivos en modo continuo. |

#### 💻 Comandos de Ejecución y Pruebas

```powershell
# 1. Servicio continuo persistente (bucle infinito cada sync_interval_seconds)
python sync_dcm_images.py
# O con el lanzador .bat:
.\ejecutar_sync_imagenes.bat

# 2. Pasada única (sincroniza pendientes y finaliza; ideal para pruebas manuales o cron externo)
python sync_dcm_images.py --once

# 3. Modo simulación (escanea el disco y reporta archivos nuevos sin transferirlos)
python sync_dcm_images.py --dry-run

# 4. Acelerar transferencia con 16 o 32 hilos (para redes 10 GbE o cargas masivas iniciales)
python sync_dcm_images.py --threads 16
python sync_dcm_images.py --once --threads 32

# 5. Ajustar intervalo de escaneo continuo a 60 segundos
python sync_dcm_images.py --interval 60

# 6. Ejecutar utilizando configuración alternativa
python sync_dcm_images.py --config C:\ruta\otro_config.json --once
```

#### 🔍 Diagnóstico y Monitoreo en Vivo

```powershell
# Monitorear la bitácora principal del ciclo de sincronización:
Get-Content .\logs\sync_imagenes.log -Wait -Tail 30

# Monitorear el escaneo detallado de directorios e imágenes de Robocopy en tiempo real:
Get-Content .\logs\robocopy_scan.log -Wait -Tail 30

# Consultar el estado del último ciclo (archivos copiados, omitidos, fallidos, espacio):
Get-Content .\status_sync_*.json | ConvertFrom-Json | Format-List

# Verificar si hay procesos de Robocopy o Python activos en el sistema:
Get-Process -Name robocopy, python -ErrorAction SilentlyContinue | Format-Table Id, ProcessName, CPU, WorkingSet

# Si el agente quedó bloqueado por un reinicio abrupto, liberar el lockfile:
Remove-Item .\logs\sync_dcm.lock -Force -ErrorAction SilentlyContinue
```

---

### 3. Agente Edge de Dosimetría Radiológica (`agente_dosimetria.py`)

* **Lanzador por lotes:** `.\ejecutar_dosimetria.bat` (Procesa las últimas 24 horas cada hora).
* **Función:** Extracción de dosis ionizantes (`CTDIvol`, `DLP`, `DGM`, `DAP`) de cabeceras DICOM de Tomografía, Mamografía y Rayos X en cumplimiento de la **Resolución 482 de 2018**, con anonimización SHA-256 de identificadores y envío a LIDiag Central.

#### 📋 Tabla de Argumentos de Línea de Comandos (`agente_dosimetria.py`)

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON. |
| `--dry-run` | `flag` | `False` | **Modo Simulación:** Extrae y calcula las métricas dosimétricas en pantalla sin transmitirlas a la API central. |
| `--dias` | `int` | `1` | Ventana de días hacia atrás a consultar desde la fecha actual. |
| `--desde` | `str` | `None` | Fecha inicial fija de consulta en formato `YYYY-MM-DD`. |
| `--hasta` | `str` | `None` | Fecha final fija de consulta en formato `YYYY-MM-DD`. |
| `--limit` | `int` | `200` | Límite máximo de estudios a procesar por ciclo. |
| `--forzar`, `--force` | `flag` | `False` | **Forzar re-procesamiento:** Ignora la memoria caché local y vuelve a evaluar y transmitir estudios ya leídos. |
| `--limpiar-cache` | `flag` | `False` | Elimina el archivo `logs/dosimetria_cache_procesados.json` y finaliza. |
| `--sede-id` | `int` | Desde config | Sobrescribe el ID de la sede hospitalaria en las métricas transmitidas. |

#### 💻 Comandos de Ejecución y Pruebas

```powershell
# 1. Ejecución estándar (últimas 24 horas, límite 200 estudios)
python agente_dosimetria.py
# O con el lanzador .bat:
.\ejecutar_dosimetria.bat

# 2. Modo Simulación (audita y muestra métricas clínicas en consola sin transmitirlas)
python agente_dosimetria.py --dry-run --dias 7

# 3. Procesar ventana amplia de días (ej: últimos 7, 15 o 30 días con mayor cupo de estudios)
python agente_dosimetria.py --dias 7 --limit 500
python agente_dosimetria.py --dias 30 --limit 1000

# 4. Procesar un rango histórico específico (ej: consolidar el mes de Agosto o Septiembre completo)
python agente_dosimetria.py --desde 2026-08-01 --hasta 2026-08-31 --limit 1000

# 5. Forzar re-transmisión de un período ya procesado (bypasea caché)
python agente_dosimetria.py --desde 2026-10-01 --hasta 2026-10-15 --limit 300 --forzar

# 6. Limpiar la memoria caché local de estudios procesados
python agente_dosimetria.py --limpiar-cache

# 7. Sobrescribir temporalmente el ID de sede sin editar config.json
python agente_dosimetria.py --sede-id 2 --dias 3
```

#### 🔍 Diagnóstico y Monitoreo en Vivo

```powershell
# Monitorear la bitácora de extracción y transmisión de dosimetría:
Get-Content .\logs\dosimetria_agent.log -Wait -Tail 30

# Probar la conectividad HTTPS y la validez del token Bearer con la API Central de LIDiag:
$token = "TU_TOKEN_BEARER_AQUI"
Invoke-RestMethod -Uri "https://10.35.47.4/lidiag/public/api/dosimetria/registros" `
    -Method GET `
    -Headers @{ "Authorization" = "Bearer $token"; "Accept" = "application/json" }

# Consultar el conteo de estudios por modalidad con radiación ionizante en MySQL local:
mysql -u root -p -e "SELECT modality, COUNT(*) AS total FROM pacsdb.series WHERE modality IN ('CT','MG','DX','CR') GROUP BY modality;"
```

---

### 4. Agente de Recuperación ante Desastres de Imágenes (`restore_dcm_images.py`)

* **Lanzador por lotes:** `.\ejecutar_restore_imagenes.bat` (Asistente guiado interactivo).
* **Función:** Recuperación ante emergencias (reinstalación de SO o cambio de servidor). Descarga el repositorio de imágenes desde la NAS QNAP hacia el disco local en **orden cronológico descendente** (`2026` -> `2025` -> `2024` -> ...), garantizando la continuidad asistencial en menos de 3 horas para el año en curso.

#### 📋 Tabla de Argumentos de Línea de Comandos (`restore_dcm_images.py`)

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON. |
| `--year`, `-y` | `str` | `None` | Restaura **exclusivamente** el año indicado (ej. `--year=2026`). |
| `--from-year` | `str` | `None` | Restaura en orden descendente desde el año actual hasta el año límite (ej. `--from-year=2024` restaura 2026, 2025 y 2024). |
| `--threads`, `-t` | `int` | `16` | Hilos concurrentes de Robocopy para descarga masiva (`/MT:16` a `/MT:32`). |
| `--dry-run` | `flag` | `False` | **Modo Simulación:** Escanea el QNAP y calcula el tamaño total a restaurar sin copiar ningún archivo. |

#### 💻 Comandos de Ejecución y Pruebas

```powershell
# 1. Asistente interactivo guiado por menú
.\ejecutar_restore_imagenes.bat

# 2. Restauración completa de todos los años en orden descendente
python restore_dcm_images.py

# 3. Restaurar con máxima prioridad únicamente el año actual (para abrir urgencias de inmediato)
python restore_dcm_images.py --year 2026

# 4. Restaurar desde el año actual hasta un año límite histórico (ej: 2026 a 2024)
python restore_dcm_images.py --from-year 2024

# 5. Acelerar la descarga en enlaces de alta velocidad forzando 32 hilos
python restore_dcm_images.py --year 2026 --threads 32

# 6. Modo simulación (audita qué carpetas y volúmenes existen en la NAS sin descargar)
python restore_dcm_images.py --dry-run
```

---

### 5. Control de Tareas Programadas de Windows (`schtasks`)

El script `instalar_tareas_sede.bat` registra 3 tareas bajo la cuenta del sistema `NT AUTHORITY\SYSTEM`:

```powershell
# 1. Registrar o reinstalar las 3 tareas automáticas (Ejecutar CMD/PowerShell como Administrador):
.\instalar_tareas_sede.bat

# 2. Consultar el estado y última ejecución de las tareas:
schtasks /query /tn "LIDiag_Backup_DB_DCM" /fo TABLE /v
schtasks /query /tn "LIDiag_Sync_Imagenes_DCM" /fo TABLE /v
schtasks /query /tn "LIDiag_Dosimetria_Edge" /fo TABLE /v

# 3. Ejecutar inmediatamente una tarea a demanda sin esperar su horario:
schtasks /run /tn "LIDiag_Backup_DB_DCM"
schtasks /run /tn "LIDiag_Sync_Imagenes_DCM"
schtasks /run /tn "LIDiag_Dosimetria_Edge"

# 4. Detener una tarea en ejecución:
schtasks /end /tn "LIDiag_Sync_Imagenes_DCM"

# 5. Eliminar una tarea en caso de desinstalación:
schtasks /delete /tn "LIDiag_Backup_DB_DCM" /f
schtasks /delete /tn "LIDiag_Sync_Imagenes_DCM" /f
schtasks /delete /tn "LIDiag_Dosimetria_Edge" /f
```

---

### 6. Mantenimiento del Entorno Virtual Python (`venv`)

El entorno aislado asegura que no existan conflictos con otras versiones de Python instaladas en el servidor del hospital:

```powershell
# 1. Activar el entorno virtual manualmente en PowerShell:
.\venv\Scripts\Activate.ps1

# O en consola CMD:
.\venv\Scripts\activate.bat

# 2. Verificar versión de Python y paquetes instalados:
python -V
pip list

# 3. Reinstalar o actualizar todas las librerías clínicas desde requirements.txt:
pip install -r requirements.txt --upgrade

# 4. Probar importación de dependencias críticas en una línea:
python -c "import pydicom, requests, mysql.connector, urllib3; print('>>> TODAS LAS DEPENDENCIAS ESTAN OPERATIVAS <<<')"
```

---

### 7. Inspección de Manifiestos de Telemetría JSON

El agente escribe periódicamente archivos JSON de telemetría local y remota que alimentan el panel central de LIDiag (`/seguridad-backups` y `/seguridad-tokens`):

```powershell
# 1. Ver el estado consolidado de salud de los 3 agentes (Base de Datos, Imágenes y Dosimetría):
Get-Content .\healthcheck_agente.json | ConvertFrom-Json | Format-List

# 2. Ver detalles del último respaldo de MySQL:
Get-Content .\backup_status_sede_*.json | ConvertFrom-Json | Format-List

# 3. Ver estadísticas de sincronización de imágenes:
Get-Content .\status_sync_*.json | ConvertFrom-Json | Format-List
```

---

## Telemetría y Bitácoras Centralizadas (`logs/`)

Todas las bitácoras operativas de los servicios del agente se almacenan de forma ordenada en la carpeta dedicada **`logs/`**:
* **`logs/sync_imagenes.log`**: Registro del ciclo continuo del agente de sincronización de imágenes PACS.
* **`logs/robocopy_scan.log`**: Registro en vivo y detallado del escaneo e indexación multi-hilo de Robocopy.
* **`logs/backup_db.log`**: Bitácora de volcado MySQL, prueba CRC32, cálculo SHA-256 y transferencia a QNAP.
* **`logs/dosimetria_agent.log`**: Registro de extracción de radiación ionizante y transmisión a LIDiag Central.

Además, el agente emite de forma continua manifiestos de estado JSON tanto localmente como en el QNAP:
* `status_sync_{sede}.json`: Estado de la sincronización de imágenes (último ciclo, archivos copiados, espacio en disco).
* `backup_status_sede_{sede}.json`: Estado del último volcado de base de datos MySQL (hash SHA-256, duración, tamaño).
* `healthcheck_agente.json`: Manifiesto consolidado en tiempo real con el estado de salud de los 3 servicios (Base de Datos, Imágenes y Dosimetría).
* `estudios_procesados_cache.json`: Caché local de estudios de dosimetría ya procesados.
* `POST /api/dosimetria/registros`: Transmisión HTTPS de métricas de radiación agrupadas en micro-lotes de 50 registros con validación de Niveles de Referencia para Diagnóstico (NRD).

---

## Recuperación ante Desastres (Disaster Recovery de Imágenes)

Cuando sea necesario reinstalar el servidor PACS o migrar a un nuevo hardware:

1. **Restaurar la Base de Datos:** Restaurar el archivo `.sql` contenido en el último `backup_dcm_sede_*.zip` respaldado en la QNAP en MySQL (`pacsdb`).
2. **Restaurar las Imágenes Cronológicamente:**
   * Haz doble clic en **`ejecutar_restore_imagenes.bat`**.
   * El asistente descargará las imágenes en **orden cronológico descendente** (`2026` -> `2025` -> `2024` -> ...).
   * **Continuidad Asistencial Inmediata:** Tan pronto finalice el año actual (`2026`), los médicos de urgencias y consulta externa pueden empezar a visualizar estudios recientes de inmediato en el visor DICOM (Oviyam/OHIF) mientras los años históricos continúan restaurándose en segundo plano.
   * **Reanudable al 100%:** Si la red se desconecta o el servidor se reinicia, al volver a ejecutar el script este reanuda automáticamente sin re-descargar archivos ya copiados.
