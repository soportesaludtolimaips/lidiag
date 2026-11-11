<?php

namespace Tests\Unit\Entrega;

use App\Models\Configuracion\ConfigSede;
use App\Services\Entrega\ImagenesEstudioPacs;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Numeración de series/imágenes y descarga JPEG por WADO (sin tocar ningún PACS real).
 */
class ImagenesEstudioPacsTest extends TestCase
{
    private function sede(): ConfigSede
    {
        return new ConfigSede(['ip_dcm' => '10.9.8.7']);
    }

    public function test_numera_series_e_imagenes_en_el_orden_recibido(): void
    {
        $filas = [
            ['series_iuid' => 'S-A', 'sop_iuid' => 'a1'],
            ['series_iuid' => 'S-A', 'sop_iuid' => 'a2'],
            ['series_iuid' => 'S-B', 'sop_iuid' => 'b1'],
            ['series_iuid' => 'S-A', 'sop_iuid' => 'a3'],
            ['series_iuid' => 'S-B', 'sop_iuid' => 'b2'],
        ];

        $resultado = (new ImagenesEstudioPacs)->numerar($filas);

        $this->assertSame([1, 1], [$resultado[0]['serie_orden'], $resultado[0]['orden']]);
        $this->assertSame([1, 2], [$resultado[1]['serie_orden'], $resultado[1]['orden']]);
        $this->assertSame([2, 1], [$resultado[2]['serie_orden'], $resultado[2]['orden']]);
        $this->assertSame([1, 3], [$resultado[3]['serie_orden'], $resultado[3]['orden']]);
        $this->assertSame([2, 2], [$resultado[4]['serie_orden'], $resultado[4]['orden']]);
        $this->assertSame('a3', $resultado[3]['sop_iuid']);
    }

    public function test_numerar_con_lista_vacia_devuelve_vacio(): void
    {
        $this->assertSame([], (new ImagenesEstudioPacs)->numerar([]));
    }

    public function test_descarga_la_imagen_jpeg_por_wado_del_pacs_de_la_sede(): void
    {
        $jpeg = "\xFF\xD8\xFF\xE0".random_bytes(40);
        Http::fake(['*' => Http::response($jpeg, 200)]);

        $bytes = (new ImagenesEstudioPacs)->descargar($this->sede(), '1.2.3', ['series_iuid' => '1.2.3.4', 'sop_iuid' => '1.2.3.4.5']);

        $this->assertSame($jpeg, $bytes);
        Http::assertSent(function (Request $peticion) {
            return str_starts_with($peticion->url(), 'http://10.9.8.7:8080/wado?')
                && str_contains($peticion->url(), 'objectUID=1.2.3.4.5')
                && str_contains($peticion->url(), 'contentType=image/jpeg');
        });
    }

    public function test_descartar_lo_que_el_pacs_devuelve_si_no_es_un_jpeg(): void
    {
        // Algunos PACS contestan 200 con una página de error en HTML.
        Http::fake(['*' => Http::response('<html>Error</html>', 200)]);

        $this->assertNull((new ImagenesEstudioPacs)->descargar($this->sede(), '1.2.3', ['series_iuid' => 's', 'sop_iuid' => 'o']));
    }

    public function test_devuelve_null_si_el_pacs_falla(): void
    {
        Http::fake(['*' => Http::response('', 500)]);

        $this->assertNull((new ImagenesEstudioPacs)->descargar($this->sede(), '1.2.3', ['series_iuid' => 's', 'sop_iuid' => 'o']));
    }
}
