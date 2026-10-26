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
Copia `config.json.example` y renómbralo a `config.json`. Ajusta los parámetros de la sede:

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

    "api_url": "https://10.35.47.4/lidiag-update/public/api/dosimetria/registros",
    "api_token": "TOKEN_BEARER_LIDIAG_AQUI"
}
```

### 4. Probar la Ejecución Manual
Abre una consola de PowerShell o CMD en la carpeta del agente y prueba los procesos:

* **Prueba de Base de Datos:**
  ```powershell
  python backup_dcm_agent.py
  ```
* **Prueba de Sincronización de Imágenes (Pasada única):**
  ```powershell
  python sync_dcm_images.py --once
  ```
* **Prueba de Extracción de Dosimetría (Modo simulado sin envío):**
  ```powershell
  python agente_dosimetria.py --dry-run
  ```

---

## Catálogo Detallado de Agentes y Comandos

A continuación se detalla la función clínica, arquitectura y todos los comandos y argumentos disponibles para cada componente:

### 1. Respaldo de Base de Datos MySQL (`backup_dcm_agent.py`)

* **Lanzador rápido:** `ejecutar_backup_db.bat` (Diseñado para tareas desatendidas nocturnas).
* **Descripción técnica y clínica:**
  - Realiza un pre-chequeo por socket TCP (puerto 3306) para verificar disponibilidad de MySQL antes de iniciar.
  - Ejecuta el volcado consistente de la base de datos `pacsdb` de dcm4chee mediante `mysqldump.exe`.
  - Comprime el archivo `.sql` en tiempo real a formato ZIP para ahorrar hasta un 85% de espacio.
  - Valida la integridad del archivo comprimido mediante algoritmo CRC32.
  - Calcula la huella criptográfica SHA-256 para trazabilidad y auditoría forense (ISO 27001).
  - Gestiona la retención local purgando respaldos que superen los días configurados (`retention_days`).
  - Autentica y transfiere el ZIP directamente a la carpeta compartida SMB del QNAP NAS (`\\<qnap_ip>\<qnap_share_backups>`).
  - Verifica la integridad remota comparando byte a byte el tamaño del archivo transferido contra el local (`remote_size == local_size`).
  - Aplica la política de retención remota en el QNAP eliminando volcados viejos de esa sede.
  - Emite estado en `logs/backup_db.log`, `backup_status_sede_{sede}.json` y el manifiesto unificado `healthcheck_agente.json`.
* **Comandos disponibles:**
  ```cmd
  :: Ejecución estándar con config.json por defecto
  python backup_dcm_agent.py

  :: Especificar un archivo de configuración alternativo
  python backup_dcm_agent.py --config C:\ruta\otro_config.json

  :: Modo simulación (Dry-Run): valida conexión y rutas sin volcar datos ni transferir
  python backup_dcm_agent.py --dry-run
  ```

---

### 2. Sincronización Continua de Imágenes DICOM (`sync_dcm_images.py`)

* **Lanzador rápido:** `ejecutar_sync_imagenes.bat` (Inicia el servicio persistente en bucle infinito).
* **Descripción técnica y clínica:**
  - Servicio demonio continuo que replica el comportamiento de Dropbox / Google Drive para el repositorio de 18 TB de imágenes DICOM (`D:\img_dcm` hacia `\\<qnap_ip>\<qnap_share_imagenes>`).
  - Escanea e indexa cambios en intervalos definidos (`sync_interval_seconds`, ej: cada 3 minutos).
  - Utiliza el motor nativo de alta velocidad `robocopy.exe` con multi-threading (`/MT:8` a `/MT:32`).
  - **Exclusión de archivos temporales e índices del sistema operativo:** Omite automáticamente archivos basura (`desktop.ini`, `Thumbs.db`, `*.tmp`, `*.syncrootid`, `*.part`) y carpetas del NAS (`@Recycle`, `.syncing_db`, `.qsyncclient`, `$RECYCLE.BIN`, `System Volume Information`).
  - **Normalizador de stream multi-hilo:** Desacopla fragmentos colisionados en stdout para que `logs/robocopy_scan.log` muestre limpiamente cada directorio y archivo procesado sin truncamiento.
  - **Control de concurrencia:** Implementa lockfile (`logs/sync_dcm.lock`) con validación activa de PID de Windows para impedir instancias duplicadas.
  - **Terminación limpia:** Captura señales `SIGINT` (Ctrl+C) y `SIGTERM` para finalizar ordenadamente los procesos hijos de Robocopy sin dejar procesos huérfanos.
  - **Chequeo de espacio:** Verifica que el almacenamiento cuente con al menos `min_free_space_gb` libres antes de transferir.
* **Comandos disponibles:**
  ```cmd
  :: Servicio continuo (bucle cada sync_interval_seconds)
  python sync_dcm_images.py

  :: Ejecutar un solo ciclo de sincronización y terminar inmediatamente
  python sync_dcm_images.py --once

  :: Sobrescribir el número de hilos de Robocopy para mayor velocidad (ej: 16 hilos)
  python sync_dcm_images.py --threads 16

  :: Sobrescribir el intervalo de espera entre ciclos en segundos (ej: cada 60 segundos)
  python sync_dcm_images.py --interval 60

  :: Modo prueba (simula el escaneo con /L de Robocopy sin transferir archivos)
  python sync_dcm_images.py --dry-run

  :: Usar archivo de configuración específico
  python sync_dcm_images.py --config config.json --once
  ```

---

### 3. Agente Edge de Dosimetría Radiológica (`agente_dosimetria.py`)

* **Lanzador rápido:** `ejecutar_dosimetria.bat` (Diseñado para ejecutarse periódicamente cada 1 hora).
* **Descripción técnica y clínica:**
  - Cumplimiento normativo estricto de la **Resolución 482 de 2018** (Colombia) para el control de dosis de radiación en pacientes.
  - Conecta a MySQL `pacsdb` y consulta estudios recientes de modalidades con radiación ionizante:
    - **CT (Tomografía Computarizada):** Extrae `CTDIvol` (mGy) y `DLP` (mGy*cm).
    - **MG (Mamografía):** Extrae Dosis Glandular Media `DGM` / Organ Dose (mGy).
    - **DX / CR (Radiografía Digital):** Extrae Producto Dosis-Área `DAP` (Gy*cm²).
  - **Doble compatibilidad SQL:** Emplea funciones de agregación ANSI (`MAX`, `MIN`) y neutraliza dinámicamente `ONLY_FULL_GROUP_BY` a nivel de sesión, soportando MySQL 5.5, 5.7, 8.0, 8.4 y MariaDB.
  - **Lectura ultra-rápida de cabeceras:** Utiliza `pydicom` con `stop_before_pixels=True` para leer únicamente los metadatos clínicos sin cargar las matrices pesadas de imagen, procesando estudios en milisegundos.
  - **Anonimización Criptográfica (ISO/IEC 27001):** El `PatientID` se convierte en un hash SHA-256 irreversible con salt antes de salir del servidor local. Jamás viajan datos de identificación sensible por la red.
  - **Memoria de Estado y Caché Anti-Duplicados:** Almacena los estudios leídos en `logs/dosimetria_cache_procesados.json`. Si un estudio ya fue evaluado, se omite de inmediato. Si el archivo DICOM aún no existe en disco, **no** se guarda en caché, permitiendo su reintento automático cuando el archivo esté disponible.
  - **Transmisión Segura y Resiliente (Chunking):** Empaqueta los registros en micro-lotes de 50 elementos y los envía por HTTPS a la API central de LIDiag (`POST /api/dosimetria/registros`) con política de reintentos exponenciales (backoff de 2s, 4s, 8s, 16s) ante micro-cortes de red.
* **Comandos disponibles:**
  ```cmd
  :: Ejecución estándar (procesa las últimas 24 horas, límite 200 estudios)
  python agente_dosimetria.py

  :: Extender la ventana temporal a los últimos N días (ej: últimos 7 días)
  python agente_dosimetria.py --dias 7

  :: Filtrar por rango de fechas fijo (ideal para procesar meses históricos específicos)
  python agente_dosimetria.py --desde 2026-08-01 --hasta 2026-08-31 --limit 500

  :: Forzar re-procesamiento (ignora la caché local y re-transmite estudios ya evaluados)
  python agente_dosimetria.py --desde 2026-10-25 --hasta 2026-10-26 --limit 100 --forzar

  :: Limpiar caché local de estudios procesados
  python agente_dosimetria.py --limpiar-cache

  :: Auditoría profunda o escaneo histórico amplio (ej: últimos 90 días, hasta 500 estudios)
  python agente_dosimetria.py --dias 90 --limit 500

  :: Modo simulación (Dry-Run): extrae las métricas en consola sin enviarlas a la API Central
  python agente_dosimetria.py --dry-run --dias 30

  :: Sobrescribir temporalmente el ID de sede
  python agente_dosimetria.py --sede-id 1 --dias 1

  :: Especificar configuración personalizada
  python agente_dosimetria.py --config config.json
  ```

---

### 4. Recuperación ante Desastres de Imágenes (`restore_dcm_images.py`)

* **Lanzador rápido:** `ejecutar_restore_imagenes.bat` (Menú interactivo con opciones guiadas de restauración).
* **Descripción técnica y clínica:**
  - Diseñado para emergencias críticas: reemplazo de servidor dañado, fallo de discos o reinstalación del sistema operativo del PACS.
  - Restaura el volumen completo de 18 TB de imágenes desde la NAS QNAP hacia el disco local `D:\img_dcm`.
  - **Orden Cronológico Descendente Estricto:** Descarga primero el año en curso (`2026`), seguido de los años inmediatamente anteriores (`2025`, `2024`, `2023`...).
  - **Continuidad Asistencial en Menos de 3 Horas:** Al completarse el año actual, los médicos de urgencias, UCI y consulta externa pueden consultar estudios recientes en los visores DICOM (Oviyam/OHIF) mientras la data histórica continúa descargándose en segundo plano.
  - **Reanudación Inteligente (Zero Re-download):** Aplica flags `/XO /XN /XC` de Robocopy para saltar archivos que ya existan con igual tamaño y fecha. Si se suspende la red o se reinicia la máquina, continúa exactamente donde iba.
  - **Pre-vuelo de Almacenamiento:** Valida espacio suficiente en el disco destino antes de transferir cada bloque anual.
* **Comandos disponibles:**
  ```cmd
  :: Menú interactivo asistido para administradores de TI
  ejecutar_restore_imagenes.bat

  :: Restauración completa de todos los años en orden cronológico descendente
  python restore_dcm_images.py

  :: Restaurar con urgencia máxima únicamente un año específico (ej: solo 2026)
  python restore_dcm_images.py --year 2026

  :: Restaurar desde el año actual descendiendo hasta un límite (ej: 2026 hasta 2024)
  python restore_dcm_images.py --from-year 2024

  :: Acelerar restauración en enlaces de fibra de 10 GbE forzando 32 hilos
  python restore_dcm_images.py --threads 32

  :: Simulación (Dry-Run): escanea directorios en el NAS y calcula tamaño sin copiar
  python restore_dcm_images.py --dry-run
  ```

---

### 5. Scripts de Soporte e Infraestructura

| Script | Propósito | Cómo se ejecuta |
| :--- | :--- | :--- |
| **`install.bat`** | Autodetecta Python en Windows, crea el entorno virtual aislado `venv\`, actualiza `pip`, instala las librerías de `requirements.txt` y prueba la importación de cada una. | Doble clic o `install.bat` en CMD. |
| **`instalar_tareas_sede.bat`** | Registra de forma desatendida las 3 tareas en el Programador de Tareas de Windows (`schtasks`) bajo la cuenta del sistema `SYSTEM` (`NT AUTHORITY\SYSTEM`). | Clic derecho -> **Ejecutar como Administrador**. |

---

### 5. Instalar Tareas Automáticas de Windows (1 Clic)
Haz clic derecho sobre **`instalar_tareas_sede.bat`** y selecciona **"Ejecutar como administrador"**.
Esto dejará configuradas automáticamente las 3 tareas del sistema bajo la cuenta `SYSTEM`:
1. `LIDiag_Backup_DB_DCM`: Se ejecuta diariamente a las **02:00 AM**.
2. `LIDiag_Sync_Imagenes_DCM`: Arranca automáticamente al prender el servidor (**ONSTART**) y sincroniza continuamente cada 3 minutos en segundo plano.
3. `LIDiag_Dosimetria_Edge`: Se ejecuta automáticamente **cada 1 hora** para procesar la telemetría de estudios recientes.

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
