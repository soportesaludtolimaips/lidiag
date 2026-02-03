<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$permission = \Spatie\Permission\Models\Permission::findByName('config.sedes.index');
$permission->assignRole(['Transcriptor', 'Administrativo', 'Estadistico']);
echo 'Permiso asignado a los roles.';
