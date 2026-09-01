<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
        'reporte_lecturas' => [
            'driver' => 'local',
            'root' => storage_path('app/reporte_lecturas'),
            'visibility' => 'private',
        ],
        'reporte_lecturas_ecografias' => [
            'driver' => 'local',
            'root' => storage_path('app/reporte_lecturas_ecografias'),
            'visibility' => 'private',
        ],
        'avatars' => [
            'driver' => 'local',
            'root' => storage_path('app/avatars'),
            'url' => env('APP_URL').'/avatars',
            'visibility' => 'public',
        ],
        'firmas_medicos' => [
            'driver' => 'local',
            'root' => storage_path('app/firmas_medicos'),
            'visibility' => 'private',
        ],
        'soportes_hc' => [
            'driver' => 'local',
            'root' => storage_path('app/soportes_hc'),
            'visibility' => 'private',
        ],
        'audios' => [
            'driver' => 'local',
            'root' => storage_path('app/audios'),
            'visibility' => 'private',
        ],
        'plantillas_lecturas' => [
            'driver' => 'local',
            'root' => storage_path('app/plantillas_lecturas'),
            'visibility' => 'private',
        ],
        'img_dicom' => [
            'driver' => 'local',
            'root' => storage_path('app/img_dicom'),
            'visibility' => 'private',
        ],
        'files_help_desk' => [
            'driver' => 'local',
            'root' => storage_path('app/files_help_desk'),
            'visibility' => 'private',
        ],
        'firmas_consntimientos' => [
            'driver' => 'local',
            'root' => storage_path('app/firmas_consntimientos'),
            'visibility' => 'private',
        ],
        'files_consentimientos' => [
            'driver' => 'local',
            'root' => storage_path('app/files_consentimientos'),
            'visibility' => 'private',
        ],
        'adjuntos_consentimientos' => [
            'driver' => 'local',
            'root' => storage_path('app/adjuntos_consentimientos'),
            'visibility' => 'private',
        ],
        'imagenes_consentimientos' => [
            'driver' => 'local',
            'root' => storage_path('app/imagenes_consentimientos'),
            'visibility' => 'private',
        ],
        'microdicom' => [
            'driver' => 'local',
            'root' => storage_path('app_lidiag/microdicom'),
            'visibility' => 'public',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
        public_path('microdicom') => storage_path('app_lidiag/microdicom'),
    ],

];
