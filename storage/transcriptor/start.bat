@echo off
setlocal
set PYTHON_DIR=C:\Users\Administrador\AppData\Local\Programs\Python\Python312
set PYTHON_EXE=%PYTHON_DIR%\python.exe

echo ============================================
echo  Lidiag - Servicio de Transcripcion de Audio
echo  FastAPI + Whisper (CPU)
echo ============================================
echo.

cd /d "%~dp0"

echo Verificando Python...
if not exist "%PYTHON_EXE%" (
    echo ERROR: Python no encontrado en %PYTHON_EXE%
    pause
    exit /b 1
)

echo Verificando dependencias...
"%PYTHON_EXE%" -c "import fastapi" >nul 2>&1
if errorlevel 1 (
    echo Instalando dependencias...
    "%PYTHON_EXE%" -m pip install -r requirements.txt
    if errorlevel 1 (
        echo ERROR: Error instalando dependencias.
        pause
        exit /b 1
    )
)

echo.
echo Iniciando servicio en http://localhost:8001
echo Presione Ctrl+C para detener
echo.

"%PYTHON_EXE%" main.py

endlocal
pause
