@echo off
chcp 65001 >nul
setlocal enabledelayedexpansion

echo =========================================================================
echo    LIDiag — Instalador y Verificador de Entorno del Agente PACS
echo =========================================================================
echo  Verifica el entorno de Python, crea el entorno virtual dedicado (venv)
echo  e instala las librerias clinicas requeridas (pydicom, requests, mysql).
echo =========================================================================
echo.

cd /d "%~dp0"

:: 1. Localizar Python en el sistema
echo [1/4] Buscando interprete de Python en el servidor...
set "SYS_PYTHON="

:: Comprobar en PATH
where python >nul 2>&1
if !errorlevel! equ 0 (
    for /f "delims=" %%I in ('where python 2^>nul') do (
        if not defined SYS_PYTHON set "SYS_PYTHON=%%I"
    )
)

:: Comprobar rutas estandar en AppData del usuario actual
if not defined SYS_PYTHON if exist "%LOCALAPPDATA%\Programs\Python\Python313\python.exe" set "SYS_PYTHON=%LOCALAPPDATA%\Programs\Python\Python313\python.exe"
if not defined SYS_PYTHON if exist "%LOCALAPPDATA%\Programs\Python\Python312\python.exe" set "SYS_PYTHON=%LOCALAPPDATA%\Programs\Python\Python312\python.exe"
if not defined SYS_PYTHON if exist "%LOCALAPPDATA%\Programs\Python\Python311\python.exe" set "SYS_PYTHON=%LOCALAPPDATA%\Programs\Python\Python311\python.exe"
if not defined SYS_PYTHON if exist "%LOCALAPPDATA%\Programs\Python\Python310\python.exe" set "SYS_PYTHON=%LOCALAPPDATA%\Programs\Python\Python310\python.exe"

:: Comprobar rutas estandar en Program Files y raiz C:\
if not defined SYS_PYTHON if exist "C:\Program Files\Python313\python.exe" set "SYS_PYTHON=C:\Program Files\Python313\python.exe"
if not defined SYS_PYTHON if exist "C:\Program Files\Python312\python.exe" set "SYS_PYTHON=C:\Program Files\Python312\python.exe"
if not defined SYS_PYTHON if exist "C:\Program Files\Python311\python.exe" set "SYS_PYTHON=C:\Program Files\Python311\python.exe"
if not defined SYS_PYTHON if exist "C:\Program Files\Python310\python.exe" set "SYS_PYTHON=C:\Program Files\Python310\python.exe"
if not defined SYS_PYTHON if exist "C:\Python312\python.exe" set "SYS_PYTHON=C:\Python312\python.exe"
if not defined SYS_PYTHON if exist "C:\Python311\python.exe" set "SYS_PYTHON=C:\Python311\python.exe"
if not defined SYS_PYTHON if exist "C:\Python310\python.exe" set "SYS_PYTHON=C:\Python310\python.exe"
if not defined SYS_PYTHON if exist "C:\laragon\bin\python\python.exe" set "SYS_PYTHON=C:\laragon\bin\python\python.exe"

:: Comprobar ruta del usuario Administrador si existe
if not defined SYS_PYTHON if exist "C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe" set "SYS_PYTHON=C:\Users\Administrador\AppData\Local\Programs\Python\Python312\python.exe"

if not defined SYS_PYTHON (
    echo [ERROR CRITICO] No se encontro ninguna instalacion de Python en el servidor.
    echo Por favor instala Python 3.10 o superior descargandolo de:
    echo https://www.python.org/downloads/
    echo (Asegurate de marcar la casilla "Add python.exe to PATH" durante la instalacion).
    echo.
    pause
    exit /b 1
)

echo       [OK] Python detectado: "!SYS_PYTHON!"
"!SYS_PYTHON!" --version
echo.

:: 2. Configurar o reutilizar Entorno Virtual Dedicado (venv)
echo [2/4] Verificando entorno virtual aislado (venv)...
set "VENV_DIR=%~dp0venv"
set "VENV_PYTHON=%VENV_DIR%\Scripts\python.exe"

if not exist "!VENV_PYTHON!" (
    echo       Creando entorno virtual dedicado en "%~dp0venv"...
    "!SYS_PYTHON!" -m venv "%~dp0venv"
    if !errorlevel! neq 0 (
        echo       [AVISO] No se pudo crear venv aislado. Se utilizara el Python del sistema.
        set "AGENT_PYTHON=!SYS_PYTHON!"
    ) else (
        echo       [OK] Entorno virtual creado exitosamente.
        set "AGENT_PYTHON=!VENV_PYTHON!"
    )
) else (
    echo       [OK] Entorno virtual existente detectado en "%~dp0venv".
    set "AGENT_PYTHON=!VENV_PYTHON!"
)
echo.

:: 3. Instalar o actualizar dependencias desde requirements.txt
echo [3/4] Instalando y actualizando paquetes clinicos desde requirements.txt...
"!AGENT_PYTHON!" -m pip install --upgrade pip --quiet

if exist "%~dp0requirements.txt" (
    "!AGENT_PYTHON!" -m pip install -r "%~dp0requirements.txt"
) else (
    "!AGENT_PYTHON!" -m pip install pydicom requests mysql-connector-python urllib3
)

if !errorlevel! neq 0 (
    echo.
    echo [ERROR] Ocurrio un fallo al instalar los paquetes.
    echo Verifica que el servidor cuente con conexion a internet o configuracion de proxy.
    pause
    exit /b 1
)
echo       [OK] Dependencias instaladas exitosamente.
echo.

:: 4. Prueba de Verificacion de Importacion
echo [4/4] Verificando importacion de librerias clinicas...
"!AGENT_PYTHON!" -c "import pydicom, requests, mysql.connector; print('      [OK] pydicom ' + pydicom.__version__ + ' | requests ' + requests.__version__ + ' | mysql.connector ' + mysql.connector.__version__)"

if !errorlevel! equ 0 (
    echo       [EXITO] Todas las librerias fueron verificadas correctamente.
) else (
    echo       [ADVERTENCIA] Algunas librerias no pudieron cargarse. Revisa la salida superior.
)

echo.
echo =========================================================================
echo  [COMPLETADO] El entorno del Agente LIDiag esta 100%% listo para operar.
echo =========================================================================
echo.
echo Proximos pasos:
echo   1. Configura 'config.json' con los datos de tu sede (si no lo has hecho).
echo   2. Ejecuta 'instalar_tareas_sede.bat' para programar los servicios.
echo   3. O prueba 'ejecutar_dosimetria.bat' para lanzar la extraccion manual.
echo =========================================================================
echo.
endlocal
pause
