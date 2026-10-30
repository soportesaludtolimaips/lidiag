<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermisionEstudiosSeeder extends Seeder
{
    /**
     * Registra y sincroniza de forma idempotente los permisos del módulo de Estudios.
     *
     * Flujo del proceso:
     * 1. Limpia la caché de Spatie Permissions.
     * 2. Registra los permisos canónicos de listado, lectura, transcripción, asignación y productos.
     * 3. Asigna de forma idempotente los permisos a los roles correspondientes (Administrador, Administrativo, Transcriptor RX, Medico, Estadistico).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $todosPermisos = [
            'estudio.index',
            'estudio.asignar',
            'estudio.transcribir',
            'estudio.leer',
            'estudio.leerEstudio',
            'estudio.productos',
            'estudios.notificar.index',
            'estudios.reporteLectura.index',
        ];

        foreach ($todosPermisos as $permiso) {
            Permission::firstOrCreate([
                'name' => $permiso,
                'guard_name' => 'web',
            ]);
        }

        $rolesPermisos = [
            'Administrador' => $todosPermisos,
            'Administrativo' => [
                'estudio.index',
                'estudio.asignar',
                'estudio.transcribir',
                'estudio.productos',
                'estudios.notificar.index',
                'estudios.reporteLectura.index',
            ],
            'Transcriptor RX' => [
                'estudio.index',
                'estudio.asignar',
                'estudio.transcribir',
                'estudio.productos',
                'estudios.notificar.index',
                'estudios.reporteLectura.index',
            ],
            'Medico' => [
                'estudio.leer',
                'estudio.leerEstudio',
            ],
            'Estadistico' => [
                'estudios.notificar.index',
                'estudios.reporteLectura.index',
            ],
        ];

        foreach ($rolesPermisos as $rolNombre => $listaPermisos) {
            $rol = Role::where('name', $rolNombre)->where('guard_name', 'web')->first();
            if ($rol) {
                $rol->givePermissionTo($listaPermisos);
            }
        }
    }
}
