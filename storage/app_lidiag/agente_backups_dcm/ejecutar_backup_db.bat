@echo off
chcp 65001 >nul
title LIDiag - Respaldo de Base de Datos PACS DCM

cd /d "%~dp0"

echo =========================================================================
echo       LIDiag - Agente de Respaldo de Base de Datos MySQL (PACS DCM)
echo =========================================================================

if not exist "config.json" (
    echo [ERROR] No se encontro config.json.
    echo Copia config.json.example a config.json y completa los datos de tu sede.
    pause
    exit /b 1
)

:: Buscar ejecutable de Python
set "PYTHON_EXE="
where python >nul 2>&1 && set "PYTHON_EXE=python"
if "%PYTHON_EXE%"=="" if exist "C:\laragon\bin\python\python.exe" set "PYTHON_EXE=C:\laragon\bin\python\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\Python312\python.exe" set "PYTHON_EXE=C:\Python312\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\Python311\python.exe" set "PYTHON_EXE=C:\Python311\python.exe"
if "%PYTHON_EXE%"=="" if exist "C:\Python310\python.exe" set "PYTHON_EXE=C:\Python310\python.exe"

if "%PYTHON_EXE%"=="" (
    echo [ERROR] Python no fue encontrado en el PATH ni en las rutas estandar.
    pause
    exit /b 1
)

echo Ejecutando respaldo de base de datos...
"%PYTHON_EXE%" "%~dp0backup_dcm_agent.py" %*
