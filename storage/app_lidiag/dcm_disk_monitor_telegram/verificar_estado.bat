@echo off
chcp 65001 > nul
title Verificando Estado de Discos DICOM
python "%~dp0dcm_disk_monitor.py" --status
echo.
pause
