# Agente de Backups DCM para Sedes (LIDiag)

Este agente se instala y ejecuta localmente en el servidor PACS/Dcm4chee de cada sede u hospital.

## Ventajas del flujo local:
1. **Velocidad:** El volcado `mysqldump` se realiza a velocidad local del disco SSD (segundos, no minutos).
2. **Eficiencia de red:** Comprime el volcado a ZIP **antes** de enviarlo por la red (reduce de 9 GB a ~450 MB, un 95% menos de tráfico por la VPN).
3. **Resiliencia:** No depende de que la conexión remota a MySQL esté abierta durante 40 minutos; solo requiere una petición HTTP POST al terminar la compresión.

---

## 1. Requisitos
- **Python 3.7 o superior** instalado en el servidor del hospital.
- **mysqldump** disponible en el sistema.
- Acceso de red hacia el servidor central de LIDiag (URL configurada en `api_url`).

No requiere librerías adicionales con `pip` (utiliza únicamente la biblioteca estándar de Python).

---

## 2. Configuración
1. Copia la carpeta `dcm_agent` al servidor de la sede (ejemplo: `/opt/lidiag_backup/` en Linux o `C:\lidiag_backup\` en Windows).
2. Copia el archivo `config.json.example` a `config.json`:
   ```bash
   cp config.json.example config.json
   ```
3. Edita `config.json` con los valores de la sede:
   ```json
   {
     "sede_id": 1,
     "db_host": "127.0.0.1",
     "db_port": 3306,
     "db_name": "pacsdb",
     "db_user": "root",
     "db_password": "tu_password",
     "mysqldump_path": "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe",
     "api_url": "https://tuservidor-lidiag.com/api/backups/upload-dcm",
     "api_token": "lidiag_backup_secure_token_2026",
     "temp_dir": "./temp_backups",
     "keep_local": false
   }
   ```
   > **Nota para servidores Windows:** Si tienes MySQL instalado como servicio en `Program Files`, especifica la ruta completa a `mysqldump.exe` en `mysqldump_path` usando doble barra invertida (`\\`), por ejemplo: `C:\\Program Files\\MySQL\\MySQL Server 5.7\\bin\\mysqldump.exe` o `C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe`. Si se deja vacío, el script buscará automáticamente en las rutas estándar del sistema.

---

## 3. Prueba Manual
Ejecuta el script desde la terminal:
```bash
python3 backup_dcm_agent.py
```

O pasando parámetros por argumentos:
```bash
python3 backup_dcm_agent.py --sede 1 --db-name pacsdb --api-url https://tuservidor-lidiag.com/api/backups/upload-dcm
```

---

## 4. Programación Automática

### En Linux (crontab):
Para ejecutar diariamente a las 2:00 AM:
```bash
crontab -e
```
Agrega la siguiente línea:
```cron
0 2 * * * /usr/bin/python3 /opt/lidiag_backup/backup_dcm_agent.py --config /opt/lidiag_backup/config.json >> /var/log/lidiag_backup.log 2>&1
```

### En Windows (Programador de Tareas):

#### Opción A — Instalación automática en 1 clic (Recomendada):
1. Copia toda la carpeta `dcm_agent` al servidor del hospital (ej: `C:\lidiag_backup\`).
2. Configura tu `config.json`.
3. Haz **clic derecho** sobre `instalar_tarea_programada.bat` y selecciona **"Ejecutar como administrador"**.
4. ¡Listo! La tarea `LIDiag_Local_DCM_Backup` quedará programada para las **02:00 AM** con permisos de sistema (`SYSTEM`) para que corra incluso sin un usuario conectado.

#### Opción B — Configuración manual en la interfaz gráfica (taskschd.msc):
1. Presiona `Win + R`, escribe `taskschd.msc` y presiona Enter.
2. En el panel derecho haz clic en **"Crear tarea básica..."** (o "Crear tarea...").
3. **General:**
   - Nombre: `LIDiag_Local_DCM_Backup`
   - Seleccionar: *"Ejecutar tanto si el usuario inició sesión como si no"*
   - Marcar: *"Ejecutar con los privilegios más altos"*
4. **Desencadenadores (Triggers):**
   - Nuevo desencadenador $\rightarrow$ *Diariamente* $\rightarrow$ Hora: `02:00:00 AM`.
5. **Acciones (Actions):**
   - Nueva acción $\rightarrow$ *Iniciar un programa*.
   - **Programa o script:** `C:\lidiag_backup\ejecutar_backup.bat` (o `python.exe`).
   - Si usas `python.exe` directo:
     - Argumentos: `backup_dcm_agent.py --config config.json`
     - Iniciar en (Start in): `C:\lidiag_backup\`
6. Guarda la tarea y escribe la contraseña de administrador si te la solicita.

> Los logs de cada ejecución automática se guardarán en `backup_agent.log` dentro de la misma carpeta.
