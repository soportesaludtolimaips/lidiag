<?php

namespace Tests\Unit;

use App\Services\TranscripcionService;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Pruebas de la conversión de las tres órdenes habladas que entiende el texto que devuelve Whisper:
 * «punto y coma» (;), «punto y aparte» (punto y párrafo nuevo) y «espacio» (solo «dar espacio» o aislado
 * por la puntuación que Whisper pone en las pausas, para no tocar «espacio pleural» ni «espacio discal»).
 * No tocan la base de datos.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-11-10
 */
class TranscripcionOrdenesHabladasTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function conversiones(): array
    {
        return [
            // ---- punto y coma ----
            'punto y coma sin puntuacion de Whisper' => ['Derrame pleural punto y coma sin neumotórax.', 'Derrame pleural; sin neumotórax.'],
            'punto y coma entre comas' => ['Derrame pleural, punto y coma, sin neumotórax.', 'Derrame pleural; sin neumotórax.'],
            'punto y coma como frase propia (Whisper pone puntos)' => ['Derrame pleural. Punto y coma. Sin neumotórax.', 'Derrame pleural; sin neumotórax.'],
            'punto y coma con coma antes y punto despues' => ['Derrame pleural, punto y coma. Sin neumotórax', 'Derrame pleural; sin neumotórax'],
            'punto y coma en mayusculas' => ['Derrame pleural PUNTO Y COMA sin neumotórax.', 'Derrame pleural; sin neumotórax.'],
            'punto y coma con espacios de mas' => ['Derrame pleural punto  y   coma sin neumotórax.', 'Derrame pleural; sin neumotórax.'],
            'punto y coma conserva un nombre propio si no hubo punto' => ['Hallazgos punto y coma Doppler normal.', 'Hallazgos; Doppler normal.'],
            'punto y coma no baja a minuscula una sigla' => ['Hallazgos. Punto y coma. TC normal.', 'Hallazgos; TC normal.'],
            'punto y coma al final' => ['Derrame punto y coma', 'Derrame;'],
            // ---- punto y aparte ----
            'punto y aparte sin puntuacion' => ['No se observan lesiones punto y aparte conclusión estudio normal.', "No se observan lesiones.\n\nConclusión estudio normal."],
            'punto y aparte entre comas' => ['No se observan lesiones, punto y aparte, conclusión estudio normal.', "No se observan lesiones.\n\nConclusión estudio normal."],
            'punto y aparte como frase propia' => ['No se observan lesiones. Punto y aparte. Conclusión estudio normal.', "No se observan lesiones.\n\nConclusión estudio normal."],
            'variante punto aparte' => ['No se observan lesiones punto aparte conclusión normal.', "No se observan lesiones.\n\nConclusión normal."],
            'variante punto a parte' => ['No se observan lesiones punto a parte conclusión normal.', "No se observan lesiones.\n\nConclusión normal."],
            'variante punto y a parte' => ['No se observan lesiones punto y a parte conclusión normal.', "No se observan lesiones.\n\nConclusión normal."],
            'varios puntos y aparte' => ['Hallazgos normales punto y aparte impresión punto y aparte recomendaciones', "Hallazgos normales.\n\nImpresión.\n\nRecomendaciones"],
            'punto y aparte pone en mayuscula una letra con tilde' => ['Lesiones punto y aparte énfasis normal', "Lesiones.\n\nÉnfasis normal"],
            'punto y aparte al final no deja parrafos vacios' => ['Estudio normal punto y aparte', 'Estudio normal.'],
            // ---- espacio ----
            'dar espacio' => ['Cardiomegalia dar espacio grado dos', 'Cardiomegalia grado dos'],
            'espacio aislado entre comas' => ['Cardiomegalia, espacio, grado dos.', 'Cardiomegalia grado dos.'],
            'espacio aislado como frase propia' => ['Cardiomegalia. Espacio. Grado dos.', 'Cardiomegalia grado dos.'],
            // ---- las tres juntas ----
            'las tres ordenes en una frase' => ['Hallazgos punto y coma sin derrame punto y aparte impresión diagnóstica normal.', "Hallazgos; sin derrame.\n\nImpresión diagnóstica normal."],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function textosQueNoSeTocan(): array
    {
        return [
            'espacio pleural' => ['Derrame en el espacio pleural, sin neumotórax.'],
            'espacio discal' => ['Disminución del espacio discal L4-L5.'],
            'espacios en plural al inicio' => ['Espacios intervertebrales conservados.'],
            'dos espacios con coma' => ['Se observan dos espacios, uno anterior y otro posterior.'],
            'un espacio libre' => ['Hay un espacio libre.'],
            'espacio sin puntuacion alrededor' => ['Cardiomegalia espacio grado dos'],
            'punto como palabra' => ['El punto de entrada del catéter es adecuado.'],
            'punto y seguido no es una de las tres' => ['Sin lesiones punto y seguido sin derrame'],
            'la palabra coma' => ['Paciente en coma con lesión difusa.'],
            'a parte de' => ['Aparte de esto, a parte del estudio previo, no hay cambios.'],
            'texto con saltos de linea y sin ordenes' => ["Línea uno.\n\nLínea dos.\n"],
            'texto vacio' => [''],
        ];
    }

    #[DataProvider('conversiones')]
    public function test_convierte_las_ordenes_habladas(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, TranscripcionService::convertirOrdenesHabladas($entrada));
    }

    #[DataProvider('conversiones')]
    public function test_aplicarla_dos_veces_no_cambia_el_resultado(string $entrada, string $esperado): void
    {
        $una = TranscripcionService::convertirOrdenesHabladas($entrada);

        $this->assertSame($una, TranscripcionService::convertirOrdenesHabladas($una));
    }

    #[DataProvider('textosQueNoSeTocan')]
    public function test_no_modifica_el_texto_clinico_que_no_trae_ordenes(string $texto): void
    {
        $this->assertSame($texto, TranscripcionService::convertirOrdenesHabladas($texto));
    }

    public function test_el_servicio_entrega_la_transcripcion_ya_convertida(): void
    {
        Http::fake([
            '*/transcribir' => Http::response([
                'texto' => 'Derrame pleural, punto y coma, sin neumotórax. Punto y aparte. Conclusión normal.',
                'duracion' => 4.2,
                'idioma' => 'es',
            ]),
        ]);
        $audio = tempnam(sys_get_temp_dir(), 'aud');
        file_put_contents($audio, 'audio de prueba');

        try {
            $resultado = (new TranscripcionService)->transcribir($audio);
        } finally {
            @unlink($audio);
        }

        $this->assertSame("Derrame pleural; sin neumotórax.\n\nConclusión normal.", $resultado['texto']);
        $this->assertSame(4.2, $resultado['duracion']);
        $this->assertSame('es', $resultado['idioma']);
    }
}
