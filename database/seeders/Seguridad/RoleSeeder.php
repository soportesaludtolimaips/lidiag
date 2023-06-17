<?php

namespace Database\Seeders\Seguridad;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $Rol1 = Role::create(['name' => 'Admintrador']);
        $Rol2 = Role::create(['name' => 'Medico']);
        $Rol3 = Role::create(['name' => 'Administrativo']);
        $Rol4 = Role::create(['name' => 'Transcriptor']);
        $Rol5 = Role::create(['name' => 'Estadistico']);

        /**
         * Seguridad
         */
        Permission::create(['name' => 'seguridad.usuarios.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.usuarios.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.usuarios.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.usuarios.show'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.usuarios.destroy'])->syncRoles([$Rol1]);

        Permission::create(['name' => 'seguridad.roles.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.roles.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.roles.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.roles.show'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'seguridad.roles.destroy'])->syncRoles([$Rol1]);

        /**
         * Configuración
         */
        Permission::create(['name' => 'config.admin.salud.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.admin.salud.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.admin.salud.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.admin.salud.destroy'])->syncRoles([$Rol1]);

        Permission::create(['name' => 'config.diagnosticos.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.diagnosticos.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.diagnosticos.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.diagnosticos.destroy'])->syncRoles([$Rol1]);

        Permission::create(['name' => 'config.prioridades.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.prioridades.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.prioridades.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.prioridades.destroy'])->syncRoles([$Rol1]);

        Permission::create(['name' => 'config.productos.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.productos.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.productos.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.productos.destroy'])->syncRoles([$Rol1]);

        Permission::create(['name' => 'config.sedes.index'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.sedes.create'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.sedes.edit'])->syncRoles([$Rol1]);
        Permission::create(['name' => 'config.sedes.destroy'])->syncRoles([$Rol1]);

        /**
         * Estudios
         */
        Permission::create(['name' => 'estudio.listarPendientesTrascribir'])->syncRoles([$Rol1, $Rol3, $Rol4]);
        Permission::create(['name' => 'estudio.guardarTranscripcion'])->syncRoles([$Rol1, $Rol3, $Rol4]);

        Permission::create(['name' => 'estudio.listarPendientesMedico'])->syncRoles([$Rol1, $Rol2, $Rol4]);
        Permission::create(['name' => 'estudio.leerEstudio'])->syncRoles([$Rol1, $Rol2]);

        /**
         * Reportes
         */
        Permission::create(['name' => 'reportes.indicadores'])->syncRoles([$Rol1, $Rol5]);
        Permission::create(['name' => 'reportes.detallados'])->syncRoles([$Rol1, $Rol5]);
    }
}
