@echo off
setlocal

echo ============================================
echo  Lidiag - Servicio de Transcripcion de Audio
echo  FastAPI + Whisper (CPU)
echo ============================================
echo.

cd /d "%~dp0"

:: Python del sistema
set PYTHON_DIR=C:\Users\Jhon\AppData\Local\Programs\Python\Python313
set PYTHON_EXE=%PYTHON_DIR%\python.exe

:: Entorno virtual
set VENV_DIR=%CD%\venv
set VENV_PYTHON=%VENV_DIR%\Scripts\python.exe
set VENV_ACTIVATE=%VENV_DIR%\Scripts\activate.bat

echo Verificando Python...
if not exist "%PYTHON_EXE%" (
    echo ERROR: Python no encontrado:
    echo %PYTHON_EXE%
    pause
    exit /b 1
)

echo.

:: Crear entorno virtual si no existe
if not exist "%VENV_PYTHON%" (
    echo Creando entorno virtual...
    "%PYTHON_EXE%" -m venv "%VENV_DIR%"
    if errorlevel 1 (
        echo ERROR: No fue posible crear el entorno virtual.
        pause
        exit /b 1
    )
)

echo Activando entorno virtual...
call "%VENV_ACTIVATE%"

echo.

:: Actualizar pip
echo Actualizando pip...
python -m pip install --upgrade pip

echo.

:: Verificar FastAPI
python -c "import fastapi" >nul 2>&1
if errorlevel 1 (
    echo Instalando dependencias...
    python -m pip install -r requirements.txt
    if errorlevel 1 (
        echo ERROR: No fue posible instalar las dependencias.
        pause
        exit /b 1
    )
)

echo.
echo ============================================
echo Iniciando servicio...
echo http://localhost:8001
echo ============================================
echo.

python main.py

endlocal
pause
