<?php

namespace Tests\Unit\Rules;

use App\Http\Requests\Estudios\GuardarTranscripcionRequest;
use App\Rules\SinCaracteresDeRuta;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * El documento y el nombre del paciente forman parte del nombre de los archivos del informe: no pueden
 * llevar caracteres de ruta (sería un recorrido de directorios al leer, escribir o borrar el archivo).
 */
class SinCaracteresDeRutaTest extends TestCase
{
    /** @return array<string, array{string}> */
    public static function valoresPeligrosos(): array
    {
        return [
            'subir un nivel (barra)' => ['../../../etc/passwd'],
            'subir un nivel (contrabarra, Windows)' => ['..\\..\\Windows\\win'],
            'barra en medio' => ['123/456'],
            'contrabarra en medio' => ['123\\456'],
            'unidad de Windows' => ['C:secreto'],
            'flujo alterno de NTFS' => ['informe.pdf:oculto'],
            'comodines' => ['12*34'],
            'interrogación' => ['12?34'],
            'comillas' => ['12"34'],
            'menor y mayor' => ['<12>'],
            'barra vertical' => ['12|34'],
            'byte nulo' => ["12\0.pdf"],
            'salto de línea' => ["12\n34"],
        ];
    }

    /** @return array<string, array{string}> */
    public static function valoresLegitimos(): array
    {
        return [
            'cédula' => ['1098765432'],
            'pasaporte' => ['AB-123.456'],
            'nombre en formato DICOM' => ['RIOS PEREZ^MARIA FERNANDA^^^'],
            'tildes y eñes' => ['MUÑOZ GÓMEZ'],
            'apóstrofo y guion' => ["O'NEIL-PÉREZ"],
        ];
    }

    private function pasa(string $valor): bool
    {
        return Validator::make(['campo' => $valor], ['campo' => [new SinCaracteresDeRuta]])->passes();
    }

    #[DataProvider('valoresPeligrosos')]
    public function test_rechaza_los_caracteres_de_ruta(string $valor): void
    {
        $this->assertFalse($this->pasa($valor));
    }

    #[DataProvider('valoresLegitimos')]
    public function test_acepta_documentos_y_nombres_legitimos(string $valor): void
    {
        $this->assertTrue($this->pasa($valor));
    }

    public function test_la_pantalla_de_transcripcion_aplica_la_regla_al_documento_y_al_nombre(): void
    {
        $reglas = (new GuardarTranscripcionRequest)->rules();

        $validador = Validator::make(
            ['num_docu' => '../../x', 'nom_pacien' => '..\\..\\y'],
            ['num_docu' => $reglas['num_docu'], 'nom_pacien' => $reglas['nom_pacien']]
        );

        $this->assertTrue($validador->fails());
        $this->assertArrayHasKey('num_docu', $validador->errors()->toArray());
        $this->assertArrayHasKey('nom_pacien', $validador->errors()->toArray());
    }
}
