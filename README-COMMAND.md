# 📖 LIDiag — Catálogo Maestro de Comandos y Automatizaciones

> **Ecosistema LIDiag:** Laravel (PHP 8.4) + MySQL + SQL Server (SAHI) + Dcm4chee PACS + Vue 3 + FastAPI Whisper + Agentes Periféricos Python en Windows Server.

Este manual consolida **todos los comandos personalizados** (`php artisan`, scripts periféricos en Python, ejecutables `.bat` y tareas programadas) desarrollados para la plataforma asistencial, integraciones hospitalarias, seguridad, respaldos y monitoreo.

---

## 📑 Tabla de Contenidos

1. [🩺 Interoperabilidad HIS / SAHI (SQL Server) & Modality Worklist](#1-interoperabilidad-his--sahi-sql-server--modality-worklist)
2. [☢️ Dosimetría Radiológica y Protección al Paciente (Res. 482 / 2018)](#2-dosimetría-radiológica-y-protección-al-paciente-res-482--2018)
3. [📦 Respaldos Centrales y Portal de Pacientes](#3-respaldos-centrales-y-portal-de-pacientes)
4. [📲 Comunicaciones, WhatsApp y Telegram](#4-comunicaciones-whatsapp-y-telegram)
5. [🔐 Seguridad, Control de Acceso (RBAC Spatie) y Sesiones](#5-seguridad-control-de-acceso-rbac-spatie-y-sesiones)
6. [🧹 Normalización, Mantenimiento y Seeders](#6-normalización-mantenimiento-y-seeders)
7. [⏱️ Programador de Tareas Automáticas (Laravel Scheduler)](#7-programador-de-tareas-automáticas-laravel-scheduler)
8. [🖥️ Agentes Periféricos y Scripts de Sedes Hospitalarias (Edge Python & Batch)](#8-agentes-periféricos-y-scripts-de-sedes-hospitalarias-edge-python--batch)
9. [🎙️ Microservicio de Transcripción de Voz (Whisper STT + FastAPI)](#9-microservicio-de-transcripción-de-voz-whisper-stt--fastapi)
10. [🛠️ Herramientas de Desarrollo, Calidad de Código y Compilación](#10-herramientas-de-desarrollo-calidad-de-código-y-compilación)

---

## 1. 🩺 Interoperabilidad HIS / SAHI (SQL Server) & Modality Worklist

### `sahi:test`
* **Clase:** `App\Console\Commands\SahiTestConnectionCommand`
* **Propósito:** Diagnostica y audita la conectividad de red (TCP socket), latencia de conexión PDO e integridad de las tablas maestras de SAHI (`admAtencion`, `admCliente`, `proProducto`, `HceEsquema`, `sisTabla`).
* **Sintaxis:**
  ```bash
  php artisan sahi:test [--timeout=5]
  ```
* **Ejemplos:**
  ```bash
  # Prueba estándar con 5 segundos de espera
  php artisan sahi:test

  # Prueba extendida con 15 segundos para enlaces WAN lentos
  php artisan sahi:test --timeout=15
  ```

---

### `estudios:sahi-backfill`
* **Clase:** `App\Console\Commands\EstudiosSahiBackfillDatosCommand`
* **Propósito:** Sincroniza y completa retroactivamente Pagador (EPS), Contrato, Plan, Servicio y Médico que ordenó desde la base de datos de SAHI hacia los estudios almacenados en LIDiag.
* **Sintaxis:**
  ```bash
  php artisan estudios:sahi-backfill [--atencion=] [--estudio-id=] [--days=] [--day=] [--from=] [--to=] [--limit=100] [--all] [--force]
  ```
* **Opciones Principales:**
  - `--atencion=NUM`: Sincroniza una atención clínica puntual.
  - `--estudio-id=ID`: Sincroniza un estudio específico de LIDiag.
  - `--days=N`: Filtra estudios de los últimos N días (ej: 30 o 365).
  - `--from=YYYY-MM-DD --to=YYYY-MM-DD`: Rango de fechas cerrado.
  - `--limit=N`: Cantidad máxima de registros por lote (por defecto 100; `0` para ilimitado).
  - `--all`: Procesa todos los registros sin límite.
  - `--force`: Sobrescribe los datos incluso si el estudio ya tiene EPS o contrato asignado.
* **Ejemplos:**
  ```bash
  # Backfill de una atención específica
  php artisan estudios:sahi-backfill --atencion=7017456

  # Backfill de los últimos 30 días (límite 100)
  php artisan estudios:sahi-backfill --days=30 --limit=100

  # Forzar actualización en un rango anual
  php artisan estudios:sahi-backfill --from=2026-01-01 --to=2026-11-10 --force --all
  ```

---

### `estudios:sahi-enviar-rango`
* **Clase:** `App\Console\Commands\EstudiosSahiEnviarRangoCommand`
* **Propósito:** Despacha en lote hacia SAHI (SQL Server) los resultados e informes de estudios radiológicos transcritos dentro de un rango de fechas.
* **Sintaxis:**
  ```bash
  php artisan estudios:sahi-enviar-rango [from] [to] [--from=] [--to=] [--sede=] [--field=fechor_trascrito] [--force] [--dry-run]
  ```
* **Opciones Principales:**
  - `--sede=ID`: Limita el despacho a una sede hospitalaria concreta.
  - `--field=CAMPO`: Campo de fecha evaluado (`fechor_trascrito`, `fec_estudio`, `created_at`, `all`).
  - `--force`: Reenvía incluso estudios con estado previo `ENVIADO`.
  - `--dry-run`: Simula la consulta y conteo sin insertar en SAHI.
* **Ejemplos:**
  ```bash
  # Simulación previa para un rango de 3 días
  php artisan estudios:sahi-enviar-rango 2026-10-28 2026-10-30 --dry-run

  # Enviar estudios transcritos de la Sede 1 (Espinal)
  php artisan estudios:sahi-enviar-rango 2026-11-01 2026-11-05 --sede=1
  ```

---

### `estudios:sahi-reconcile`
* **Clase:** `App\Console\Commands\EstudiosSahiReconcileCommand`
* **Propósito:** Reconcilia envíos de radiología que quedaron en estado transitorio (`ENVIANDO`) o con errores recuperables de red, reintentando su confirmación en SAHI.
* **Sintaxis:**
  ```bash
  php artisan estudios:sahi-reconcile [--id=] [--estudio-id=] [--minutes=5] [--limit=50]
  ```
* **Ejemplos:**
  ```bash
  # Ejecución estándar (programada cada 5 minutos en scheduler)
  php artisan estudios:sahi-reconcile --minutes=5 --limit=50

  # Reconciliar un estudio específico
  php artisan estudios:sahi-reconcile --estudio-id=212636
  ```

---

### `ecografias:sahi-reconcile`
* **Clase:** `App\Console\Commands\EcografiasSahiReconcileCommand`
* **Propósito:** Reconcilia reportes de ecografías atascados en estado `ENVIANDO`, consultando si ya fueron impactados en SAHI para marcarlos como `ENVIADO` o devolverlos a reintento.
* **Sintaxis:**
  ```bash
  php artisan ecografias:sahi-reconcile [--minutes=10] [--limit=200]
  ```
* **Ejemplos:**
  ```bash
  # Reconciliar ecografías de los últimos 10 minutos (programado cada 10 min)
  php artisan ecografias:sahi-reconcile --minutes=10 --limit=200
  ```

---

### `worklist:reconcile`
* **Clase:** `App\Console\Commands\WorklistReconcileCommand`
* **Propósito:** Reconcilia las órdenes de la lista de trabajo DICOM (Modality Worklist) con las imágenes efectivamente recibidas y almacenadas en el PACS Dcm4chee.
* **Sintaxis:**
  ```bash
  php artisan worklist:reconcile [--sede=]
  ```
* **Ejemplos:**
  ```bash
  # Reconciliación global (programada cada 5 minutos)
  php artisan worklist:reconcile

  # Reconciliación forzada para una sede específica (ej. Sede 4 - Honda)
  php artisan worklist:reconcile --sede=4
  ```

---

## 2. ☢️ Dosimetría Radiológica y Protección al Paciente (Res. 482 / 2018)

### `dosimetria:sync-estudios`
* **Clase:** `App\Console\Commands\DosimetriaSyncEstudiosCommand`
* **Propósito:** Extrae, normaliza y puebla la telemetría dosimétrica integral de todas las modalidades emisoras de radiación ionizante (Tomografía Computarizada CT, Radiografía Digital DX/CR, Fluoroscopia) desde la tabla de estudios hacia `medicion_dosis_radiacion`.
* **Sintaxis:**
  ```bash
  php artisan dosimetria:sync-estudios [--sede=] [--limite=]
  ```
* **Ejemplos:**
  ```bash
  # Sincronización masiva de hasta 500 estudios
  php artisan dosimetria:sync-estudios --limite=500

  # Sincronización para la sede Espinal (ID 1)
  php artisan dosimetria:sync-estudios --sede=1 --limite=200
  ```

---

### `dosimetria:sync-mamografias`
* **Clase:** `App\Console\Commands\DosimetriaSyncMamografiaCommand`
* **Propósito:** Sincroniza y clasifica estudios de mamografía digital (DGM/MG), calculando y extrayendo la Dosis Glandular Media (AGD / Entrance Dose) requerida para vigilancia epidemiológica.
* **Sintaxis:**
  ```bash
  php artisan dosimetria:sync-mamografias [--sede=] [--limite=]
  ```
* **Ejemplos:**
  ```bash
  # Poblar mamografías pendientes en todas las sedes
  php artisan dosimetria:sync-mamografias

  # Sincronizar hasta 300 mamografías de una sede
  php artisan dosimetria:sync-mamografias --sede=1 --limite=300
  ```

---

### `dosimetria:token`
* **Clase:** `App\Console\Commands\DosimetriaTokenCommand`
* **Propósito:** Genera, renueva o revoca tokens permanentes de Laravel Sanctum (`SANCTUM_EXPIRATION=null`) para los agentes periféricos de dosimetría que operan en los servidores de los hospitales.
* **Sintaxis:**
  ```bash
  php artisan dosimetria:token [--user=] [--name=] [--revoke]
  ```
* **Ejemplos:**
  ```bash
  # Generar token para un usuario administrador
  php artisan dosimetria:token --user=administrador@administrador.com --name="Agente-Dosimetria-Espinal"

  # Revocar tokens previos del servicio
  php artisan dosimetria:token --user=administrador@administrador.com --revoke
  ```

---

## 3. 📦 Respaldos Centrales y Portal de Pacientes

### `lidiag:backup`
* **Clase:** `App\Console\Commands\LidiagBackupCommand`
* **Propósito:** Genera el respaldo del servidor central de LIDiag: volcado de la base de datos MySQL (`mysqldump`) y compresión independiente de los directorios de almacenamiento (`storage/app/public`, plantillas, consentimientos y firmas).
* **Sintaxis:**
  ```bash
  php artisan lidiag:backup [--only-db] [--only-files] [--destination=] [--retention=]
  ```
* **Opciones:**
  - `--only-db`: Respalda exclusivamente la base de datos.
  - `--only-files`: Respalda únicamente los archivos físicos.
  - `--destination=RUTA`: Carpeta de destino alternativa.
  - `--retention=DIAS`: Días de retención histórica antes de purgar zips antiguos.
* **Ejemplos:**
  ```bash
  # Backup completo nocturno (base de datos + archivos)
  php artisan lidiag:backup

  # Solo base de datos con retención de 15 días
  php artisan lidiag:backup --only-db --retention=15
  ```

---

### `lidiag:backup-dcm`
* **Clase:** `App\Console\Commands\LidiagBackupDcmCommand`
* **Propósito:** Orquesta y consolida los respaldos de las bases de datos de los PACS dcm4chee (`pacsdb`) de cada sede hospitalaria remota.
* **Sintaxis:**
  ```bash
  php artisan lidiag:backup-dcm [--sede=] [--destination=] [--retention=] [--timeout=]
  ```
* **Ejemplos:**
  ```bash
  # Respaldar todas las sedes hospitalarias configuradas
  php artisan lidiag:backup-dcm

  # Respaldar solo la sede Purificación (ID 2)
  php artisan lidiag:backup-dcm --sede=2
  ```

---

### `lidiag:enviar-paciente`
* **Clase:** `App\Console\Commands\LidiagEnviarPacienteCommand`
* **Propósito:** Genera los reportes PDF de estudios transcritos y los despacha al portal web de pacientes (`lidiag-reportes`).
* **Sintaxis:**
  ```bash
  php artisan lidiag:enviar-paciente [--desde=] [--hasta=] [--sede=] [--limite=] [--id=] [--reintentar=] [--dry-run]
  ```
* **Ejemplos:**
  ```bash
  # Despachar estudios transcritos de la jornada de hoy
  php artisan lidiag:enviar-paciente --desde=2026-11-10 --hasta=2026-11-10

  # Simular envío sin generar PDFs ni impactar el portal externo
  php artisan lidiag:enviar-paciente --limite=50 --dry-run

  # Reintentar lista de fallidos desde archivo plano
  php artisan lidiag:enviar-paciente --reintentar=C:\temp\fallidos.txt
  ```

---

## 4. 📲 Comunicaciones, WhatsApp y Telegram

### `citas:notificar`
* **Clase:** `App\Console\Commands\CitasNotificarCommand`
* **Propósito:** Envía recordatorios automáticos por WhatsApp a los pacientes con citas agendadas para el **día siguiente**, incluyendo fecha, hora, sede, médico y recomendaciones de preparación.
* **Sintaxis:**
  ```bash
  php artisan citas:notificar
  ```
* **Ejecución Programada:** Todos los días a las `08:00 AM`.

---

### `citas:notificar-2h`
* **Clase:** `App\Console\Commands\CitasNotificar2hCommand`
* **Propósito:** Envía un segundo recordatorio preventivo por WhatsApp a los pacientes cuya cita está programada dentro de las **próximas 2 horas**.
* **Sintaxis:**
  ```bash
  php artisan citas:notificar-2h
  ```
* **Ejecución Programada:** Cada 10 minutos mediante el scheduler.

---

### `telegram:daily-digest`
* **Clase:** `App\Console\Commands\TelegramDailyDigestCommand`
* **Propósito:** Genera y envía a los directores e ingenieros autorizados por Telegram el resumen consolidado matutino: estudios realizados ayer, represamientos pendientes por transcribir y semáforo asistencial de sedes.
* **Sintaxis:**
  ```bash
  php artisan telegram:daily-digest [--chat-id=]
  ```
* **Ejecución Programada:** Todos los días a las `07:30 AM`.
* **Ejemplos:**
  ```bash
  # Despacho automático a todos los administradores en lista blanca
  php artisan telegram:daily-digest

  # Enviar prueba a un Chat ID específico
  php artisan telegram:daily-digest --chat-id=123456789
  ```

---

### `telegram:poll`
* **Clase:** `App\Console\Commands\TelegramPollCommand`
* **Propósito:** Escucha y procesa comandos interactivos de Telegram (`/start`, `/ayuda`, `/resumen`, `/represamiento`, `/discos`) mediante Long-Polling en entornos locales o gestiona la configuración del Webhook oficial.
* **Sintaxis:**
  ```bash
  php artisan telegram:poll [--once] [--diagnose] [--delete-webhook] [--set-webhook=URL]
  ```
* **Ejemplos:**
  ```bash
  # Diagnóstico completo de conectividad hacia api.telegram.org
  php artisan telegram:poll --diagnose

  # Iniciar escucha en bucle en servidor de desarrollo local
  php artisan telegram:poll

  # Registrar webhook en producción
  php artisan telegram:poll --set-webhook=https://lidiag.com/api/telegram/webhook

  # Eliminar webhook para permitir pruebas locales
  php artisan telegram:poll --delete-webhook
  ```

---

## 5. 🔐 Seguridad, Control de Acceso (RBAC Spatie) y Sesiones

### `permissions:audit`
* **Clase:** `App\Console\Commands\AuditPermissions`
* **Propósito:** Audita exhaustivamente todos los permisos usados en código PHP (Controllers, Requests, Policies, Middleware) y vistas Blade vs los permisos existentes en la base de datos (Spatie Permissions).
* **Sintaxis:**
  ```bash
  php artisan permissions:audit [--guard=web] [--find=] [--fail] [--show-unused] [--paths=*]
  ```
* **Ejemplos:**
  ```bash
  # Auditoría estándar del proyecto
  php artisan permissions:audit

  # Buscar la existencia y guard de un permiso puntual
  php artisan permissions:audit --find="estudios.transcribir"

  # Retornar código de error (exit code != 0) si faltan permisos (ideal para CI/CD)
  php artisan permissions:audit --fail

  # Mostrar permisos en BD que no están referenciados en ningún archivo de código
  php artisan permissions:audit --show-unused
  ```

---

### `permissions:cache-reset`
* **Clase:** `App\Console\Commands\ResetPermissionCacheSafe`
* **Propósito:** Limpia la memoria caché de Spatie Permissions de forma segura, evitando la excepción nativa `Unable to flush cache` cuando el store de caché no tiene la clave persistida.
* **Sintaxis:**
  ```bash
  php artisan permissions:cache-reset
  ```

---

### `permissions:user-check`
* **Clase:** `App\Console\Commands\CheckUserPermission`
* **Propósito:** Herramienta de diagnóstico rápido para consultar los roles y permisos efectivos (directos y heredados) asignados a un usuario.
* **Sintaxis:**
  ```bash
  php artisan permissions:user-check [user] [--search=] [--perm=]
  ```
* **Ejemplos:**
  ```bash
  # Ver todos los roles y permisos del usuario administrador
  php artisan permissions:user-check administrador@administrador.com

  # Verificar si un usuario cuenta con un permiso específico
  php artisan permissions:user-check 12345678 --perm="citas.crear"

  # Buscar usuarios por término
  php artisan permissions:user-check --search="radiologo"
  ```

---

### `sync:missing-permissions`
* **Clase:** `App\Console\Commands\SyncMissingPermissions`
* **Propósito:** Detecta permisos declarados en código que aún no existen en la base de datos y los crea automáticamente en la tabla `permissions` bajo el guard `web`, asignándolos de inmediato al rol `Super Administrador`.
* **Sintaxis:**
  ```bash
  php artisan sync:missing-permissions [--dry-run]
  ```
* **Ejemplos:**
  ```bash
  # Simular permisos faltantes sin insertar
  php artisan sync:missing-permissions --dry-run

  # Crear y sincronizar permisos faltantes en caliente
  php artisan sync:missing-permissions
  ```

---

### `sessions:prune`
* **Clase:** `App\Console\Commands\PruneSessions`
* **Propósito:** Purgar sesiones expiradas o inactivas de la tabla `sessions` para mantener ligera la base de datos y evitar degradación en MySQL.
* **Sintaxis:**
  ```bash
  php artisan sessions:prune [--hours=24] [--user-hours=168] [--dry-run]
  ```
* **Ejecución Programada:** Cada 1 hora en scheduler.

---

### `notifications:prune`
* **Clase:** `App\Console\Commands\PruneNotifications`
* **Propósito:** Elimina notificaciones leídas de la tabla `notifications` con antigüedad superior a los días de retención definidos (por defecto 90 días).
* **Sintaxis:**
  ```bash
  php artisan notifications:prune [--days=90] [--dry-run]
  ```
* **Ejecución Programada:** Diario a medianoche.

---

## 6. 🧹 Normalización, Mantenimiento y Seeders

### `seed:faltantes`
* **Clase:** `App\Console\Commands\SeedFaltantesCommand`
* **Propósito:** Evalúa de forma inteligente qué catálogos, roles, permisos o parámetros maestros no han sido sembrados y ejecuta exclusivamente los seeders requeridos, evitando duplicidad o reinserción de datos existentes.
* **Sintaxis:**
  ```bash
  php artisan seed:faltantes [--audit] [--force]
  ```
* **Ejemplos:**
  ```bash
  # Modo auditoría: lista qué seeders faltan por sembrar sin tocar la BD
  php artisan seed:faltantes --audit

  # Sembrar de forma segura los catálogos y permisos faltantes
  php artisan seed:faltantes

  # Forzar ejecución integral de todos los seeders registrados
  php artisan seed:faltantes --force
  ```

---

### `ecografias:normalize-estados`
* **Clase:** `App\Console\Commands\EcografiasNormalizeEstadosCommand`
* **Propósito:** Corrige inconsistencias entre el estado maestro de la ecografía (`ecografias.estado`) y sus exámenes hijos (`ecografias_productos.estado`), normalizando huérfanos o desalineaciones.
* **Sintaxis:**
  ```bash
  php artisan ecografias:normalize-estados [--dry-run] [--limit=] [--chunk=100]
  ```
* **Ejemplos:**
  ```bash
  # Simulación de registros que serían normalizados
  php artisan ecografias:normalize-estados --dry-run

  # Ejecutar normalización efectiva
  php artisan ecografias:normalize-estados
  ```

---

### `firmas:limpiar`
* **Clase:** `App\Console\Commands\LimpiarFirmasMedicos`
* **Propósito:** Audita el directorio `storage/app/firmas_medicos` y elimina imágenes huérfanas de firmas médicas que no estén vinculadas a ningún médico activo en la base de datos.
* **Sintaxis:**
  ```bash
  php artisan firmas:limpiar [--dry-run] [--force]
  ```

---

### `sync-usuarios`
* **Clase:** `App\Console\Commands\SyncUsuarios`
* **Propósito:** Sincroniza personal asistencial y plantillas de transcripción de ecografías desde la base de datos origen de importación (`mysql_importar_dicom`) hacia LIDiag.
* **Sintaxis:**
  ```bash
  php artisan sync-usuarios
  ```

---

### `sync:dicom-ecografias`
* **Clase:** `App\Console\Commands\SyncDicomEcografias`
* **Propósito:** Importa y sincroniza usuarios, entidades aseguradoras (EPS), catálogo de productos y registros históricos de ecografías desde la conexión externa `mysql_importar_dicom`.
* **Sintaxis:**
  ```bash
  php artisan sync:dicom-ecografias
  ```

---

## 7. ⏱️ Programador de Tareas Automáticas (Laravel Scheduler)

Todas las tareas periódicas están centralizadas en [`routes/console.php`](file:///c:/laragon/www/lidiag/routes/console.php) y protegidas con `->withoutOverlapping()` para evitar ejecuciones concurrentes colisionadas.

### 📅 Matriz de Tareas Programadas

| Comando | Periodicidad | Horario / Intervalo | Propósito Clínico / Operativo |
| :--- | :---: | :---: | :--- |
| `worklist:reconcile` | Cada 5 minutos | Continuo | Reconcilia órdenes de Worklist con estudios recibidos en PACS. |
| `estudios:sahi-reconcile` | Cada 5 minutos | Continuo | Reconcilia y reintenta envíos de radiología a SAHI. |
| `ecografias:sahi-reconcile` | Cada 10 minutos | Continuo | Reconcilia ecografías en estado `ENVIANDO` con SAHI. |
| `citas:notificar-2h` | Cada 10 minutos | Continuo | Recordatorios de citas por WhatsApp 2 horas antes de la atención. |
| `sessions:prune` | Cada 1 hora | Minuto :00 | Purga de sesiones inactivas en la tabla `sessions`. |
| `telegram:daily-digest` | Diario | `07:30 AM` | Envío de reporte de producción matutino consolidado. |
| `citas:notificar` | Diario | `08:00 AM` | Recordatorios de citas por WhatsApp para el día siguiente. |
| `lidiag:backup` | Diario | `02:00 AM` | Backup MySQL y storage del servidor central. |
| `lidiag:backup-dcm` | Diario | `02:30 AM` | Backup orquestado de las bases de datos PACS de sedes. |
| `notifications:prune` | Diario | Medianoche | Purga de notificaciones leídas con más de 90 días. |

### 🛠️ Comandos de Inspección del Scheduler
```bash
# Listar todas las tareas programadas con su próxima fecha y estado
php artisan schedule:list

# Ejecutar manualmente un ciclo del scheduler
php artisan schedule:run

# Dejar el scheduler corriendo en segundo plano (para entornos locales)
php artisan schedule:work
```

---

## 8. 🖥️ Agentes Periféricos y Scripts de Sedes Hospitalarias (Edge Python & Batch)

Ubicación principal en el repositorio:
[`storage/app_lidiag/agente_backups_dcm/`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/)

Estos componentes corren de forma autónoma en el servidor local de cada una de las 7 sedes hospitalarias bajo la cuenta `SYSTEM` de Windows.

### 📋 Los 5 Agentes Periféricos

1. **Respaldo de Base de Datos PACS (`backup_dcm_agent.py`):**
   - Lanzador: `ejecutar_backup_db.bat`
   - Tarea Windows: `LIDiag_Backup_DB_DCM` (Diario a las `02:00 AM`).
   - Función: Dump comprimido en ZIP de `pacsdb`, transferencia local a NAS QNAP y telemetría a Central.

2. **Sincronización Continua de Imágenes DICOM (`sync_dcm_images.py`):**
   - Lanzador: `ejecutar_sync_imagenes.bat`
   - Tarea Windows: `LIDiag_Sync_Imagenes_DCM` (`ONSTART` al arranque del servidor).
   - Función: Replicación multi-hilo (`robocopy /MT:8`) incremental de `D:\img_dcm` hacia NAS QNAP cada 180 segundos sin borrar jamás en destino.

3. **Agente Edge de Dosimetría Radiológica (`agente_dosimetria.py`):**
   - Lanzador: `ejecutar_dosimetria.bat`
   - Tarea Windows: `LIDiag_Dosimetria_Edge` (Cada 1 hora al minuto `:15`).
   - Función: Escaneo de cabeceras DICOM con PyDICOM, extracción de KVP, mA, mAs, DLP, CTDIvol y AGD, y envío HTTPS a LIDiag Central.

4. **Monitor de Espacio en Discos y Repositorios (`dcm_disk_monitor.py`):**
   - Lanzador: `ejecutar_disk_monitor.bat`
   - Tarea Windows: `LIDiag_Disk_Monitor_DCM` (Cada 30 minutos al minuto `:05`).
   - Función: Audita `C:\`, carpetas de backups y repositorios de imágenes; reporta telemetría a `/api/backups/telemetria` y alerta por Telegram.

5. **Recuperación ante Desastres (`restore_dcm_images.py`):**
   - Lanzador: `ejecutar_restore_imagenes.bat`
   - Uso: Manual asistido ante emergencias de disco o cambio de servidor.
   - Función: Descarga priorizada cronológica descendente ($2026 \to 2025 \dots$) con RTO asistencial menor a 3 horas.

### 🚀 Instaladores de Sede Automatizados
* [`install.bat`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/install.bat): Prepara el entorno Python, instala librerías (`pydicom`, `requests`, `mysql-connector-python`, `pymysql`) y crea directorios.
* [`instalar_tareas_sede.bat`](file:///c:/laragon/www/lidiag/storage/app_lidiag/agente_backups_dcm/instalar_tareas_sede.bat): Script 100% desatendido con auto-elevación UAC que registra las 4 tareas de Windows bajo `SYSTEM`.

---

## 9. 🎙️ Microservicio de Transcripción de Voz (Whisper STT + FastAPI)

Ubicación:
[`storage/app_lidiag/transcriptor/`](file:///c:/laragon/www/lidiag/storage/app_lidiag/transcriptor/)

* **Script:** `transcriptor_service.py`
* **Lanzador por lotes:** `iniciar_transcriptor.bat`
* **Tecnología:** FastAPI + OpenAI Whisper (modelo `base` corriendo en CPU local).
* **Puerto de Servicio:** `http://localhost:8001`
* **Endpoint:** `POST http://localhost:8001/transcribir`
* **Flujo Asistencial:**
  1. El radiólogo graba la nota de voz en el navegador web (Vue 3).
  2. Laravel Controller recibe el audio y lo comprime con FFmpeg a MP3 (64 kbps mono) en `audios/tmp/`.
  3. Laravel despacha el archivo por HTTP POST hacia el microservicio local en el puerto `8001`.
  4. Whisper transcribe el texto clínico y lo retorna en JSON (`{"texto": "..."}`).
  5. Laravel persiste la transcripción en la tabla `estudios_audios.transcripcion`.

---

## 10. 🛠️ Herramientas de Desarrollo, Calidad de Código y Compilación

En el entorno Laragon de Windows (PowerShell 5.1):

### 🐘 PHP & Estilo Laravel Pint
```powershell
# Probar estilo sin modificar archivos (preset Laravel oficial)
vendor\bin\pint.bat --test app/Services/MiServicio.php

# Aplicar formateo y corrección automática de estilo
vendor\bin\pint.bat app/Services/MiServicio.php

# Normalizar múltiples archivos modificados
vendor\bin\pint.bat app/Http/Controllers/EstudioController.php app/Services/EstudioService.php
```

### 🔬 Análisis Estático Larastan / PHPStan
```powershell
# Ejecutar análisis de tipos nivel 5 sobre archivos modificados
vendor\bin\phpstan.bat analyse app/Services/TeleRadiologiaService.php --memory-limit=1G
```

### 🎨 Frontend Vue 3 & Vite
```powershell
# Validar reglas de ESLint en un componente Vue
npx eslint resources/js/components/modulos/estudios/AsignarEstudioComponent.vue

# Formatear componente con Prettier
npx prettier --write resources/js/components/modulos/estudios/AsignarEstudioComponent.vue

# Compilar assets de producción (Obligatorio tras modificar .vue o .js)
npm run build

# Levantar servidor de desarrollo con Hot Reload (Vite HMR)
npm run dev
```

### 💡 Reglas Críticas del Repositorio para Desarrolladores
1. **Líneas de Fin (Line Endings):** `core.autocrlf=true` en Windows descarga archivos con CRLF, pero Pint y las herramientas exigen estrictamente **LF**. Si Pint reporta error únicamente por `line_ending`, normaliza a LF sin alterar lógica.
2. **Sin BOM en PowerShell:** Nunca uses `Set-Content -Encoding utf8` para archivos PHP en PowerShell 5.1 (inyecta BOM invisible y causa error fatal de PHP). Usa UTF-8 sin BOM.
3. **Scripts Temporales en Consola:** `php artisan tinker --execute` falla con barras invertidas (`\`). Crea scripts temporales en `C:\Users\Jhon\AppData\Local\Temp\opencode\script.php` y ejecútalos con `php`.
4. **Política de Git:** Nunca ejecutes `git add -A`. Añade siempre rutas explícitas de los archivos modificados para evitar subir volcados locales o Excel de pruebas.
