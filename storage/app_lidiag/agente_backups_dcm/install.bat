@echo off
chcp 65001 >nul
cd /d "%~dp0"

echo =========================================================================
echo    LIDiag — Instalador de Dependencias del Agente PACS
echo =========================================================================
echo.

echo [1/2] Actualizando pip...
python -m pip install --upgrade pip

echo.
echo [2/2] Instalando paquetes clinicos requeridos desde requirements.txt...
python -m pip install -r requirements.txt

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Fallo al instalar dependencias.
    echo Verifica tu conexion a internet o que Python este agregado al PATH.
    pause
    exit /b 1
)

echo.
echo Verificando instalacion de librerias...
python -c "import pydicom, requests, mysql.connector; print('   [OK] pydicom, requests y mysql-connector verificados exitosamente')"

echo.
echo =========================================================================
echo  Instalacion completada con exito!
echo =========================================================================
echo.
pause
