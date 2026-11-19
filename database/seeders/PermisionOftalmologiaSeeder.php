<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder de permisos Spatie para el módulo de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Limpia la caché de permisos de Spatie.
 * 2. Asegura la existencia de los roles principales (Administrador, Medico, Administrativo, Transcriptor).
 * 3. Crea de forma idempotente los permisos y sincroniza los roles asignados.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class PermisionOftalmologiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (["Administrador", "Medico", "Administrativo", "Transcriptor"] as $roleName) {
            Role::findOrCreate($roleName, "web");
        }

        $map = [
            "oftalmologia.index" => ["Administrador", "Transcriptor", "Administrativo", "Medico"],
            "oftalmologia.admision" => ["Administrador", "Transcriptor", "Administrativo"],
            "oftalmologia.anular" => ["Administrador", "Transcriptor"],
            "oftalmologia.consulta" => ["Administrador", "Medico"],
            "oftalmologia.plantillas" => ["Administrador", "Medico"],
            "oftalmologia.reportes" => ["Administrador", "Medico", "Administrativo"],
            "oftalmologia.sahi.sincronizar" => ["Administrador", "Transcriptor", "Medico"],
        ];

        foreach ($map as $name => $roles) {
            $perm = Permission::query()
                ->where("name", $name)
                ->where("guard_name", "web")
                ->first();

            if (! $perm) {
                $perm = Permission::create(["name" => $name, "guard_name" => "web"]);
            }

            $perm->syncRoles($roles);
        }
    }
}
