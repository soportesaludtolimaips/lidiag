@echo off
echo ============================================
echo  Lidiag - Servicio de Transcripcion de Audio
echo  FastAPI + Whisper (CPU)
echo ============================================
echo.

cd /d "%~dp0"

echo Verificando Python...
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python no encontrado. Instale Python 3.10+ y agreguelo al PATH.
    pause
    exit /b 1
)

echo Verificando dependencias...
python -c "import fastapi" >nul 2>&1
if errorlevel 1 (
    echo Instalando dependencias...
    pip install -r requirements.txt
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

python main.py

pause
