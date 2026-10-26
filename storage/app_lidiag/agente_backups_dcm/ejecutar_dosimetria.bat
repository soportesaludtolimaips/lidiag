@echo off
chcp 65001 > nul
REM =========================================================================
REM LIDiag - Ejecutor del Agente Edge de Dosimetría Radiológica
REM =========================================================================

set SCRIPT_DIR=%~dp0
cd /d "%SCRIPT_DIR%"

echo [%date% %time%] Iniciando ciclo de extracción de dosimetría radiológica...

python agente_dosimetria.py --dias 1 --limit 200

if %ERRORLEVEL% NEQ 0 (
    if not exist "logs" mkdir logs
    echo [%date% %time%] [ERROR] El agente de dosimetría finalizó con código %ERRORLEVEL% >> logs\dosimetria_agent.log
    exit /b %ERRORLEVEL%
)

echo [%date% %time%] Ciclo de dosimetría completado exitosamente.
exit /b 0
