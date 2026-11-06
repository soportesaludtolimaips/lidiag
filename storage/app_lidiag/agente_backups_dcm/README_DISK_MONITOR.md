# LIDiag — Agente de Monitoreo de Espacio en Discos y Repositorios PACS

Script principal: **`dcm_disk_monitor.py`**  
Lanzador por lotes: **`ejecutar_disk_monitor.bat`**  
Tarea programada de Windows: **`LIDiag_Disk_Monitor_DCM`**  
Frecuencia recomendada: **Cada 30 minutos (vía Task Scheduler)**  

---

## 1. Descripción y Propósito Clínico

El Agente de Monitoreo de Espacio en Discos audita periódicamente la capacidad total, espacio ocupado, gigabytes libres y porcentaje de utilización de las unidades de almacenamiento locales en el servidor PACS (`dcm4chee`).

Garantiza la continuidad operativa de los servicios de radiología, evitando la saturación imprevista de los discos de adquisición de estudios (Tomografía, Resonancia, RX, Mamografía) y reportando el estado de salud directamente al panel de control central de **Seguridad y Backups** (`https://<servidor_central>/seguridad-backups`).

### Capacidades Principales:
* **Detección Automática de Unidades (Sin duplicidad):** Inspecciona de forma transparente la unidad del sistema operativo (`C:\`), el directorio de respaldos locales (`destination_dir`) y todos los repositorios de imágenes configurados en `local_images_dir` (soporte mono y multi-disco).
* **Emisión de Telemetría a Central LIDiag:** Envía vía HTTPS seguro a `POST /api/backups/telemetria` las métricas de cada disco, permitiendo su monitoreo remoto en tiempo real.
* **Evaluación Preventiva por Umbrales:** Clasifica el estado de cada disco como **ÓPTIMO**, **ADVERTENCIA** (amarillo) o **CRÍTICO** (rojo) según los gigabytes y porcentajes libres disponibles.
* **Cero Dependencias Externas:** Construido íntegramente con la librería estándar de Python (`shutil`, `urllib`, `socket`, `json`).

---

## 2. Configuración en `config.json`

El agente lee los siguientes parámetros del archivo unificado `config.json`:

```json
{
  "sede_id": 1,
  "sede_nombre": "Espinal",
  "servidor_identificador": "SR-PACS-ESPINAL",

  "destination_dir": "C:\\backups_dcm",
  "local_images_dir": [
    "D:\\img_dcm",
    "E:\\img_dcm_historico"
  ],

  "umbrales": {
    "advertencia_porcentaje_libre": 15.0,
    "advertencia_gb_libres": 50.0,
    "critico_porcentaje_libre": 5.0,
    "critico_gb_libres": 20.0
  },

  "api_url": "https://10.35.47.4/lidiag/public/api/dosis/registros",
  "api_token": "TOKEN_BEARER_LIDIAG_AQUI"
}
```

### Explicación de Parámetros:
* **`local_images_dir`**: Ruta (string) o lista de rutas (array) de los discos donde residen las imágenes DICOM. El monitor los auditará automáticamente.
* **`destination_dir`**: Directorio de los respaldos locales de base de datos; su unidad de disco también es monitoreada automáticamente.
* **`umbrales`**:
  * `advertencia_porcentaje_libre`: % libre por debajo del cual se enciende alerta amarilla (por defecto: 15%).
  * `advertencia_gb_libres`: GB libres mínimos antes de generar advertencia (por defecto: 50 GB).
  * `critico_porcentaje_libre`: % libre por debajo del cual el disco se declara en riesgo crítico (por defecto: 5%).
  * `critico_gb_libres`: GB libres mínimos antes de declarar estado crítico (por defecto: 20 GB).
* **`api_url` y `api_token`**: Credenciales de enlace para emitir telemetría al servidor central.

---

## 3. Tabla de Argumentos de Línea de Comandos

| Argumento | Tipo | Valor por Defecto | Descripción |
| :--- | :---: | :---: | :--- |
| `--config`, `-c` | `str` | `config.json` | Ruta al archivo de configuración JSON de la sede. |
| `--check-once` | `flag` | `False` | **Pasada Única:** Audita discos, emite telemetría a Central y finaliza (usado por Windows Task Scheduler). |
| `--telemetria` | `flag` | `False` | Audita los discos e imprime el resultado detallado forzando la emisión inmediata a Central. |
| `--status` | `flag` | `False` | Modo consulta: Imprime en consola la tabla de estado, capacidad, GB libres y barras ASCII. |
| `--daemon` | `flag` | `False` | Modo servicio residente en segundo plano (bucle de monitoreo periódico). |

---

## 4. Ejemplos de Uso Práctico

### A. Consultar estado de discos en consola local:
```powershell
python dcm_disk_monitor.py --status
```
Salida esperada:
```text
==================================================================
ESTADO DE DISCOS — Espinal (SR-PACS-ESPINAL)
Fecha: 2026-11-06 10:45:00
==================================================================
[OK] Disco del Sistema (OS) (C:\):
  Capacidad: 1.86 TB
  Usado:     296.53 GB (15.6%) [██░░░░░░░░]
  Libre:     1.57 TB (84.4%)
------------------------------------------------------------------
[OK] Repositorio Imágenes #1 (D:) (D:\img_dcm):
  Capacidad: 1.86 TB
  Usado:     1.37 TB (73.7%) [███████░░░]
  Libre:     502.36 GB (26.3%)
------------------------------------------------------------------
[OK] Destino Backups Locales (C:\backups_dcm):
  Capacidad: 1.86 TB
  Usado:     296.53 GB (15.6%) [██░░░░░░░░]
  Libre:     1.57 TB (84.4%)
==================================================================
```

### B. Probar emisión de telemetría hacia Central:
```powershell
python dcm_disk_monitor.py --telemetria
```

### C. Ejecución manual del lanzador por lotes:
```powershell
.\ejecutar_disk_monitor.bat
```
