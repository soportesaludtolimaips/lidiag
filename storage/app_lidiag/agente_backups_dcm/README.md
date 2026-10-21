# LIDiag — Agente Unificado de Backups PACS DCM por Sede

Solución autónoma y de grado clínico para servidores hospitalarios de PACS / DCM.
Combina en un solo paquete y con una sola configuración:
1. **Respaldo de Base de Datos MySQL (PACS DB):** Volcado nocturno con `mysqldump`, compresión ZIP de alta velocidad, prueba de integridad CRC32, cálculo de huella criptográfica SHA-256 y purga automática por retención.
2. **Sincronización Continua de Imágenes DICOM a QNAP (Modelo Dropbox / Google Drive):** Monitoreo continuo en segundo plano con motor nativo multi-hilo `robocopy` que solo transfiere archivos `.dcm` nuevos hacia el QNAP NAS, con modo backup seguro (nunca borra en el NAS).

---

## Archivos del Paquete

| Archivo | Descripción |
| :--- | :--- |
| `config.json.example` | Plantilla única de configuración (datos de la sede, QNAP, MySQL e imágenes). |
| `backup_dcm_agent.py` | Script que realiza el volcado, compresión ZIP, CRC32 y SHA-256 de la BD. |
| `sync_dcm_images.py` | Script que ejecuta la sincronización continua e incremental de imágenes DICOM. |
| `ejecutar_backup_db.bat` | Lanzador del respaldo de base de datos MySQL. |
| `ejecutar_sync_imagenes.bat` | Lanzador del servicio continuo de sincronización de imágenes. |
| `instalar_tareas_sede.bat` | **Instalador de 1 Clic:** Registra ambas tareas en el Programador de Tareas de Windows bajo `SYSTEM`. |
| `README.md` | Este manual técnico de instalación y operación. |

---

## Guía Rápida de Despliegue en el Servidor PACS de la Sede

### 1. Copiar y Descomprimir
Descomprime el archivo `agente_backups_dcm.zip` en una ruta fija del servidor del hospital (por ejemplo en `C:\agente_backups_dcm\`).

### 2. Configurar `config.json`
Copia `config.json.example` y renómbralo a `config.json`. Ajusta los parámetros de la sede:

```json
{
    "sede_nombre": "Chaparral",
    "qnap_ip": "192.168.1.100",
    "qnap_user": "sede_chaparral",
    "qnap_password": "PASSWORD_AQUI",
    "qnap_share_backups": "backups_chaparral",
    "qnap_share_imagenes": "imagenes_chaparral",

    "db_host": "127.0.0.1",
    "db_port": 3306,
    "db_name": "pacsdb",
    "db_user": "root",
    "db_password": "",
    "mysqldump_path": "C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe",
    "destination_dir": "C:\\backups_dcm",
    "retention_days": 30,

    "local_images_dir": "D:\\imagenes_pacs",
    "sync_interval_seconds": 180,
    "threads": 8,
    "min_free_space_gb": 10
}
```

### 3. Probar la Ejecución Manual
Abre una consola de PowerShell o CMD en la carpeta del agente y prueba ambos procesos:

* **Prueba de Base de Datos:**
  ```powershell
  python backup_dcm_agent.py
  ```
* **Prueba de Sincronización de Imágenes (Pasada única):**
  ```powershell
  python sync_dcm_images.py --once
  ```

### 4. Instalar Tareas Automáticas de Windows (1 Clic)
Haz clic derecho sobre **`instalar_tareas_sede.bat`** y selecciona **"Ejecutar como administrador"**.
Esto dejará configuradas automáticamente:
* `LIDiag_Backup_DB_DCM`: Se ejecuta diariamente a las **02:00 AM**.
* `LIDiag_Sync_Imagenes_DCM`: Arranca automáticamente al prender el servidor (**ONSTART**) y sincroniza continuamente cada 3 minutos en segundo plano bajo la cuenta `SYSTEM`.

---

## Telemetría y Monitoreo en el QNAP

El agente emite de forma continua manifiestos de estado JSON tanto localmente como en el QNAP:
* `status_sync_{sede}.json`: Estado de la sincronización de imágenes (último ciclo, archivos copiados, espacio en disco).
* `backup_status_sede_{sede}.json`: Estado del último volcado de base de datos MySQL (hash SHA-256, duración, tamaño).
