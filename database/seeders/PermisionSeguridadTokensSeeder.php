<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermisionSeguridadTokensSeeder extends Seeder
{
    /**
     * Registra permisos para la gestión de tokens por sede y garantiza la existencia
     * del usuario de servicio institucional del Agente Edge.
     *
     * Flujo del proceso:
     * 1. Crea de forma idempotente los permisos de visualización, creación y revocación de tokens.
     * 2. Asigna y sincroniza los permisos con el rol Administrador.
     * 3. Asigna directamente los permisos al usuario administrador principal para visualización inmediata.
     * 4. Crea o actualiza de forma idempotente el usuario técnico de sistema `agente.edge@lidiag.gov.co`.
     * 5. Limpia la caché interna de permisos de Spatie.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function run(): void
    {
        $permisos = [
            "seguridad.tokens.index",
            "seguridad.tokens.create",
            "seguridad.tokens.revoke",
        ];

        foreach ($permisos as $nombrePermiso) {
            $perm = Permission::firstOrCreate([
                "name" => $nombrePermiso,
                "guard_name" => "web",
            ]);
            $perm->syncRoles(["Administrador"]);
        }

        // Asignar explícitamente a usuarios administradores existentes
        $admins = User::role("Administrador")->get();
        foreach ($admins as $admin) {
            $admin->givePermissionTo($permisos);
        }

        $usuarioAdmin = User::where("email", "administrador@administrador.com")->first();
        if ($usuarioAdmin) {
            $usuarioAdmin->givePermissionTo($permisos);
        }

        // Usuario de servicio dedicado para los tokens Edge de las sedes
        $usuarioServicio = User::firstOrCreate(
            ["email" => "agente.edge@lidiag.gov.co"],
            [
                "name" => "Agente Edge LIDiag",
                "num_docu" => "0000000000",
                "password" => Hash::make(Str::random(40)),
                "estado" => 1,
            ]
        );

        $rolAdmin = Role::where("name", "Administrador")->first();
        if ($rolAdmin && ! $usuarioServicio->hasRole("Administrador")) {
            $usuarioServicio->assignRole($rolAdmin);
        }

        // Limpiar inmediatamente la memoria caché de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
