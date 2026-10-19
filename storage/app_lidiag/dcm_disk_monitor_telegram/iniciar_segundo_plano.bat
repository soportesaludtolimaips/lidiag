@echo off
chcp 65001 > nul
title Iniciando Monitor de Discos DCM

if not exist "%~dp0config.json" (
    echo [ERROR] No existe el archivo config.json.
    echo Copia 'config.json.example' a 'config.json' y configura tu Bot Token.
    pause
    exit /b 1
)

:: pythonw.exe es el ejecutable nativo de Python para correr en segundo plano sin ventana de consola
start "" pythonw "%~dp0dcm_disk_monitor.py" --daemon
echo [OK] Monitor de Discos DCM iniciado en segundo plano mediante pythonw.
timeout /t 2 > nul
