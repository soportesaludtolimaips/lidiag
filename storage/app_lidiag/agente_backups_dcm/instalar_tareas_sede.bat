@echo off
setlocal EnableExtensions
title LIDiag - Instalador de Tareas Automatizadas PACS DCM

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag - Instalador de Tareas Automatizadas PACS DCM por Sede
echo =========================================================================
echo.

REM 1. Verificar y auto-elevar permisos de Administrador si es necesario
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ADVERTENCIA] Este instalador requiere ejecutarse como Administrador.
    echo Intentando solicitar permisos elevados...
    powershell -Command "Start-Process cmd -ArgumentList '/k \"\"%~f0\"\"' -Verb RunAs" >nul 2>&1
    if %errorLevel% neq 0 (
        echo.
        echo Por favor ejecuta este script haciendo:
        echo Clic derecho en instalar_tareas_sede.bat -^> "Ejecutar como administrador"
        echo O abre Simbolo del sistema como Administrador y vuelve a ejecutarlo.
        echo.
        pause
    )
    exit /b
)

REM 2. Verificar existencia de config.json
if not exist "%~dp0config.json" (
    echo [AVISO] No se encontro "config.json" en: %~dp0
    if exist "%~dp0config.json.example" (
        echo Recuerda copiar "config.json.example" a "config.json" y configurar
        echo los parametros de conexion de la sede para que los agentes operen.
    ) else (
        echo Recuerda configurar "config.json" con los parametros de la sede.
    )
    echo.
)

REM 3. Definicion de nombres y rutas de cada agente
set "TASK_DB=LIDiag_Backup_DB_DCM"
set "BAT_DB=%~dp0ejecutar_backup_db.bat"

set "TASK_IMG=LIDiag_Sync_Imagenes_DCM"
set "BAT_IMG=%~dp0ejecutar_sync_imagenes.bat"

set "TASK_DOSI=LIDiag_Dosimetria_Edge"
set "BAT_DOSI=%~dp0ejecutar_dosimetria.bat"

set "TASK_DISK=LIDiag_Disk_Monitor_DCM"
set "BAT_DISK=%~dp0ejecutar_disk_monitor.bat"

REM 4. Validar existencia de scripts ejecutables
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

REM Limpiar tareas con nombres antiguos en caso de existir
schtasks /delete /tn "LiDiag_Backup_DB_DCM" /f >nul 2>&1

echo Registrando las 4 tareas operativas en el Programador de Tareas:
echo -------------------------------------------------------------------------

REM TAREA 1: Respaldo MySQL diario de base de datos DCM (02:00 AM)
echo [1/4] Registrando tarea de Base de Datos MySQL: %TASK_DB%...
schtasks /create /tn "%TASK_DB%" /tr "\"%BAT_DB%\"" /sc DAILY /st 02:00 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DB%" /tr "%BAT_DB%" /sc DAILY /st 02:00 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada diariamente a las 02:00 AM bajo SYSTEM.
) else (
    echo       [ERROR] No se pudo crear la tarea %TASK_DB%.
)

echo.
REM TAREA 2: Sincronizacion continua de imagenes DICOM (Inicio de Windows ONSTART)
echo [2/4] Registrando tarea de Sincronizacion Continua de Imagenes: %TASK_IMG%...
schtasks /create /tn "%TASK_IMG%" /tr "\"%BAT_IMG%\"" /sc ONSTART /delay 0001:00 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_IMG%" /tr "\"%BAT_IMG%\"" /sc ONSTART /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_IMG%" /tr "%BAT_IMG%" /sc ONSTART /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada para iniciar con Windows al arranque ONSTART bajo SYSTEM.
) else (
    echo       [ERROR] No se pudo crear la tarea %TASK_IMG%.
)

echo.
REM TAREA 3: Extraccion y telemetria de Dosimetria Radiologica Edge (Cada 1 hora a las :15)
echo [3/4] Registrando tarea de Dosimetria Radiologica Edge: %TASK_DOSI%...
schtasks /create /tn "%TASK_DOSI%" /tr "\"%BAT_DOSI%\"" /sc HOURLY /mo 1 /st 00:15 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DOSI%" /tr "\"%BAT_DOSI%\"" /sc HOURLY /mo 1 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DOSI%" /tr "%BAT_DOSI%" /sc HOURLY /mo 1 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada para ejecutarse cada 1 hora bajo SYSTEM.
) else (
    echo       [ERROR] No se pudo crear la tarea %TASK_DOSI%.
)

echo.
REM TAREA 4: Auditoria y telemetria de Discos y Almacenamiento PACS (Cada 30 min a las :05)
echo [4/4] Registrando tarea de Monitoreo de Almacenamiento PACS: %TASK_DISK%...
schtasks /create /tn "%TASK_DISK%" /tr "\"%BAT_DISK%\"" /sc MINUTE /mo 30 /st 00:05 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DISK%" /tr "\"%BAT_DISK%\"" /sc MINUTE /mo 30 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% neq 0 (
    schtasks /create /tn "%TASK_DISK%" /tr "%BAT_DISK%" /sc MINUTE /mo 30 /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1
)
if %errorLevel% equ 0 (
    echo       [OK] Programada para ejecutarse cada 30 minutos bajo SYSTEM.
) else (
    echo       [ERROR] No se pudo crear la tarea %TASK_DISK%.
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
echo [COMPLETADO] Las 4 tareas operan de forma autonoma bajo SYSTEM.
echo (Nota: ejecutar_restore_imagenes.bat es una herramienta manual para
echo  recuperacion de desastres y no requiere tarea programada).
echo =========================================================================
echo.

REM Iniciar sincronizacion de imagenes e informe inicial de discos en segundo plano
schtasks /run /tn "%TASK_DISK%" >nul 2>&1
schtasks /run /tn "%TASK_IMG%" >nul 2>&1
echo [OK] Servicios y telemetria inicial puestos en marcha en segundo plano.
echo.
echo Instalacion completada con exito. Presiona cualquier tecla para salir.
pause >nul