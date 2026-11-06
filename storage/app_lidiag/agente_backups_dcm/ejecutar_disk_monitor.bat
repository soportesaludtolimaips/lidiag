@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo =========================================================================
echo       LIDiag - Monitor de Almacenamiento y Discos PACS DCM
echo =========================================================================
echo [%date% %time%] Auditando almacenamiento de repositorios...

python "%~dp0dcm_disk_monitor.py" --check-once

if %errorlevel% neq 0 (
    if not exist "logs" mkdir logs
    echo [%date% %time%] [ERROR] El monitor de discos finalizo con codigo %errorlevel% >> logs\disk_monitor.log
    exit /b %errorlevel%
)

echo [%date% %time%] Auditoria de discos completada exitosamente.
exit /b 0
