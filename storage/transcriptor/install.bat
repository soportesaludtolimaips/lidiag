@echo off
echo ============================================
echo  Lidiag - Instalacion de Dependencias
echo  FastAPI + Whisper (CPU)
echo ============================================
echo.

cd /d "%~dp0"

echo Verificando Python...
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python no encontrado.
    echo Instale Python 3.10+ desde https://www.python.org/downloads/
    echo Marque "Add Python to PATH" durante la instalacion.
    pause
    exit /b 1
)

echo.
echo Actualizando pip...
python -m pip install --upgrade pip

echo.
echo Instalando dependencias...
pip install -r requirements.txt

if errorlevel 1 (
    echo.
    echo ERROR: Error instalando dependencias.
    echo Verifique que tiene conexion a internet.
    pause
    exit /b 1
)

echo.
echo ============================================
echo  Instalacion completada exitosamente!
echo ============================================
echo.
echo Para iniciar el servicio, ejecute: start.bat
echo.
pause
