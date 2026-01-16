<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermisionConfigTipoDocumentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Permission::create(['name' => 'config.tipo.documento.index']);
        Permission::create(['name' => 'config.tipo.documento.create']);
        Permission::create(['name' => 'config.tipo.documento.edit']);
        Permission::create(['name' => 'config.tipo.documento.destroy']);
    }
}
