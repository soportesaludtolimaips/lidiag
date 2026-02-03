<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::find(36);
dd($user->hasRole('Medico'), $user->hasPermissionTo('estudio.leerEstudio'), $user->roles->pluck('name'), $user->permissions->pluck('name'));
