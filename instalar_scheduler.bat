@echo off
chcp 65001 >nul
title LIDiag - Instalador de Scheduler y Backups Automaticos

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag — Instalador de Scheduler y Backups Automaticos (Windows)
echo =========================================================================
echo.

:: 1. Verificar privilegios de Administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ADVERTENCIA] Este instalador requiere privilegios de Administrador.
    echo Por favor, haz clic derecho sobre este archivo y selecciona:
    echo "Ejecutar como administrador".
    echo.
    pause
    exit /b 1
)

:: 2. Configurar nombres y rutas
set "TASK_NAME=LIDiag_Laravel_Scheduler"
set "SCRIPT_PYTHON=%~dp0scheduler_runner.py"

:: 3. Verificar que exista scheduler_runner.py
if not exist "%SCRIPT_PYTHON%" (
    echo [ERROR] No se encontro el archivo %SCRIPT_PYTHON%
    pause
    exit /b 1
)

:: 4. Detectar binario de Python
set "PYTHON_EXE="
where pythonw >nul 2>&1
if %errorLevel% equ 0 (
    for /f "tokens=*" %%i in ('where pythonw') do if not defined PYTHON_EXE set "PYTHON_EXE=%%i"
)

if not defined PYTHON_EXE (
    where python >nul 2>&1
    if %errorLevel% equ 0 (
        for /f "tokens=*" %%i in ('where python') do if not defined PYTHON_EXE set "PYTHON_EXE=%%i"
    )
)

if not defined PYTHON_EXE (
    echo [ERROR] No se encontro Python en el PATH del sistema.
    echo Asegurate de que Python este instalado y agregado al PATH.
    pause
    exit /b 1
)

echo [1/2] Binario Python detectado: %PYTHON_EXE%
echo [2/2] Registrando tarea programada: %TASK_NAME%...
echo.

:: 5. Crear la tarea en Windows Task Scheduler (corre cada 1 minuto bajo SYSTEM)
schtasks /create /tn "%TASK_NAME%" /tr "\"%PYTHON_EXE%\" \"%SCRIPT_PYTHON%\"" /sc MINUTE /mo 1 /ru "SYSTEM" /rl HIGHEST /f

if %errorLevel% equ 0 (
    echo.
    echo =========================================================================
    echo  [EXITO] Tarea %TASK_NAME% programada correctamente!
    echo =========================================================================
    echo  - Frecuencia: Cada 1 minuto (en segundo plano, 100%% silencioso).
    echo  - Usuario: SYSTEM (funciona aunque no haya sesion iniciada).
    echo  - Log de ejecucion: storage\logs\scheduler_runner.log
    echo  - Backups automaticos programados para las 02:00 AM en:
    echo    routes\console.php -^> storage\logs\backups.log
    echo =========================================================================
) else (
    echo.
    echo [ERROR] No se pudo registrar la tarea en el Programador de Windows.
)

echo.
pause
