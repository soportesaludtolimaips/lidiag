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
        Permission::create(['name' => 'config.consentimiento_informado.index']);
        Permission::create(['name' => 'config.consentimiento_informado.create']);
        Permission::create(['name' => 'config.consentimiento_informado.edit']);
        Permission::create(['name' => 'config.consentimiento_informado.destroy']);
    }
}
