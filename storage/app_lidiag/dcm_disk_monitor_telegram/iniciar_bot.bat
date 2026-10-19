@echo off
chcp 65001 > nul
title DCM Disk Monitor - Bot de Telegram
echo ==================================================================
echo   DCM DISK MONITOR - BOT DE TELEGRAM PARA SERVIDORES PACS
echo ==================================================================
echo.

if not exist "%~dp0config.json" (
    echo [ERROR] No existe el archivo config.json.
    echo Copia 'config.json.example' a 'config.json' y configura tu Bot Token.
    echo.
    pause
    exit /b 1
)

echo Iniciando monitor y bot de Telegram...
echo Presiona Ctrl+C para detener el servicio.
echo.

python "%~dp0dcm_disk_monitor.py" --daemon

echo.
echo El servicio se ha detenido.
pause
