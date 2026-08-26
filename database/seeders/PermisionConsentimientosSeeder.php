<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permisos del módulo genérico de Consentimientos Informados.
 *
 * Nota: la firma del paciente/acompañante es por URL con token (sin login),
 * por lo que NO se controla con permisos Spatie.
 */
class PermisionConsentimientosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Administrador', 'Medico', 'Administrativo', 'Transcriptor'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        $permissionsToRoles = [
            // Plantillas (WYSIWYG + versiones + imágenes) - parametrización
            'consentimientos.plantillas.index' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.create' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.edit' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.destroy' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.duplicar' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.versiones' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.preview' => ['Administrador', 'Transcriptor'],
            'consentimientos.plantillas.imagenes' => ['Administrador', 'Transcriptor'],

            // Asignación de plantilla a productos/sedes
            'consentimientos.configurar' => ['Administrador', 'Transcriptor'],

            // Generación y consulta de documentos
            'consentimientos.generar' => ['Administrador', 'Administrativo', 'Transcriptor'],
            'consentimientos.index' => ['Administrador', 'Administrativo', 'Transcriptor', 'Medico'],
            'consentimientos.show' => ['Administrador', 'Administrativo', 'Transcriptor', 'Medico'],

            // Marcas del administrativo en imágenes
            'consentimientos.marcar' => ['Administrador', 'Administrativo', 'Transcriptor'],

            // Flujo de firma
            'consentimientos.firmar_paciente' => ['Administrador', 'Administrativo', 'Transcriptor'],
            'consentimientos.firmar_medico' => ['Administrador', 'Medico'],

            // Anulación / restauración
            'consentimientos.anular' => ['Administrador', 'Transcriptor'],
            'consentimientos.restaurar' => ['Administrador'],

            // Liberación de emergencia del bloqueo de lecturas del médico
            'consentimientos.liberar_bloqueo' => ['Administrador'],

            // Adjuntos
            'consentimientos.adjuntos' => ['Administrador', 'Administrativo', 'Transcriptor'],

            // PDF / exportación
            'consentimientos.pdf.generar' => ['Administrador', 'Administrativo', 'Transcriptor'],
            'consentimientos.pdf.descargar' => ['Administrador', 'Administrativo', 'Transcriptor', 'Medico'],

            // Estadísticas / dashboard
            'consentimientos.estadisticas' => ['Administrador'],
        ];

        foreach ($permissionsToRoles as $permissionName => $roles) {
            $perm = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
            }

            $perm->syncRoles($roles);
        }
    }
}
