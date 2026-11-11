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

    /*
    |--------------------------------------------------------------------------
    | URL Base de la API de Telegram
    |--------------------------------------------------------------------------
    |
    | Permite redirigir a un proxy inverso o servidor local de Bot API si la
    | red hospitalaria o firewall corporativo bloquea api.telegram.org.
    |
    */
    "base_url" => env("TELEGRAM_BASE_URL", "https://api.telegram.org"),

    /*
    |--------------------------------------------------------------------------
    | Forzar Resolución IPv4
    |--------------------------------------------------------------------------
    |
    | En servidores hospitalarios o redes con IPv6 deshabilitado en el router
    | pero activo en la tarjeta de red, cURL puede colgarse esperando IPv6
    | causando timeouts (cURL error 28). Forzar IPv4 evita este fallo.
    |
    */
    "force_ipv4" => (bool) env("TELEGRAM_FORCE_IPV4", true),

    /*
    |--------------------------------------------------------------------------
    | Proxy HTTP / SOCKS5
    |--------------------------------------------------------------------------
    |
    | Servidor proxy para salida a internet si el servidor de producción está
    | detrás de un firewall corporativo (ej: http://proxy:8080 o socks5://proxy:1080).
    |
    */
    "proxy" => env("TELEGRAM_PROXY", null),

    /*
    |--------------------------------------------------------------------------
    | Timeouts de Red (Segundos)
    |--------------------------------------------------------------------------
    |
    | connect_timeout: Tiempo máximo para establecer el socket TCP.
    | timeout: Tiempo máximo total de respuesta de la petición HTTP.
    |
    */
    "connect_timeout" => (int) env("TELEGRAM_CONNECT_TIMEOUT", 10),
    "timeout" => (int) env("TELEGRAM_TIMEOUT", 20),
];
