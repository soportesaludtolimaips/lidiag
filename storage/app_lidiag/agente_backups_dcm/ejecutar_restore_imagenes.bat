@echo off
chcp 65001 >nul
title LIDiag - Restauracion Cronologica de Imagenes PACS desde QNAP

cd /d "%~dp0"

echo =========================================================================
echo    LIDiag - Restauracion de Emergencia de Imagenes PACS desde QNAP
echo =========================================================================
echo  Herramienta de Recuperacion ante Desastres (Disaster Recovery)
echo  Restaura los repositorios en orden cronologico descendente:
echo  [1] Año actual (2026) primero para atencion clinica inmediata en Oviyam
echo  [2] Años historicos (2025, 2024, etc.) en segundo plano
echo =========================================================================
echo.

if not exist "config.json" (
    echo [ERROR] No se encontro config.json.
    echo Asegurate de configurar config.json con las credenciales de la NAS antes de continuar.
    pause
    exit /b 1
)

echo Selecciona la modalidad de restauracion:
echo.
echo   [1] Restauracion COMPLETA (Mas reciente al mas antiguo: 2026 a historico)
echo   [2] Restaurar SOLO el AÑO ACTUAL (Maxima prioridad para urgencias y consulta)
echo   [3] Ver orden y carpetas sin copiar (Simulacion Dry-run)
echo   [4] Cancelar y salir
echo.
set /p "OPCION=Ingresa tu opcion (1-4): "

if "%OPCION%"=="1" (
    echo.
    echo Iniciando restauracion completa descendente...
    python "%~dp0restore_dcm_images.py"
) else if "%OPCION%"=="2" (
    set /p "ANIO_ACTUAL=Ingresa el año que deseas restaurar con urgencia (ej. 2026): "
    echo.
    echo Iniciando restauracion prioritaria para el año %ANIO_ACTUAL%...
    python "%~dp0restore_dcm_images.py" --year=%ANIO_ACTUAL%
) else if "%OPCION%"=="3" (
    echo.
    echo Ejecutando simulacion...
    python "%~dp0restore_dcm_images.py" --dry-run
) else (
    echo Operacion cancelada por el usuario.
    exit /b 0
)

echo.
pause
