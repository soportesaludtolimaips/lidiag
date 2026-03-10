<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class HelpDeskPermisosSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Asegura existencia de roles (en este proyecto pueden estar creados manualmente)
        foreach (['Administrador', 'Soporte Manager', 'Soporte Tecnico', 'Medico', 'Administrativo', 'Transcriptor'] as $roleName) {
            Role::findOrCreate($roleName);
        }

        $permisos = [
            // Tickets
            'helpdesk.tickets.index',
            'helpdesk.tickets.create',
            'helpdesk.tickets.show',
            'helpdesk.tickets.edit',
            'helpdesk.tickets.delete',
            'helpdesk.tickets.change_status',
            'helpdesk.tickets.reassign',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.attachments.upload',
            'helpdesk.tickets.attachments.download',
            'helpdesk.tickets.attachments.delete',

            // Notificaciones
            'helpdesk.notificaciones.index',
            'helpdesk.notificaciones.read',

            // Reportes
            'helpdesk.reportes.index',
            'helpdesk.reportes.export',

            // Configuración (catálogos)
            'helpdesk.config.categorias.index',
            'helpdesk.config.categorias.create',
            'helpdesk.config.categorias.edit',
            'helpdesk.config.categorias.destroy',
            'helpdesk.config.prioridades.index',
            'helpdesk.config.prioridades.create',
            'helpdesk.config.prioridades.edit',
            'helpdesk.config.prioridades.destroy',
            'helpdesk.config.estados.index',
            'helpdesk.config.estados.create',
            'helpdesk.config.estados.edit',
            'helpdesk.config.estados.destroy',
        ];

        foreach ($permisos as $name) {
            $perm = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            $perm->syncRoles(['Administrador', 'Soporte Manager']);
        }

        // Soporte Técnico (operación)
        $soporteTecnico = [
            'helpdesk.tickets.index',
            'helpdesk.tickets.show',
            'helpdesk.tickets.edit',
            'helpdesk.tickets.change_status',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.attachments.upload',
            'helpdesk.tickets.attachments.download',
        ];
        foreach ($soporteTecnico as $name) {
            $perm = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            $perm->assignRole(['Soporte Tecnico']);
        }

        // Solicitantes (roles existentes) - mínimo viable
        $solicitante = [
            'helpdesk.tickets.index',
            'helpdesk.tickets.create',
            'helpdesk.tickets.show',
            'helpdesk.tickets.comment',
            'helpdesk.tickets.attachments.upload',
            'helpdesk.tickets.attachments.download',
            'helpdesk.notificaciones.index',
            'helpdesk.notificaciones.read',
        ];
        foreach ($solicitante as $name) {
            $perm = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if (! $perm) {
                $perm = Permission::create(['name' => $name, 'guard_name' => 'web']);
            }

            $perm->assignRole(['Medico', 'Administrativo', 'Transcriptor']);
        }
    }
}
