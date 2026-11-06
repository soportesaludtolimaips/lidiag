@echo off
chcp 65001 >nul
title LIDiag - Instalador de Dependencias del Agente PACS DCM
cd /d "%~dp0"

echo =========================================================================
echo    LIDiag — Instalador de Dependencias del Paquete de Agentes PACS DCM
echo =========================================================================
echo.

:: 1. Verificar si Python esta disponible en el PATH
where python >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR CRITICO] Python no esta instalado o no se encuentra en el PATH.
    echo.
    echo Por favor, instala Python 3.10 o superior:
    echo 1. Descarga Python desde: https://www.python.org/downloads/
    echo 2. Durante la instalacion, MARCA obligatoriamente la casilla:
    echo    "Add python.exe to PATH"
    echo 3. Cierra esta ventana y vuelve a ejecutar install.bat.
    echo.
    pause
    exit /b 1
)

:: Mostrar version detectada de Python
for /f "tokens=*" %%v in ('python --version 2^>^&1') do set "PY_VER=%%v"
echo [OK] Entorno Python detectado: %PY_VER%

:: 2. Asegurar existencia de directorio de bitacoras
if not exist "logs" (
    mkdir "logs"
    echo [OK] Directorio de bitacoras creado: logs\
)

echo.
echo [1/3] Actualizando gestor de paquetes pip...
python -m pip install --upgrade pip

echo.
echo [2/3] Instalando dependencias requeridas desde requirements.txt...
python -m pip install -r requirements.txt

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Fallo al instalar las dependencias con pip.
    echo Posibles causas:
    echo - Falta de conexion a internet en este servidor.
    echo - Restriccion de proxy o firewall hospitalario.
    echo - Permisos insuficientes en la carpeta de Python.
    echo.
    pause
    exit /b 1
)

echo.
echo [3/3] Verificando integridad de librerias clinicas y de red...
python -c "import pydicom; print('      [OK] pydicom: ' + pydicom.__version__)"
python -c "import requests; print('      [OK] requests: ' + requests.__version__)"
python -c "import urllib3; print('      [OK] urllib3: ' + urllib3.__version__)"
python -c "try: import mysql.connector; print('      [OK] mysql-connector-python: ' + mysql.connector.__version__)\nexcept Exception: import pymysql; print('      [OK] pymysql (fallback): ' + pymysql.__version__)"

echo.
echo =========================================================================
echo  INSTALACION DE DEPENDENCIAS COMPLETADA CON EXITO
echo =========================================================================
echo.
echo Siguientes pasos para completar el despliegue en la sede:
echo   1. Asegurate de tener "config.json" en esta carpeta con los datos de tu sede.
echo   2. Ejecuta "instalar_tareas_sede.bat" para registrar las 4 tareas de Windows:
echo      - Respaldo BD MySQL (diario 02:00 AM)
echo      - Sincronizacion Continua de Imagenes (ONSTART)
echo      - Dosimetria Radiologica Edge (cada hora)
echo      - Monitor de Discos y Almacenamiento (cada 30 min)
echo.
echo ¿Deseas registrar las 4 tareas de Windows ahora mismo en este servidor? (S/N)
set /p "REG_TAREAS=Opcion: "
if /i "%REG_TAREAS%"=="S" (
    call "%~dp0instalar_tareas_sede.bat"
) else (
    echo.
    echo Puedes registrar las tareas en cualquier momento ejecutando "instalar_tareas_sede.bat".
    pause
)