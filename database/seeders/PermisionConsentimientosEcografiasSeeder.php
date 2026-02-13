<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermisionConsentimientosEcografiasSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Asegurar roles base existentes en el sistema
        foreach (['Administrador', 'Medico', 'Administrativo', 'Transcriptor'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        /**
         * Permisos del módulo de Consentimientos (Ecografías).
         *
         * Nota: La firma del paciente/acompañante es por URL con token (sin login),
         * por lo que NO se controla por permisos Spatie.
         */
        $permissionsToRoles = [
            // Listado / consulta
            'ecografias.consentimientos.index' => ['Administrador', 'Transcriptor', 'Administrativo'],
            'ecografias.consentimientos.show' => ['Administrador', 'Transcriptor', 'Administrativo'],

            // Plantillas (WYSIWYG + versiones) - parametrización
            'ecografias.consentimientos.plantillas.index' => ['Administrador', 'Transcriptor'],
            'ecografias.consentimientos.plantillas.create' => ['Administrador', 'Transcriptor'],
            'ecografias.consentimientos.plantillas.edit' => ['Administrador', 'Transcriptor'],
            'ecografias.consentimientos.plantillas.destroy' => ['Administrador', 'Transcriptor'],
            'ecografias.consentimientos.plantillas.preview_pdf' => ['Administrador', 'Transcriptor'],

            // Flujo transcriptor (iniciar captura de firma paciente/acompañante)
            'ecografias.consentimientos.capturar_firma' => ['Administrador', 'Transcriptor'],

            // Firma médico (usa firma guardada en users.firma)
            'ecografias.consentimientos.firmar_medico' => ['Administrador', 'Medico'],

            // Anulación (con observación) y reemisión
            'ecografias.consentimientos.anular' => ['Administrador', 'Transcriptor'],

            // Generación/descarga de PDF y ZIP (módulo de impresión)
            'ecografias.consentimientos.pdf.generar' => ['Administrador', 'Transcriptor', 'Administrativo'],
            'ecografias.consentimientos.pdf.descargar' => ['Administrador', 'Transcriptor', 'Administrativo'],
            'ecografias.consentimientos.zip.generar' => ['Administrador', 'Transcriptor', 'Administrativo'],
        ];

        foreach ($permissionsToRoles as $permissionName => $roles) {
            $perm = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
            }

            // Asegura que esos roles tengan el permiso
            $perm->syncRoles($roles);
        }
    }
}
