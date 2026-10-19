@echo off
setlocal
cd /d "%~dp0"

echo =========================================================
echo    Instalador de Tarea Programada - Agente DCM LIDiag
echo =========================================================
echo.

:: Verificar permisos de administrador
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Este instalador debe ejecutarse con privilegios de Administrador.
    echo Por favor haz clic derecho sobre este archivo y elige:
    echo   "Ejecutar como administrador"
    echo.
    pause
    exit /b 1
)

set SCRIPT_DIR=%~dp0
set RUNNER_BAT=%SCRIPT_DIR%ejecutar_backup.bat
set TASK_NAME=LIDiag_Local_DCM_Backup
set SCHEDULE_TIME=02:00

if not exist "%RUNNER_BAT%" (
    echo [ERROR] No se encontro el archivo %RUNNER_BAT%
    pause
    exit /b 1
)

echo Creando tarea programada: %TASK_NAME%
echo Horario: Diariamente a las %SCHEDULE_TIME% AM
echo Script: %RUNNER_BAT%
echo.

schtasks /create /tn "%TASK_NAME%" /tr "\"%RUNNER_BAT%\"" /sc daily /st %SCHEDULE_TIME% /ru "SYSTEM" /rl HIGHEST /f

if %ERRORLEVEL% EQU 0 (
    echo.
    echo =========================================================
    echo [OK] Tarea programada "%TASK_NAME%" creada con exito!
    echo =========================================================
    echo Horario programado: Todos los dias a las %SCHEDULE_TIME% AM.
    echo Nivel de privilegios: SYSTEM (maximos privilegios, no requiere sesion abierta).
    echo Registro de logs: %SCRIPT_DIR%backup_agent.log
) else (
    echo.
    echo [ERROR] Ocurrio un fallo al intentar registrar la tarea con schtasks.
)

echo.
pause
