<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::find(36);
$user->givePermissionTo('estudio.leerEstudio');
echo 'Permiso asignado directamente al usuario.';
