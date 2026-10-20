@echo off
setlocal
cd /d "%~dp0"

echo ======================================================== >> backup_agent.log
echo [%date% %time%] Iniciando ejecucion de backup DCM >> backup_agent.log
echo ======================================================== >> backup_agent.log

python backup_dcm_agent.py --config config.json >> backup_agent.log 2>&1
set EXIT_CODE=%ERRORLEVEL%

if %EXIT_CODE% EQU 0 (
    echo [%date% %time%] Backup finalizado exitosamente. >> backup_agent.log
) else (
    echo [%date% %time%] ERROR: El backup finalizo con codigo de error %EXIT_CODE%. >> backup_agent.log
)
echo. >> backup_agent.log

exit /b %EXIT_CODE%
