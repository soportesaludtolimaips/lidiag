@echo off
chcp 65001 > nul
title Descubrir Chat ID de Telegram
echo ==================================================================
echo   DESCUBRIR CHAT ID DE TELEGRAM
echo ==================================================================
echo.
echo Recuerda haberle enviado previamente un mensaje a tu bot en Telegram
echo (por ejemplo: "hola" o "/start").
echo.
python "%~dp0dcm_disk_monitor.py" --get-chat-ids
echo.
pause
