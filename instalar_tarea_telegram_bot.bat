@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion
title LIDiag - Instalador de Tarea Programada Bot de Telegram

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag ? Instalador de Tarea Automatizada Bot de Telegram
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
set "TASK_NAME=LIDiag_Telegram_Bot"
set "BAT_RUNNER=%~dp0ejecutar_telegram_bot.bat"

:: 3. Verificar que exista ejecutar_telegram_bot.bat
if not exist "%BAT_RUNNER%" (
    echo [ERROR] No se encontro el archivo %BAT_RUNNER%
    pause
    exit /b 1
)

echo [1/3] Script lanzador localizado en: "%BAT_RUNNER%"
echo [2/3] Registrando tarea en Windows Task Scheduler: "%TASK_NAME%"...
echo.

:: 4. Eliminar tarea previa si ya existe para reconfigurarla limpiamente
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %errorLevel% equ 0 (
    echo       [AVISO] Tarea existente detectada. Reemplazando configuracion...
    schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1
)

:: 5. Crear la tarea en Windows Task Scheduler (inicia al arrancar el sistema /sc ONSTART bajo SYSTEM)
schtasks /create /tn "%TASK_NAME%" /tr "\"%BAT_RUNNER%\"" /sc ONSTART /ru "SYSTEM" /rl HIGHEST /f >nul 2>&1

:: Si falla con SYSTEM por politicas de dominio, reintentar con el usuario actual
if %errorLevel% neq 0 (
    echo       [AVISO] No se pudo crear bajo usuario SYSTEM. Registrando con usuario actual...
    schtasks /create /tn "%TASK_NAME%" /tr "\"%BAT_RUNNER%\"" /sc ONSTART /rl HIGHEST /f >nul 2>&1
)

if %errorLevel% equ 0 (
    echo.
    echo =========================================================================
    echo  [EXITO] Tarea '%TASK_NAME%' programada correctamente!
    echo =========================================================================
    echo  - Disparador:  Al iniciar el sistema (ONSTART, 24/7 permanente).
    echo  - Resiliencia: Bucle de auto-recuperacion en caso de reinicio de red.
    echo  - Log de actividad: storage\logs\telegram_bot.log
    echo =========================================================================
    echo.
    echo ?Deseas iniciar el Bot de Telegram ahora mismo en segundo plano? (S/N)
    set /p "INICIAR=Opcion: "
    if /i "!INICIAR!"=="S" (
        schtasks /run /tn "%TASK_NAME%"
        echo [OK] Bot de Telegram iniciado mediante el Programador de Tareas.
    ) else if /i "!INICIAR!"=="" (
        schtasks /run /tn "%TASK_NAME%"
        echo [OK] Bot de Telegram iniciado mediante el Programador de Tareas.
    )
) else (
    echo.
    echo [ERROR] No se pudo registrar la tarea en el Programador de Windows.
    echo Verifica permisos o directivas de seguridad locales.
)

echo.
pause
