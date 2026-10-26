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
