@echo off
setlocal
set PYTHON_DIR=C:\Users\Administrador\AppData\Local\Programs\Python\Python312
set PYTHON_EXE=%PYTHON_DIR%\python.exe

echo ============================================
echo  Lidiag - Instalacion de Dependencias
echo  FastAPI + Whisper (CPU)
echo ============================================
echo.

cd /d "%~dp0"

echo Verificando Python...
if not exist "%PYTHON_EXE%" (
    echo ERROR: Python no encontrado en %PYTHON_EXE%
    echo Instale Python 3.10+ desde https://www.python.org/downloads/
    pause
    exit /b 1
)

echo.
echo Actualizando pip...
"%PYTHON_EXE%" -m pip install --upgrade pip

echo.
echo Instalando dependencias...
"%PYTHON_EXE%" -m pip install -r requirements.txt

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
endlocal
pause
