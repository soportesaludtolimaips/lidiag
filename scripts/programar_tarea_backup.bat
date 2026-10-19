@echo off
echo =========================================================
echo    Configurando Tarea Programada de Backup para LIDiag
echo =========================================================
schtasks /create /tn "LIDiag_Daily_Backup" /tr "\"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe\" \"C:\laragon\www\lidiag\artisan\" lidiag:backup" /sc daily /st 02:00 /ru "SYSTEM" /f
if %ERRORLEVEL% EQU 0 (
    echo.
    echo [OK] Tarea programada "LIDiag_Daily_Backup" creada con exito para ejecutarse diariamente a las 02:00 AM.
) else (
    echo.
    echo [ERROR] No se pudo crear la tarea programada. Asegurate de ejecutar como Administrador.
)
pause
