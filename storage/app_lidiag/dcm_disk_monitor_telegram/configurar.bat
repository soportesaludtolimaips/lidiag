@echo off
chcp 65001 > nul
title Asistente de Configuración - DCM Disk Monitor
python "%~dp0configurar_interactivo.py"
pause
