@echo off
chcp 65001 > nul
title Detener Monitor de Discos DICOM
echo ==================================================================
echo   DETENER SERVICIO - DCM DISK MONITOR
echo ==================================================================
echo.

echo Buscando procesos de dcm_disk_monitor.py...
wmic process where "name='python.exe' and commandline like '%%dcm_disk_monitor.py%%'" call terminate >nul 2>&1

if %errorlevel% equ 0 (
    echo [OK] Proceso(s) de monitoreo detenidos correctamente.
) else (
    echo [INFO] No se encontraron instancias de dcm_disk_monitor.py en ejecucion.
)

echo.
pause
