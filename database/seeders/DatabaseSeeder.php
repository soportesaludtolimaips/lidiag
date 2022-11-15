<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\configuracion\ConfigAdminSaludSeeder;
use Database\Seeders\Configuracion\ConfigDiagnosticoSeeder;
use Database\Seeders\configuracion\ConfigPrioridadeSeeder;
use Database\Seeders\Configuracion\ConfigProductoSeeder;
use Database\Seeders\Configuracion\ConfigSucursalSeeder;
use Database\Seeders\Seguridad\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call(ConfigAdminSaludSeeder::class);
        $this->call(ConfigDiagnosticoSeeder::class);
        $this->call(ConfigPrioridadeSeeder::class);
        $this->call(ConfigProductoSeeder::class);
        $this->call(ConfigSucursalSeeder::class);

        $this->call(UserSeeder::class);
    }
}
