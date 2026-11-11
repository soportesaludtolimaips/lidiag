@echo off
chcp 65001 >nul
title LIDiag ? Agente Bot de Telegram (Long-Polling)

cd /d "%~dp0"

set "LOG_DIR=%~dp0storage\logs"
if not exist "%LOG_DIR%" mkdir "%LOG_DIR%"
set "LOG_FILE=%LOG_DIR%\telegram_bot.log"

:: 1. Detectar binario de PHP
set "PHP_EXE="

:: Probar en Laragon (versiones instaladas de php-*)
for /d %%D in ("C:\laragon\bin\php\php-*") do (
    if exist "%%D\php.exe" (
        set "PHP_EXE=%%D\php.exe"
    )
)

:: Probar en enlace simb?lico current de Laragon
if not defined PHP_EXE (
    if exist "C:\laragon\bin\php\current\php.exe" (
        set "PHP_EXE=C:\laragon\bin\php\current\php.exe"
    )
)

:: Probar en PATH del sistema
if not defined PHP_EXE (
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        for /f "tokens=*" %%I in ('where php') do (
            if not defined PHP_EXE set "PHP_EXE=%%I"
        )
    )
)

if not defined PHP_EXE (
    echo [%date% %time%] [ERROR] No se encontr? el ejecutable de PHP. >> "%LOG_FILE%"
    echo [ERROR] No se encontr? PHP en Laragon ni en el PATH del sistema.
    pause
    exit /b 1
)

:: 2. Verificar existencia de artisan
if not exist "%~dp0artisan" (
    echo [%date% %time%] [ERROR] No se encontr? el archivo artisan en %~dp0 >> "%LOG_FILE%"
    echo [ERROR] No se encontr? el archivo artisan en %~dp0
    pause
    exit /b 1
)

echo =========================================================================
echo    LIDiag ? Agente Residente Bot de Telegram (@salud_tolima_bot)
echo =========================================================================
echo  PHP detectado: "%PHP_EXE%"
echo  Directorio:    "%~dp0"
echo  Registro Log:  "%LOG_FILE%"
echo =========================================================================
echo.

echo [%date% %time%] [INICIO] Iniciando servicio residente de Telegram Bot... >> "%LOG_FILE%"

:loop
echo [%date% %time%] [EJECUCION] Ejecutando: artisan telegram:poll >> "%LOG_FILE%"
"%PHP_EXE%" "%~dp0artisan" telegram:poll >> "%LOG_FILE%" 2>&1

:: Si el proceso se detiene o se interrumpe la red, registrar y reintentar en 5 segundos
echo [%date% %time%] [AVISO] El proceso telegram:poll se detuvo. Reintentando en 5 segundos... >> "%LOG_FILE%"
timeout /t 5 /nobreak >nul
goto loop
