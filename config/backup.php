<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Destino de los Backups
    |--------------------------------------------------------------------------
    |
    | Carpeta local o de red donde se depositarán los archivos .zip de backup.
    | Puede ser sincronizada por clientes como QNAP Qsync hacia el NAS.
    |
    */
    "destination_path" => env("BACKUP_DESTINATION_PATH", "C:\\Backups_Lidiag"),

    /*
    |--------------------------------------------------------------------------
    | Política de Retención (Días)
    |--------------------------------------------------------------------------
    |
    | Cantidad de días que se conservarán los backups en el directorio de destino.
    | Los archivos .zip con fecha de modificación más antigua serán purgados.
    |
    */
    "retention_days" => (int) env("BACKUP_RETENTION_DAYS", 30),

    /*
    |--------------------------------------------------------------------------
    | Binario de mysqldump
    |--------------------------------------------------------------------------
    |
    | Ruta absoluta al ejecutable mysqldump.exe si no está en el PATH del sistema.
    | Si se deja nulo o vacío, el servicio intentará auto-detectarlo en Laragon o PATH.
    |
    */
    "mysqldump_path" => env("MYSQLDUMP_PATH"),

    /*
    |--------------------------------------------------------------------------
    | Carpetas de Storage a Respaldar
    |--------------------------------------------------------------------------
    |
    | Subcarpetas dentro de storage/app/ que se incluirán en el archivo .zip.
    |
    */
    "storage_folders" => [
        "audios",
        "avatars",
        "citas_soportes",
        "files_consentimientos",
        "files_help_desk",
        "firmas_consntimientos",
        "firmas_medicos",
        "img_dicom",
        "lo-debug-profile",
        "logos",
        "plantillas",
        "plantillas_lecturas",
        "public",
        "reporte_lecturas",
        "reporte_lecturas_ecografias",
        "soportes_hc",
    ],

    /*
    |--------------------------------------------------------------------------
    | Directorio Temporal de Trabajo
    |--------------------------------------------------------------------------
    |
    | Directorio transitorio para generar el dump .sql antes de comprimirlo.
    | Se limpia automáticamente al finalizar la operación.
    |
    */
    "temp_path" => storage_path("app/backup-temp"),
];
