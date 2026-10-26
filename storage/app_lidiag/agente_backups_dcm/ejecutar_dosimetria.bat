@echo off
chcp 65001 > nul
REM =========================================================================
REM LIDiag - Ejecutor del Agente Edge de Dosimetría Radiológica
REM =========================================================================

set SCRIPT_DIR=%~dp0
cd /d "%SCRIPT_DIR%"

:: Localizar ejecutable de Python (priorizando venv local)
set "PYTHON_EXE="
if exist "%~dp0venv\Scripts\python.exe" set "PYTHON_EXE=%~dp0venv\Scripts\python.exe"
if "%PYTHON_EXE%"=="" where python >nul 2>&1 && set "PYTHON_EXE=python"
if "%PYTHON_EXE%"=="" if exist "%LOCALAPPDATA%\Programs\Python\Python312\python.exe" set "PYTHON_EXE=%LOCALAPPDATA%\Programs\Python\Python312\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\Program Files\Python312\python.exe" set "PYTHON_EXE=C:\Program Files\Python312\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\Python312\python.exe" set "PYTHON_EXE=C:\Python312\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\Python311\python.exe" set "PYTHON_EXE=C:\Python311\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\laragon\bin\python\python.exe" set "PYTHON_EXE=C:\laragon\bin\python\python.exe"

if "%PYTHON_EXE%"=="" (
    echo [ERROR] No se encontro Python ni el entorno virtual venv.
    echo Ejecuta install.bat para configurar el entorno automaticamente.
    exit /b 1
)

echo [%date% %time%] Iniciando ciclo de extracción de dosimetría radiológica...
"%PYTHON_EXE%" "%~dp0agente_dosimetria.py" --dias 1 --limit 200

if %ERRORLEVEL% NEQ 0 (
    if not exist "logs" mkdir logs
    echo [%date% %time%] [ERROR] El agente de dosimetría finalizó con código %ERRORLEVEL% >> logs\dosimetria_agent.log
    exit /b %ERRORLEVEL%
)

echo [%date% %time%] Ciclo de dosimetría completado exitosamente.
exit /b 0
