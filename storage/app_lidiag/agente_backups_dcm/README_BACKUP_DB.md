# LIDiag — Agente de Respaldo de Base de Datos MySQL (PACS DB)

Script principal: **`backup_dcm_agent.py`**  
Lanzador por lotes: **`ejecutar_backup_db.bat`**  
Tarea programada de Windows: **`LIDiag_Backup_DB_DCM`**  
Frecuencia recomendada: **Diaria (02:00 AM nocturna)**  

---

## 1. Descripción y Propósito Clínico

El Agente de Respaldo de Base de Datos MySQL garantiza la preservación íntegra y automatizada de la base de datos de metadatos clínicos del servidor PACS dcm4chee (`pacsdb`), que contiene el catálogo completo de pacientes, estudios, series e instancias radiológicas de la sede hospitalaria.

### Capacidades Principales:
* **Volcado consistente con `mysqldump`:** Extrae la base de datos con bloqueo transaccional mínimo (`--single-transaction` y `--quick`) para no interrumpir el flujo asistencial.
* **Compresión ZIP de alta velocidad:** Empaqueta el archivo `.sql` en un archivo `.zip` con ahorro promedio del 85% de espacio en disco.
* **Validación de integridad CRC32:** Comprueba que el archivo comprimido no presente corrupción antes de darlo por válido.
* **Huella criptográfica SHA-256:** Calcula y registra el hash SHA-256 del respaldo para auditorías forenses y cumplimiento normativo.
* **Transmisión directa a NAS QNAP vía SMB (`\\<qnap_ip>\<share>`):** Transfiere de inmediato el respaldo a la unidad NAS compartida en la red local.
* **Purga dual por retención (Local y Remota):** Elimina automáticamente respaldos que superen los días de retención configurados (por defecto 30 días) tanto en el disco local como en la NAS QNAP.
* **Manifiesto de telemetría JSON:** Genera `backup_status_sede_{sede}.json` y emite telemetría hacia LIDiag Central para alimentar el monitor asistencial (`/seguridad-backups`).

---

## 2. Configuración en `config.json`

El agente lee los siguientes parámetros del archivo `config.json`:

```json
{
  "sede_id": 1,
  "sede_nombre": "Espinal",
  "qnap_ip": "10.35.47.21",
  "qnap_user": "sede_espinal",
  "qnap_password": "PASSWORD_QNAP_AQUI",
  "qnap_share_backups": "backups_espinal",

  "db_host": "127.0.0.1",
  "db_port": 3306,
  "db_name": "pacsdb",
  "db_user": "root",
  "db_password": "PASSWORD_MYSQL_AQUI",
  "mysqldump_path": "C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe",
  "destination_dir": "C:\\backups_dcm",
  "retention_days": 30,
  "min_free_space_gb": 5,
  "subir_zip_central": false,

  "api_url": "https://10.35.47.4/lidiag/public/api/dosis/registros",
  "api_token": "TOKEN_BEARER_LIDIAG_AQUI"
}
```

### Explicación de Parámetros Clave:
* **`subir_zip_central`**: (Opcional, por defecto `false`). Política de custodia WAN inteligente. Cuando está en `false`, el backup completo permanece custodiado en el almacenamiento local y la NAS QNAP de la sede, emitiendo telemetría instantánea a Central. Si se establece en `true`, intentará además transmitir el archivo ZIP masivo por HTTP hacia Central LIDiag.

---

## 3. Tabla de Argumentos de Línea de Comandos

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

---

## 4. Comandos de Ejecución y Pruebas

```powershell
# 1. Ejecución estándar utilizando la configuración de config.json
python backup_dcm_agent.py

# O mediante su lanzador por lotes:
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

---

## 5. Diagnóstico y Pruebas de Conectividad en Vivo

```powershell
# 1. Probar socket TCP local de MySQL (puerto 3306):
Test-NetConnection -ComputerName 127.0.0.1 -Port 3306

# 2. Probar disponibilidad de puertos SMB en la NAS QNAP (evalúa 139 NetBIOS y 445 SMB):
Test-NetConnection -ComputerName 10.35.47.21 -Port 139
Test-NetConnection -ComputerName 10.35.47.21 -Port 445

# 3. Probar autenticación y montaje manual del recurso SMB en Windows:
net use \\10.35.47.21\backups_espinal /user:sede_espinal PASSWORD_AQUI

# Desmontar recurso tras la prueba:
net use \\10.35.47.21\backups_espinal /delete /y

# 4. Monitorear la bitácora del agente en tiempo real:
Get-Content .\logs\backup_db.log -Wait -Tail 30

# 5. Consultar el estado del último volcado generado (manifiesto JSON):
Get-Content .\backup_status_sede_*.json | ConvertFrom-Json | Format-List
```

---

## 6. Nomenclatura Estándar de Archivos Generados

Cada archivo de respaldo se genera siguiendo la convención estricta:
```text
backup_dcm_sede_{sede_id}_{slug_sede}_{YYYY-MM-DD_HH-mm-ss}.zip
```
Ejemplo real:
```text
backup_dcm_sede_1_espinal_2026-11-04_02-00-15.zip
```
Este estándar permite que el módulo de **Seguridad y Backups** de LIDiag Central identifique de forma unívoca la sede emisora, la antigüedad y el estado de salud sin ambigüedad.
