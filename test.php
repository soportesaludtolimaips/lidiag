<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::find(36);
dd($user->hasRole('Medico'), $user->hasPermissionTo('estudio.leerEstudio'), $user->roles->pluck('name'), $user->permissions->pluck('name'));
