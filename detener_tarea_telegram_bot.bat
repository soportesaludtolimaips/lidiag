@echo off
chcp 65001 >nul
title LIDiag - Detener y Eliminar Tarea Programada Bot de Telegram

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag ? Detener Servicio de Bot de Telegram
echo =========================================================================
echo.

:: 1. Verificar privilegios de Administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ADVERTENCIA] Requiere privilegios de Administrador.
    echo Por favor, haz clic derecho y selecciona "Ejecutar como administrador".
    echo.
    pause
    exit /b 1
)

set "TASK_NAME=LIDiag_Telegram_Bot"

echo Deteniendo tarea en Windows Task Scheduler: "%TASK_NAME%"...
schtasks /end /tn "%TASK_NAME%" >nul 2>&1

echo Terminando cualquier proceso activo de 'artisan telegram:poll'...
wmic process where "CommandLine like '%%artisan%%telegram:poll%%'" call terminate >nul 2>&1

echo.
echo ?Deseas tambien ELIMINAR la tarea programada? (S/N)
set /p "ELIMINAR=Opcion: "
if /i "%ELIMINAR%"=="S" (
    schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1
    echo [OK] Tarea '%TASK_NAME%' eliminada correctamente.
) else (
    echo [OK] Tarea detenida (permanece registrada para futuros arranques).
)

echo.
pause
