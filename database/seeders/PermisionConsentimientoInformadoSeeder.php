<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisionConsentimientoInformadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            'config.consentimiento_informado.index',
            'config.consentimiento_informado.create',
            'config.consentimiento_informado.edit',
            'config.consentimiento_informado.destroy',
        ];

        foreach ($permisos as $nombre) {
            Permission::firstOrCreate([
                'name' => $nombre,
                'guard_name' => 'web',
            ]);
        }
    }
}
