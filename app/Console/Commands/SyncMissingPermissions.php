<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SyncMissingPermissions extends Command
{
    protected $signature = 'sync:missing-permissions {--dry-run : Show what would be created without creating}';

    protected $description = 'Sync permissions that exist in code but not in database';

    /**
     * Sincroniza los permisos que existen en código pero faltan en la base de datos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function handle(): int
    {
        $this->info('Running permissions audit...');

        // Get permissions from code
        $codePermissions = $this->getCodePermissions();

        // Get existing permissions in database
        $dbPermissions = Permission::pluck('name')->toArray();

        // Find missing
        $missing = array_diff($codePermissions, $dbPermissions);

        if (empty($missing)) {
            $this->info('No missing permissions found.');

            return Command::SUCCESS;
        }

        $this->warn('Found '.count($missing).' missing permissions:');

        foreach ($missing as $permission) {
            $this->line("  - $permission");
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run mode - no permissions created.');

            return Command::SUCCESS;
        }

        if (! $this->confirm('Create '.count($missing).' permissions?')) {
            $this->info('Cancelled.');

            return Command::SUCCESS;
        }

        $created = 0;
        foreach ($missing as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
            $created++;
        }

        $this->info("Created $created permissions.");

        // Sync to roles if needed
        if ($this->confirm('Sync permissions to all roles?')) {
            $roles = Role::all();
            $allPermissions = Permission::all();

            foreach ($roles as $role) {
                $role->givePermissionTo($allPermissions);
            }

            $this->info('Synced all permissions to '.$roles->count().' roles.');
        }

        return Command::SUCCESS;
    }

    /**
     * Devuelve el catálogo de permisos usados en el código de la aplicación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function getCodePermissions(): array
    {
        // Common permissions used in the application
        return [
            // Estudios
            'estudio.asignar',
            'estudio.leerEstudio',
            'estudio.transcribir',
            'estudio.leerestudio',

            // Ecografias
            'ecografias.index',
            'ecografias.admision',
            'ecografias.anular',
            'ecografias.leer',
            'ecografias.reporte',

            // Ecografias consentimientos
            'ecografias.consentimientos.index',
            'ecografias.consentimientos.capturar_firma',
            'ecografias.consentimientos.firmar_medico',

            // Ecografias consentimientos pdf
            'ecografias.consentimientos.pdf.generar',
            'ecografias.consentimientos.pdf.descargar',

            // Ecografias consentimientos plantillas
            'ecografias.consentimientos.plantillas.index',
            'ecografias.consentimientos.plantillas.create',
            'ecografias.consentimientos.plantillas.edit',
            'ecografias.consentimientos.plantillas.destroy',
            'ecografias.consentimientos.plantillas.preview_pdf',

            // Ecografias consentimientos zip
            'ecografias.consentimientos.zip.generar',
        ];
    }
}
