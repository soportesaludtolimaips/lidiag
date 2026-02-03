<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Verificar si el rol 'Medico' tiene el permiso 'estudio.leerEstudio'
$roleHasPermission = DB::table('role_has_permissions')
    ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
    ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
    ->where('roles.name', 'Medico')
    ->where('permissions.name', 'estudio.leerEstudio')
    ->exists();

dd('Rol Medico tiene permiso estudio.leerEstudio:', $roleHasPermission);

// Verificar si el usuario 36 tiene el rol 'Medico'
$userHasRole = DB::table('model_has_roles')
    ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
    ->where('model_has_roles.model_type', 'App\Models\User')
    ->where('model_has_roles.model_id', 36)
    ->where('roles.name', 'Medico')
    ->exists();

dd('Usuario 36 tiene rol Medico:', $userHasRole);

// Verificar permisos directos del usuario 36
$userPermissions = DB::table('model_has_permissions')
    ->join('permissions', 'model_has_permissions.permission_id', '=', 'permissions.id')
    ->where('model_has_permissions.model_type', 'App\Models\User')
    ->where('model_has_permissions.model_id', 36)
    ->pluck('permissions.name');

dd('Permisos directos del usuario 36:', $userPermissions);
