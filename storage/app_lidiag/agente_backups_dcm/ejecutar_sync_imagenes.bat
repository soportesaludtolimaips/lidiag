@echo off
chcp 65001 >nul
title LIDiag - Sincronizacion Continua de Imagenes PACS DCM

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag - Agente de Sincronizacion Continua de Imagenes PACS a QNAP
echo =========================================================================

if not exist "config.json" (
    echo [ERROR] No se encontro config.json.
    echo Copia config.json.example a config.json y completa los datos de tu sede.
    pause
    exit /b 1
)

echo Iniciando agente de sincronizacion continua de imagenes...
python "%~dp0sync_dcm_images.py" %*
