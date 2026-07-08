<?php

namespace Database\Seeders;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use Illuminate\Database\Seeder;

class ConfigRecomendacionProductoSeeder extends Seeder
{
    public function run(): void
    {
        $recomendaciones = [
            '876802' => 'No requiere preparación. Asista con ropa cómoda. Evite usar desodorante, talco o cremas el día del examen. Traiga estudios anteriores si los tiene.',
            '870001' => 'No requiere preparación especial. Retire objetos metálicos de la cabeza (aretes, ganchos, etc.) antes del estudio.',
            '871121' => 'No requiere preparación. Use ropa cómoda. Para mujeres en edad fértil, informe si existe posibilidad de embarazo.',
            '872002' => 'No ingiera alimentos sólidos 6 horas antes del estudio. Puede tomar líquidos claros. No fume ni consuma goma de mascar.',
            '873121' => 'No requiere preparación especial. Vista ropa cómoda que permita descubrir el brazo.',
            '873420' => 'No requiere preparación especial. Vista ropa cómoda que permita descubrir la rodilla. Traiga estudios anteriores si los tiene.',
            '879111' => 'No requiere ayuno. Retire objetos metálicos (aretes, dentaduras postizas, horquillas). Informe al tecnólogo si tiene alergias al medio de contraste o problemas renales.',
            '879301' => 'No requiere ayuno. Retire objetos metálicos del tórax (collares, cadenas). Informe si tiene alergias al medio de contraste.',
            '879410' => 'Ayuno de 6 horas. No ingiera alimentos sólidos ni bebidas carbonatadas. Puede tomar agua. Informe si tiene alergias al medio de contraste.',
            '883101' => 'Ayuno de 4 horas. Retire todos los objetos metálicos (joyas, reloj, monedas, llaves, tarjetas magnéticas). Informe si tiene claustrofobia, marcapasos, implantes cocleares o clips de aneurisma.',
            '881302' => 'Ayuno de 6 horas. No ingiera alimentos sólidos. Puede tomar agua. Evite consumir bebidas carbonatadas o gaseosas 24 horas antes.',
        ];

        foreach ($recomendaciones as $codCups => $recomendacion) {
            $producto = ConfigProducto::where('cod_cups', $codCups)->first();
            if (! $producto) {
                continue;
            }

            ConfigRecomendacionProducto::updateOrCreate(
                ['producto_id' => $producto->id],
                [
                    'recomendacion' => $recomendacion,
                    'estado' => true,
                ]
            );
        }
    }
}
