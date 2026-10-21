@echo off
chcp 65001 >nul
title LIDiag - Instalador de Tareas Automatizadas PACS DCM

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag — Instalador de Tareas Automatizadas PACS DCM por Sede
echo =========================================================================
echo.

:: Verificar permisos de Administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ADVERTENCIA] Este instalador requiere privilegios de Administrador.
    echo Por favor, haz clic derecho y selecciona "Ejecutar como administrador".
    echo.
    pause
    exit /b 1
)

if not exist "config.json" (
    echo [ERROR] No se encontro config.json.
    echo Configura el archivo config.json antes de registrar las tareas.
    echo.
    pause
    exit /b 1
)

set "TASK_DB=LIDiag_Backup_DB_DCM"
set "BAT_DB=%~dp0ejecutar_backup_db.bat"

set "TASK_IMG=LIDiag_Sync_Imagenes_DCM"
set "BAT_IMG=%~dp0ejecutar_sync_imagenes.bat"

echo [1/2] Registrando tarea de Respaldo de Base de Datos MySQL (%TASK_DB%)...
schtasks /create /tn "%TASK_DB%" /tr "\"%BAT_DB%\"" /sc DAILY /st 02:00 /ru "SYSTEM" /rl HIGHEST /f

if %errorLevel% equ 0 (
    echo       [OK] Tarea de Base de Datos programada diariamente a las 02:00 AM.
) else (
    echo       [ERROR] No se pudo crear la tarea %TASK_DB%.
)

echo.
echo [2/2] Registrando tarea de Sincronizacion Continua de Imagenes (%TASK_IMG%)...
schtasks /create /tn "%TASK_IMG%" /tr "\"%BAT_IMG%\"" /sc ONSTART /ru "SYSTEM" /rl HIGHEST /f

if %errorLevel% equ 0 (
    echo       [OK] Tarea de Imagenes programada para iniciar con el sistema (ONSTART).
) else (
    echo       [ERROR] No se pudo crear la tarea %TASK_IMG%.
)

echo.
echo =========================================================================
echo [COMPLETADO] Las tareas fueron registradas con exito bajo la cuenta SYSTEM.
echo Ambas operan de forma 100%% autonoma sin requerir inicio de sesion de usuario.
echo =========================================================================
echo.
echo ¿Deseas iniciar la sincronizacion continua de imagenes ahora mismo? (S/N)
set /p "INICIAR=Opcion: "
if /i "%INICIAR%"=="S" (
    schtasks /run /tn "%TASK_IMG%"
    echo Sincronizacion de imagenes iniciada en segundo plano.
)

echo.
pause
