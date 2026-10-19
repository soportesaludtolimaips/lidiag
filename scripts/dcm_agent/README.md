# Agente de Backups DCM para Sedes (LIDiag / QNAP Qsync)

Este agente se instala y ejecuta localmente en el servidor PACS/Dcm4chee de cada sede u hospital.
Genera el volcado local de MySQL comprimido en ZIP en la carpeta local sincronizada por el cliente **Qsync de QNAP** (por defecto `C:\backups_dcm`), y purga automáticamente los backups antiguos (> 30 días).

## Ventajas del flujo local + QNAP Qsync:
1. **Velocidad y aislamiento:** El volcado `mysqldump` se realiza a velocidad de disco SSD local en cuestión de segundos o pocos minutos.
2. **Sincronización desatendida:** El cliente **Qsync** detecta el archivo `.zip` en `C:\backups_dcm` y lo sincroniza de manera incremental y segura hacia el NAS QNAP central.
3. **Independiente de la conexión:** Si la VPN o el enlace a internet se cae temporalmente, el backup local se completa exitosamente y Qsync lo transferirá automáticamente en cuanto se restablezca la conectividad.
4. **Auto-limpieza (Purga):** Elimina automáticamente los backups locales que superen los días de retención configurados (30 días), protegiendo el espacio en disco del servidor del hospital.

---

## 1. Requisitos
- **Python 3.7 o superior** instalado en el servidor del hospital.
- **mysqldump** disponible en el sistema (MySQL Server o MariaDB).
- **Cliente Qsync de QNAP** instalado y configurado para sincronizar la carpeta `C:\backups_dcm` con el NAS.

No requiere librerías adicionales con `pip` (utiliza únicamente la biblioteca estándar de Python).

---

## 2. Configuración
1. Copia la carpeta `dcm_agent` al servidor de la sede (ejemplo: `C:\lidiag_backup\` en Windows o `/opt/lidiag_backup/` en Linux).
2. Copia el archivo `config.json.example` a `config.json`:
   ```bash
   copy config.json.example config.json
   ```
3. Edita `config.json` con los datos de la sede:
   ```json
   {
     "sede_id": 1,
     "sede_nombre": "espinal_hospital_san_rafael",
     "db_host": "127.0.0.1",
     "db_port": 3306,
     "db_name": "pacsdb",
     "db_user": "root",
     "db_password": "tu_password",
     "mysqldump_path": "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe",
     "destination_dir": "C:\\backups_dcm",
     "retention_days": 30,
     "temp_dir": "./temp_backups"
   }
   ```
   > **Nota para servidores Windows:** Si tienes MySQL instalado en `Program Files`, especifica la ruta completa a `mysqldump.exe` en `mysqldump_path` usando doble barra invertida (`\\`). Si se deja vacío, el script buscará automáticamente en las rutas estándar.

---

## 3. Prueba Manual
Ejecuta el script desde una consola CMD o PowerShell:
```cmd
python backup_dcm_agent.py --config config.json
```
O simplemente haciendo doble clic en `ejecutar_backup.bat` y revisando `backup_agent.log`.

---

## 4. Programación Automática

### En Windows (Programador de Tareas):

#### Opción A — Instalación automática en 1 clic (Recomendada):
1. Copia toda la carpeta `dcm_agent` al servidor del hospital (ej: `C:\lidiag_backup\`).
2. Configura tu `config.json`.
3. Haz **clic derecho** sobre `instalar_tarea_programada.bat` y selecciona **"Ejecutar como administrador"**.
4. ¡Listo! La tarea `LIDiag_Local_DCM_Backup` quedará programada para las **02:00 AM** con permisos de sistema (`SYSTEM`) para que corra incluso sin un usuario conectado.

#### Opción B — Configuración manual en la interfaz gráfica (taskschd.msc):
1. Presiona `Win + R`, escribe `taskschd.msc` y presiona Enter.
2. En el panel derecho haz clic en **"Crear tarea..."**.
3. **General:**
   - Nombre: `LIDiag_Local_DCM_Backup`
   - Seleccionar: *"Ejecutar tanto si el usuario inició sesión como si no"*
   - Marcar: *"Ejecutar con los privilegios más altos"*
4. **Desencadenadores (Triggers):**
   - Nuevo desencadenador $\rightarrow$ *Diariamente* $\rightarrow$ Hora: `02:00:00 AM`.
5. **Acciones (Actions):**
   - Nueva acción $\rightarrow$ *Iniciar un programa*.
   - **Programa o script:** `C:\lidiag_backup\ejecutar_backup.bat`
   - O si usas `python.exe` directo:
     - Argumentos: `backup_dcm_agent.py --config config.json`
     - Iniciar en (Start in): `C:\lidiag_backup\`
6. Guarda la tarea.

> Los logs de cada ejecución automática se guardarán en `backup_agent.log` dentro de la misma carpeta.
