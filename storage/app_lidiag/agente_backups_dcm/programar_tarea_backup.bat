@echo off
echo =========================================================
echo    Configurando Tareas Programadas de Backup para LIDiag
echo =========================================================
echo 1. Programando Backup General LIDiag (02:00 AM)...
schtasks /create /tn "LIDiag_Daily_Backup" /tr "\"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe\" \"C:\laragon\www\lidiag\artisan\" lidiag:backup" /sc daily /st 02:00 /ru "SYSTEM" /f
if %ERRORLEVEL% EQU 0 (
    echo [OK] Tarea "LIDiag_Daily_Backup" configurada (02:00 AM).
) else (
    echo [ERROR] No se pudo crear la tarea "LIDiag_Daily_Backup". Ejecuta como Administrador.
)

echo.
echo 2. Programando Backup DCM Sedes (02:30 AM)...
schtasks /create /tn "LIDiag_Daily_Backup_DCM" /tr "\"C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe\" \"C:\laragon\www\lidiag\artisan\" lidiag:backup-dcm" /sc daily /st 02:30 /ru "SYSTEM" /f
if %ERRORLEVEL% EQU 0 (
    echo [OK] Tarea "LIDiag_Daily_Backup_DCM" configurada (02:30 AM).
) else (
    echo [ERROR] No se pudo crear la tarea "LIDiag_Daily_Backup_DCM". Ejecuta como Administrador.
)

echo.
pause
