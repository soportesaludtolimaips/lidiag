<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeder PermisionLiquidacionSeeder: permisos del módulo de liquidación.
 *
 * Crea los permisos liquidacion.index y liquidacion.exportar de forma
 * idempotente y los asigna al rol Administrador. El permiso exportar se
 * usa desde la Task 6 (exportación a Excel).
 *
 * Flujo del proceso:
 * 1. Limpia la caché de permisos de Spatie.
 * 2. Crea cada permiso solo si no existe en el guard web.
 * 3. Sincroniza cada permiso con el rol Administrador.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
class PermisionLiquidacionSeeder extends Seeder
{
    /**
     * Ejecuta la creación idempotente de los permisos de liquidación.
     *
     * Flujo del proceso:
     * 1. Olvida la caché de permisos registrada.
     * 2. Por cada permiso: lo busca y solo lo crea si falta.
     * 3. Sincroniza el permiso con el rol Administrador.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            "liquidacion.index",
            "liquidacion.exportar",
        ];

        foreach ($permisos as $name) {
            $perm = Permission::query()
                ->where("name", $name)
                ->where("guard_name", "web")
                ->first();

            if (! $perm) {
                $perm = Permission::create(["name" => $name, "guard_name" => "web"]);
            }

            $perm->syncRoles(["Administrador"]);
        }
    }
}
