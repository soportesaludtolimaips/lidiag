@echo off
chcp 65001 >nul
title LIDiag - Instalador de Tareas Automatizadas PACS DCM

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag — Instalador de Tareas Automatizadas PACS DCM por Sede
echo =========================================================================
echo.

:: 1. Verificar y auto-elevar permisos de Administrador si es necesario
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [SOLICITANDO ELEVACION] Este instalador requiere privilegios de Administrador.
    echo Abriendo ventana con permisos elevados...
    powershell -Command "Start-Process cmd -ArgumentList '/c \"\"%~f0\"\"' -Verb RunAs"
    exit /b
)

:: 2. Verificar existencia de config.json
if not exist "%~dp0config.json" (
    echo [ADVERTENCIA] No se encontro "config.json" en: %~dp0
    if exist "%~dp0config.json.example" (
        echo Se detecto "config.json.example". Por favor copialo a "config.json" y configura
        echo los parametros de conexion de la sede antes de programar las tareas.
    ) else (
        echo Por favor crea y configura "config.json" con los parametros de la sede.
    )
    echo.
    echo Deseas continuar registrando las tareas de todos modos? (S/N)
    set /p "CONT_CFG=Opcion: "
    if /i not "%CONT_CFG%"=="S" (
        pause
        exit /b 1
    )
)

:: 3. Definicion de rutas absolutas de cada agente
set "TASK_DB=LIDiag_Backup_DB_DCM"
set "BAT_DB=%~dp0ejecutar_backup_db.bat"

set "TASK_IMG=LIDiag_Sync_Imagenes_DCM"
set "BAT_IMG=%~dp0ejecutar_sync_imagenes.bat"

set "TASK_DOSI=LIDiag_Dosimetria_Edge"
set "BAT_DOSI=%~dp0ejecutar_dosimetria.bat"

set "TASK_DISK=LIDiag_Disk_Monitor_DCM"
set "BAT_DISK=%~dp0ejecutar_disk_monitor.bat"

:: 4. Validar existencia fisica de los scripts ejecutables .bat
if not exist "%BAT_DB%" (
    echo [ERROR] No existe el script: %BAT_DB%
    pause
    exit /b 1
)
if not exist "%BAT_IMG%" (
    echo [ERROR] No existe el script: %BAT_IMG%
    pause
    exit /b 1
)
if not exist "%BAT_DOSI%" (
    echo [ERROR] No existe el script: %BAT_DOSI%
    pause
    exit /b 1
)
if not exist "%BAT_DISK%" (
    echo [ERROR] No existe el script: %BAT_DISK%
    pause
    exit /b 1
)

:: Limpiar tareas con nombres antiguos o variantes en caso de existir
schtasks /delete /tn "LiDiag_Backup_DB_DCM" /f >nul 2>&1

echo Registrando las 4 tareas operativas en el Programador de Tareas de Windows:
echo -------------------------------------------------------------------------

:: TAREA 1: Respaldo MySQL diario de base de datos DCM (02:00 AM)
echo [1/4] Registrando tarea de Base de Datos MySQL: %TASK_DB%...
schtasks /create /tn "%TASK_DB%" /tr "\"%BAT_DB%\"" /sc DAILY /st 02:00 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% equ 0 (
    echo       [OK] Programada diariamente a las 02:00 AM bajo SYSTEM.
) else (
    echo       [FALLO] Intentando sin comillas de escape...
    schtasks /create /tn "%TASK_DB%" /tr "%BAT_DB%" /sc DAILY /st 02:00 /ru "SYSTEM" /rl HIGHEST /f
)

echo.
:: TAREA 2: Sincronizacion continua de imagenes DICOM (Inicio del sistema ONSTART)
echo [2/4] Registrando tarea de Sincronizacion Continua de Imagenes: %TASK_IMG%...
schtasks /create /tn "%TASK_IMG%" /tr "\"%BAT_IMG%\"" /sc ONSTART /delay 0001:00 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_IMG%" /tr "\"%BAT_IMG%\"" /sc ONSTART /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada para iniciar con Windows (ONSTART) bajo SYSTEM.
) else (
    echo       [FALLO] Reintentando registro basico...
    schtasks /create /tn "%TASK_IMG%" /tr "%BAT_IMG%" /sc ONSTART /ru "SYSTEM" /rl HIGHEST /f
)

echo.
:: TAREA 3: Extraccion y telemetria de Dosimetria Radiologica Edge (Cada hora a las :15)
echo [3/4] Registrando tarea de Dosimetria Radiologica Edge: %TASK_DOSI%...
schtasks /create /tn "%TASK_DOSI%" /tr "\"%BAT_DOSI%\"" /sc HOURLY /mo 1 /st 00:15 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DOSI%" /tr "\"%BAT_DOSI%\"" /sc HOURLY /mo 1 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada para ejecutarse cada 1 hora bajo SYSTEM.
) else (
    echo       [FALLO] Reintentando registro basico...
    schtasks /create /tn "%TASK_DOSI%" /tr "%BAT_DOSI%" /sc HOURLY /mo 1 /ru "SYSTEM" /rl HIGHEST /f
)

echo.
:: TAREA 4: Auditoria y telemetria de Discos y Almacenamiento PACS (Cada 30 minutos a las :05)
echo [4/4] Registrando tarea de Monitoreo de Almacenamiento PACS: %TASK_DISK%...
schtasks /create /tn "%TASK_DISK%" /tr "\"%BAT_DISK%\"" /sc MINUTE /mo 30 /st 00:05 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DISK%" /tr "\"%BAT_DISK%\"" /sc MINUTE /mo 30 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada para ejecutarse cada 30 minutos bajo SYSTEM.
) else (
    echo       [FALLO] Reintentando registro basico...
    schtasks /create /tn "%TASK_DISK%" /tr "%BAT_DISK%" /sc MINUTE /mo 30 /ru "SYSTEM" /rl HIGHEST /f
)

echo.
echo =========================================================================
echo                  ESTADO DE LAS TAREAS EN WINDOWS
echo =========================================================================
schtasks /query /tn "%TASK_DB%" /fo TABLE /nh 2>nul
schtasks /query /tn "%TASK_IMG%" /fo TABLE /nh 2>nul
schtasks /query /tn "%TASK_DOSI%" /fo TABLE /nh 2>nul
schtasks /query /tn "%TASK_DISK%" /fo TABLE /nh 2>nul

echo =========================================================================
echo [COMPLETADO] Las 4 tareas operan de forma 100%% autonoma bajo SYSTEM.
echo (Nota: ejecutar_restore_imagenes.bat es una herramienta manual para
echo  recuperacion de desastres y no requiere tarea programada).
echo =========================================================================
echo.

echo ¿Deseas ejecutar ahora una prueba del Monitor de Discos para enviar telemetria? (S/N)
set /p "TEST_DISK=Opcion: "
if /i "%TEST_DISK%"=="S" (
    echo Ejecutando monitor de almacenamiento...
    call "%BAT_DISK%"
)

echo.
echo ¿Deseas iniciar la sincronizacion continua de imagenes ahora mismo? (S/N)
set /p "INICIAR=Opcion: "
if /i "%INICIAR%"=="S" (
    schtasks /run /tn "%TASK_IMG%" >nul 2>&1
    echo [OK] Tarea de sincronizacion de imagenes enviada a ejecucion en segundo plano.
)

echo.
echo Proceso finalizado. Puedes cerrar esta ventana.
pause
