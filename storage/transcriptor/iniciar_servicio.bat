@echo off
set PYTHON_EXE=C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe
set LOG_FILE=C:\laragon\www\lidiag\storage\transcriptor\servicio.log

set PATH=C:\ffmpeg\bin;%PATH%

echo %date% %time% - Iniciando servicio... >> %LOG_FILE%

cd /d C:\laragon\www\lidiag\storage\transcriptor
if %errorlevel% neq 0 (
    echo %date% %time% - ERROR: No se pudo cambiar al directorio >> %LOG_FILE%
    exit /b 1
)

"%PYTHON_EXE%" main.py >> %LOG_FILE% 2>&1
if %errorlevel% neq 0 (
    echo %date% %time% - ERROR: Codigo %errorlevel% >> %LOG_FILE%
    exit /b 1
)
