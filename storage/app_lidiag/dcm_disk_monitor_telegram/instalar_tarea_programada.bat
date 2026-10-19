@echo off
chcp 65001 > nul
setlocal enabledelayedexpansion
title Instalar Tarea Programada - Monitor de Discos DCM

echo ==================================================================
echo   INSTALADOR DE TAREA PROGRAMADA (WINDOWS TASK SCHEDULER)
echo   Monitor de Espacio en Discos DICOM con Alertas a Telegram
echo ==================================================================
echo.

set "SCRIPT_DIR=%~dp0"
set "PYTHON_SCRIPT=%SCRIPT_DIR%dcm_disk_monitor.py"
set "TASK_NAME=LIDiag_DCM_Disk_Monitor"

where python >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] No se encontro 'python' en el PATH del sistema.
    echo Asegurate de que Python 3.7+ este instalado y agregado al PATH.
    echo.
    pause
    exit /b 1
)

for /f "delims=" %%I in ('where python') do (
    set "PYTHON_EXE=%%I"
    goto :found_python
)

:found_python
echo [OK] Python localizado en: "%PYTHON_EXE%"
echo [OK] Script localizado en: "%PYTHON_SCRIPT%"
echo.

echo Se creara una Tarea Programada en Windows para ejecutar la revision
echo de discos periodicamente (Modo silencioso --check-once).
echo.
echo Nombre de la tarea: %TASK_NAME%
echo Frecuencia: Cada 1 hora
echo.

set /p CONFIRMAR="Deseas proceder con la instalacion? (S/N): "
if /i not "%CONFIRMAR%"=="S" (
    echo Instalacion cancelada por el usuario.
    pause
    exit /b 0
)

echo.
echo Registrando tarea en Windows Task Scheduler...
schtasks /create /tn "%TASK_NAME%" /tr "\"%PYTHON_EXE%\" \"%PYTHON_SCRIPT%\" --check-once" /sc HOURLY /mo 1 /ru "SYSTEM" /f >nul 2>&1

if %errorlevel% neq 0 (
    echo [AVISO] No se pudo crear con usuario SYSTEM (requiere Ejecutar como Administrador).
    echo Reintentando creacion con el usuario de sesion actual...
    schtasks /create /tn "%TASK_NAME%" /tr "\"%PYTHON_EXE%\" \"%PYTHON_SCRIPT%\" --check-once" /sc HOURLY /mo 1 /f
)

if %errorlevel% equ 0 (
    echo.
    echo ==================================================================
    echo [EXITO] Tarea '%TASK_NAME%' instalada correctamente.
    echo Se ejecutara cada 1 hora para vigilar los discos y alertar a Telegram.
    echo ==================================================================
) else (
    echo.
    echo [ERROR] No se pudo registrar la tarea. Por favor ejecuta este .bat
    echo haciendo clic derecho y seleccionando 'Ejecutar como Administrador'.
)

echo.
pause
