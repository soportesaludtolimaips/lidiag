<?php

namespace Database\Seeders\HelpDesk;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HelpDeskCatalogosSeeder extends Seeder
{
    public function run(): void
    {
        // Categorías iniciales
        foreach (['Hardware', 'Software', 'Red', 'Otros'] as $nombre) {
            DB::table('help_desk_categorias')->updateOrInsert(
                ['nombre' => $nombre],
                ['activo' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Prioridades iniciales (orden para sorting por defecto)
        $prioridades = [
            ['nombre' => 'Alta', 'orden' => 1],
            ['nombre' => 'Media', 'orden' => 2],
            ['nombre' => 'Baja', 'orden' => 3],
        ];
        foreach ($prioridades as $row) {
            DB::table('help_desk_prioridades')->updateOrInsert(
                ['nombre' => $row['nombre']],
                ['orden' => $row['orden'], 'activo' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Estados base fijos (no editables/borrables)
        $estados = [
            ['nombre' => 'Abierto', 'orden' => 1],
            ['nombre' => 'En Progreso', 'orden' => 2],
            ['nombre' => 'Resuelto', 'orden' => 3],
            ['nombre' => 'Cerrado', 'orden' => 4],
        ];
        foreach ($estados as $row) {
            DB::table('help_desk_estados')->updateOrInsert(
                ['nombre' => $row['nombre']],
                ['orden' => $row['orden'], 'activo' => 1, 'es_base' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
