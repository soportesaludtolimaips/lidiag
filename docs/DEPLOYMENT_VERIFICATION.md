# Guía de Despliegue y Verificación de Infraestructura — LIDiag

**Sistema:** LIDiag Medical Imaging System  
**Entorno Principal:** Windows + Laragon (PHP 8.3 / MySQL 8) + Vite Dev Server  
**Fecha:** 2026-10-20  
**Autor:** Jhon Javer Lozano Arce  

---

## 1. Requisitos de Infraestructura y Entorno

| Componente | Versión / Estado Requerido | Observación |
| :--- | :--- | :--- |
| **PHP** | `>= 8.3.30` | Extensiones activas: `pdo_mysql`, `sqlsrv`, `pdo_sqlsrv`, `mbstring`, `fileinfo`, `curl`, `openssl`, `zip`. |
| **MySQL** | `MySQL >= 8.0` o `MariaDB >= 10.5` | Motor `InnoDB` con soporte para transacciones seguras y claves foráneas. |
| **Node.js** | `>= 20.x` / npm | Requerido para compilación de activos Vue 3 + Vite. |
| **FastAPI + Whisper** | Python 3.10+ en `http://localhost:8001` | Transcripción de audios clínicos en `storage/app_lidiag/transcriptor/`. |
| **PACS Dcm4chee** | Dcm4chee Archive + Visor OHIF / Oviyam | Conexión C-MOVE/C-STORE e integración DICOM web. |
| **HIS / SAHI** | SQL Server | Sincronización de agendas, atenciones y pacientes. |
| **Bot Telegram** | Telegram Bot API / Polling o Webhook | Asistente de producción, reportes hospitalarios y monitor de discos PACS. |

---

## 2. Checklist de Despliegue Paso a Paso

### Paso 1: Dependencias y Variables de Entorno
```powershell
# Instalar dependencias de PHP sin scripts de desarrollo si es producción
composer install --no-dev --optimize-autoloader

# Verificar presencia de archivo .env
Test-Path .env
```

Asegurar las siguientes claves críticas en `.env`:
- `APP_ENV=production` (o `local` para staging)
- `APP_KEY=base64:...`
- `DB_CONNECTION=mysql`
- `TELEGRAM_BOT_TOKEN=...`
- `TELEGRAM_ALLOWED_CHAT_IDS=...`
- `TRANSCRIPTOR_TIMEOUT=300`

### Paso 2: Base de Datos y Migraciones
```powershell
# Ejecutar migraciones pendientes
php artisan migrate --force

# Limpieza de caché de permisos Spatie (utilizar el comando seguro del proyecto)
php artisan permissions:cache-reset
```

### Paso 3: Almacenamiento y Enlaces Simbólicos
```powershell
# Generar los enlaces simbólicos de storage si no existen
php artisan storage:link
```
Verificar que los siguientes directorios en `storage/app/` cuenten con permisos de escritura:
- `storage/app/audios/`
- `storage/app/backups/`
- `storage/app/firmas_medicos/`
- `storage/app/reporte_lecturas/`
- `storage/app/files_help_desk/`

### Paso 4: Compilación de Frontend
```powershell
# Instalar dependencias de Node
npm install

# Compilar activos de producción optimizados
npm run build
```

### Paso 5: Optimización de Framework
```powershell
# Cachear configuración, rutas y vistas
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. Verificación de Salud del Sistema (Smoke Tests)

Una vez completado el despliegue, ejecutar las siguientes validaciones:

1. **Suite de Pruebas Automatizadas:**
   ```powershell
   php vendor/bin/phpunit
   ```
   *Debe reportar 30 tests pasando con 0 errores y 0 fallos.*

2. **Diagnóstico de Conectividad con Telegram:**
   ```powershell
   php artisan telegram:poll --once
   ```
   *Debe confirmar conexión con `@salud_tolima_bot` y devolver status exitoso.*

3. **Verificación de Respaldo de Base de Datos:**
   ```powershell
   php artisan lidiag:backup --only-db
   ```
   *Debe generar el `.zip` en `storage/app/backups/` con su correspondiente archivo `.sha256`.*

4. **Monitoreo de Discos PACS:**
   Verificar que en cada servidor hospitalario el script `storage/app_lidiag/dcm_disk_monitor_telegram/iniciar_bot.bat` se encuentre en ejecución o programado en el Programador de Tareas de Windows.
