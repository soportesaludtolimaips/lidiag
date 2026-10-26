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

echo Ejecutando respaldo de base de datos...
python "%~dp0backup_dcm_agent.py" %*
