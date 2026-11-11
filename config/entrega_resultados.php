<?php

/*
|--------------------------------------------------------------------------
| Entrega de resultados al paciente (enlace del portal por WhatsApp / correo)
|--------------------------------------------------------------------------
|
| Cuando existe la lectura, LIDiag principal la envía al portal de pacientes
| (lidiag-reportes), el portal genera un enlace con token y principal se lo entrega
| al paciente por WhatsApp y/o correo. TODO está apagado por defecto: no sale ningún
| mensaje hasta encenderlo en el .env. Los valores se leen aquí (no con env() en el
| código) para que `config:cache` funcione.
|
*/

return [

    /* Interruptor general de la entrega automática y del reenvío manual. */
    'activo' => (bool) env('ENTREGA_RESULTADOS_ACTIVO', false),

    /*
     * Sedes en las que está activa la entrega (ids de config_sedes separados por comas, p. ej. "7").
     * Vacío = todas. Sirve para un piloto en una sola sede antes de abrir al resto.
     */
    'sedes' => array_values(array_filter(array_map('intval', explode(',', (string) env('ENTREGA_SEDES', ''))))),

    'whatsapp' => [
        'activo' => (bool) env('ENTREGA_WHATSAPP_ACTIVO', false),

        /*
         * Plantilla APROBADA en Meta (categoría Utilidad, idioma es_CO) con 2 variables en el
         * cuerpo ({{1}} sede, {{2}} días de vigencia) y un botón de URL cuyo sufijo dinámico es
         * el token del enlace. No lleva nombre ni tipo de examen: el mensaje queda visible en la
         * pantalla de bloqueo. Ver Documentos/PLAN_ENLACE_PACIENTE_REPORTES.md.
         */
        'plantilla' => env('ENTREGA_WHATSAPP_PLANTILLA', 'resultado_estudio_listo'),
    ],

    'correo' => [
        'activo' => (bool) env('ENTREGA_CORREO_ACTIVO', false),
    ],

    'imagenes' => [
        /* Subir también las imágenes del estudio al portal (además del informe). */
        'activo' => (bool) env('ENTREGA_IMAGENES_ACTIVO', true),

        /* Tope de imágenes por estudio que se suben (el portal acepta hasta 800). */
        'max_por_estudio' => (int) env('ENTREGA_IMAGENES_MAX', 500),

        /* Imágenes por petición. No puede superar `max_file_uploads` de PHP en el portal (20 por defecto). */
        'max_por_lote' => (int) env('ENTREGA_IMAGENES_LOTE', 20),

        /* Tamaño máximo de un lote (bytes); debe quedar por debajo de `post_max_size` del portal. */
        'max_bytes_lote' => (int) env('ENTREGA_IMAGENES_BYTES_LOTE', 5 * 1024 * 1024),

        /* Tiempo máximo (s) para bajar y subir las imágenes; pasado ese tiempo se cierra como parcial. */
        'max_segundos' => (int) env('ENTREGA_IMAGENES_MAX_SEGUNDOS', 600),
    ],

    'portal' => [
        /*
         * URL pública del portal: la que ven los pacientes (p. ej. https://lidiag-reportes.com), distinta
         * de URL_API_REPORTES (la que usa este servidor para hablar con el portal). Solo se entregan
         * enlaces del tipo «<esta URL>/p/<token>»: si el portal devolviera otro dominio, el envío se
         * descarta, para no mandar a un paciente un enlace ajeno desde el número oficial de la sede.
         * Sin configurar no se envía nada. En producción debe ser https. Debe coincidir con
         * REPORTES_URL_PUBLICA del portal.
         */
        'url_publica' => rtrim((string) env('REPORTES_URL_PUBLICA', ''), '/'),

        /*
         * Verificar el certificado TLS del portal. Hoy la llamada se hace sin verificar (el portal
         * se alcanza por IP interna). Poner en true cuando se use el dominio con certificado válido.
         *
         * Sin verificar, la firma HMAC protege que nadie altere la PETICIÓN, pero no impide que alguien en la
         * red lea el informe, el documento y el enlace, ni que altere la respuesta (p. ej. el id de la lectura).
         * No lo deje en false fuera de una red controlada.
         */
        'verificar_tls' => (bool) env('REPORTES_VERIFICAR_TLS', false),

        /*
         * Ruta de un certificado (o paquete de CAs, .pem/.crt) en el que confiar para hablar con el portal.
         * Sirve cuando el portal se alcanza por IP interna con un certificado propio: se verifica la conexión
         * sin comprar uno público (el certificado debe incluir esa IP o nombre). Tiene prioridad sobre
         * `verificar_tls`. Vacío = no se usa.
         */
        'ca_bundle' => (string) env('REPORTES_CA_BUNDLE', ''),

        'timeout' => (int) env('REPORTES_TIMEOUT', 60),

        /* Pausa base (ms) antes de reintentar una petición rechazada por saturación; se multiplica por el intento. */
        'pausa_reintento_ms' => (int) env('REPORTES_PAUSA_REINTENTO_MS', 1000),
    ],
];
