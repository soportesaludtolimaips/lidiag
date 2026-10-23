@echo off
chcp 65001 > nul
REM =========================================================================
REM LIDiag - Instalador de Tarea Programada para Agente Edge de Dosimetría
REM =========================================================================

echo =========================================================
echo    INSTALACIÓN DE TAREA PROGRAMADA - AGENTE DOSIMETRÍA
echo =========================================================

net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Este script requiere privilegios de Administrador.
    echo Por favor haga clic derecho y seleccione "Ejecutar como administrador".
    pause
    exit /b 1
)

set SCRIPT_DIR=%~dp0
set BAT_PATH=%SCRIPT_DIR%ejecutar_dosimetria.bat
set TASK_NAME=LIDiag_Dosimetria_Edge

echo Configurando tarea programada '%TASK_NAME%'...
echo Ruta del script: %BAT_PATH%

REM Crear tarea para ejecutarse cada 1 hora todos los días
schtasks /create /tn "%TASK_NAME%" /tr "\"%BAT_PATH%\"" /sc HOURLY /mo 1 /ru "SYSTEM" /f

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [ÉXITO] Tarea programada instalada correctamente.
    echo Se ejecutará automáticamente cada hora para procesar la telemetría.
) else (
    echo.
    echo [ERROR] No se pudo crear la tarea programada. Código: %ERRORLEVEL%
)

echo =========================================================
pause
