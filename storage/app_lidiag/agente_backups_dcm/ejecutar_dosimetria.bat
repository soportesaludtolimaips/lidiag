@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo =========================================================================
echo       LIDiag - Agente Edge de Dosimetria Radiologica
echo =========================================================================
echo [%date% %time%] Iniciando ciclo de extraccion de dosimetria...

python "%~dp0agente_dosimetria.py" --dias 1 --limit 200

if %errorlevel% neq 0 (
    if not exist "logs" mkdir logs
    echo [%date% %time%] [ERROR] El agente finalizo con codigo %errorlevel% >> logs\dosimetria_agent.log
    exit /b %errorlevel%
)

echo [%date% %time%] Ciclo de dosimetria completado exitosamente.
exit /b 0
