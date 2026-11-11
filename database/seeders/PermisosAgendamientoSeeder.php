<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisosAgendamientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            'agendamiento.disponibilidad.index',
            'agendamiento.disponibilidad.create',
            'agendamiento.disponibilidad.delete',
            'agendamiento.disponibilidad.update',
            'agendamiento.solicitudes.index',
            'agendamiento.solicitudes.create',
            'agendamiento.solicitudes.delete',
            'agendamiento.solicitudes.update',
            'agendamiento.agendamiento.index',
            'agendamiento.agendamiento.create',
            'agendamiento.agendamiento.delete',
            'agendamiento.agendamiento.update',
            'agendamiento.agendamiento.interectuar',
            'gestion.pacientes.index',
            'gestion.pacientes.create',
            'gestion.pacientes.delete',
            'gestion.pacientes.update',
        ];

        foreach ($permisos as $nombre) {
            $permiso = Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
            $permiso->assignRole(['Administrador', 'Transcriptor']);
        }
    }
}
