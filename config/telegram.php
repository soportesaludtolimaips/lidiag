<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Token & Credentials
    |--------------------------------------------------------------------------
    |
    | Token otorgado por @BotFather para el bot institucional de LIDiag.
    |
    */
    "bot_token" => env("TELEGRAM_BOT_TOKEN"),

    /*
    |--------------------------------------------------------------------------
    | Lista Blanca de Chat IDs Autorizados
    |--------------------------------------------------------------------------
    |
    | IDs de chat individuales o de grupos que tienen permiso de solicitar
    | reportes y consultar información asistencial/administrativa.
    |
    */
    "allowed_chat_ids" => array_values(array_filter(array_map("trim", explode(",", env("TELEGRAM_ALLOWED_CHAT_IDS", ""))))),

    /*
    |--------------------------------------------------------------------------
    | Clave Institucional de Auto-Autorización (PIN)
    |--------------------------------------------------------------------------
    |
    | Clave institucional que permite a médicos, directores y coordinadores
    | autorizar su cuenta de Telegram de forma autónoma con solo escribirla.
    |
    */
    "access_pin" => env("TELEGRAM_ACCESS_PIN", "Lidiag2026*"),

    /*
    |--------------------------------------------------------------------------
    | Configuración de Webhook
    |--------------------------------------------------------------------------
    |
    | URL pública donde Telegram enviará las actualizaciones mediante POST.
    |
    */
    "webhook_url" => env("TELEGRAM_WEBHOOK_URL"),
    "secret_token" => env("TELEGRAM_WEBHOOK_SECRET", "lidiag_telegram_secret_2026"),

    /*
    |--------------------------------------------------------------------------
    | Verificación SSL
    |--------------------------------------------------------------------------
    |
    | En Windows/Laragon suele haber cadenas de certificados locales/antivirus.
    | Permite alternar la verificación estricta de SSL según el entorno.
    |
    */
    "ssl_verify" => env("TELEGRAM_SSL_VERIFY", false),
];
