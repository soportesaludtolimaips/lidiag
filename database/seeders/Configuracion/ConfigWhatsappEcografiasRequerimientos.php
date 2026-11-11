<?php

namespace Database\Seeders\Configuracion;

use Database\Seeders\ConfigRecomendacionProductoSeeder;
use Illuminate\Database\Seeder;

class ConfigWhatsappEcografiasRequerimientos extends Seeder
{
    /**
     * Run the database seeds.
     * Centralizado e integrado en ConfigRecomendacionProductoSeeder (fuente canónica).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function run(): void
    {
        $this->call(ConfigRecomendacionProductoSeeder::class);
    }
}
