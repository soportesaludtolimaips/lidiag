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
        Permission::create(['name' => 'agendamiento.disponibilidad.index'])->assignRole(['Administrador', 'Transcriptor']); // Ver otras plataformas
        Permission::create(['name' => 'agendamiento.disponibilidad.create'])->assignRole(['Administrador', 'Transcriptor']); // Ver otras plataformas
        Permission::create(['name' => 'agendamiento.disponibilidad.delete'])->assignRole(['Administrador', 'Transcriptor']); // Ver otras plataformas
        Permission::create(['name' => 'agendamiento.disponibilidad.update'])->assignRole(['Administrador', 'Transcriptor']); // Ver otras plataformas

        Permission::create(['name' => 'agendamiento.solicitudes.index'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.solicitudes.create'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.solicitudes.delete'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.solicitudes.update'])->assignRole(['Administrador', 'Transcriptor']);

        Permission::create(['name' => 'agendamiento.agendamiento.index'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.agendamiento.create'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.agendamiento.delete'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.agendamiento.update'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'agendamiento.agendamiento.interectuar'])->assignRole(['Administrador', 'Transcriptor']); // Intereactua con el paciente por medio de la api de whatsapp

        Permission::create(['name' => 'gestion.pacientes.index'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'gestion.pacientes.create'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'gestion.pacientes.delete'])->assignRole(['Administrador', 'Transcriptor']);
        Permission::create(['name' => 'gestion.pacientes.update'])->assignRole(['Administrador', 'Transcriptor']);
    }
}
