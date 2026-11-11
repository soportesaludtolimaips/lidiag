<?php

namespace Tests\Unit;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Services\Consentimientos\ConsentimientoDocumentoService;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pruebas de la lógica de plantillas de consentimientos (renderizado de
 * placeholders y encabezado institucional). No tocan la base de datos: usan
 * modelos en memoria con relaciones fijadas manualmente.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-09-02
 */
class ConsentimientoPlantillaRenderTest extends TestCase
{
    private function service(): ConsentimientoDocumentoService
    {
        return app(ConsentimientoDocumentoService::class);
    }

    private function invocarPrivado(string $metodo, array $args): string
    {
        $svc = $this->service();
        $ref = new ReflectionMethod($svc, $metodo);
        $ref->setAccessible(true);

        return (string) $ref->invoke($svc, ...$args);
    }

    private function documentoDemo(string $html): ConsentimientoDocumento
    {
        $doc = new ConsentimientoDocumento;
        $doc->plantilla_html = $html;
        $doc->num_docu = '111222333';
        $doc->nombres = 'JUAN';
        $doc->apellidos = 'PEREZ';

        $sede = new ConfigSede;
        $sede->nom_sede = 'Espinal';
        $sede->ciudad = 'Espinal';
        $doc->setRelation('sede', $sede);

        $producto = new ConfigProducto;
        $producto->nom_produc = 'RX DE TORAX';
        $producto->cod_cups = '870121';
        $doc->setRelation('producto', $producto);

        $doc->setRelation('medico', null);

        return $doc;
    }

    public function test_reemplaza_los_datos_del_paciente(): void
    {
        $doc = $this->documentoDemo('Paciente ${nombres} ${apellidos}, documento ${num_docu}.');

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        $this->assertStringContainsString('JUAN', $html);
        $this->assertStringContainsString('PEREZ', $html);
        $this->assertStringContainsString('111222333', $html);
        $this->assertStringNotContainsString('${nombres}', $html);
        $this->assertStringNotContainsString('${apellidos}', $html);
        $this->assertStringNotContainsString('${num_docu}', $html);
    }

    public function test_reemplaza_producto_y_sede(): void
    {
        $doc = $this->documentoDemo('Servicio ${servicio} en la sede ${sede}, ciudad ${ciudad}.');

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        $this->assertStringContainsString('RX DE TORAX', $html);
        $this->assertStringContainsString('Espinal', $html);
    }

    public function test_red_de_seguridad_reemplaza_placeholders_desconocidos(): void
    {
        $doc = $this->documentoDemo('Valor ${campo_inexistente} final.');

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        $this->assertStringNotContainsString('${campo_inexistente}', $html);
        $this->assertStringContainsString('__________', $html);
    }

    public function test_preserva_los_placeholders_de_firma(): void
    {
        $doc = $this->documentoDemo('Firma paciente: ${firma_paciente} — Firma médico: ${firma_medico}');

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        // Las firmas se incrustan luego como imagen; el render base NO debe borrarlas.
        $this->assertStringContainsString('${firma_paciente}', $html);
        $this->assertStringContainsString('${firma_medico}', $html);
    }

    public function test_escapa_el_marcado_de_los_datos_del_paciente(): void
    {
        $doc = $this->documentoDemo('Paciente ${nombres} ${apellidos}.');
        $doc->nombres = '<img src=x onerror=alert(1)>MUÑOZ';
        $doc->apellidos = 'PEREZ & <b>HIJOS</b>';

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        // El texto escrito por una persona nunca debe convertirse en etiquetas: este HTML se muestra
        // en la página pública de firma y se imprime en el PDF del documento legal.
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<b>', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;MUÑOZ', $html);
        $this->assertStringContainsString('PEREZ &amp; &lt;b&gt;HIJOS&lt;/b&gt;', $html);
    }

    public function test_escapa_tambien_sede_y_producto(): void
    {
        $doc = $this->documentoDemo('Servicio ${servicio} en la sede ${sede}.');
        $doc->sede->nom_sede = 'Clínica <Norte> & Cía';
        $doc->producto->nom_produc = 'RX <script>x</script>';

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<Norte>', $html);
        $this->assertStringContainsString('Clínica &lt;Norte&gt; &amp; Cía', $html);
        $this->assertStringContainsString('RX &lt;script&gt;x&lt;/script&gt;', $html);
    }

    public function test_las_comillas_del_paciente_no_rompen_un_atributo_de_la_plantilla(): void
    {
        $doc = $this->documentoDemo('<span title="${nombres}">x</span>');
        $doc->nombres = 'x" onmouseover="alert(1)';

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        $this->assertStringNotContainsString('" onmouseover="', $html);
        $this->assertStringContainsString('&quot;', $html);
    }

    public function test_los_datos_del_paciente_no_se_interpretan_como_placeholders(): void
    {
        $doc = $this->documentoDemo('Paciente ${nombres}. Firma: ${firma_paciente}');
        $doc->nombres = '${firma_paciente} ${sede}';

        $html = $this->invocarPrivado('renderHtml', [$doc]);

        // La única marca de firma es la que puso la plantilla, y el texto del paciente no se resuelve
        // como si fuera un campo (no debe aparecer el nombre de la sede, que la plantilla no usa).
        $this->assertSame(1, substr_count($html, '${firma_paciente}'));
        $this->assertStringNotContainsString('Espinal', $html);
    }

    public function test_encabezado_vacio_sin_sede(): void
    {
        $html = $this->invocarPrivado('encabezadoSedeHtml', [null, null]);

        $this->assertSame('', $html);
    }

    public function test_encabezado_incluye_institucion_y_codigo_version_anio(): void
    {
        $sede = new ConfigSede;
        $sede->nom_sede = 'Hospital San Juan Bautista';
        $sede->encabezado = "APOYO DIAGNÓSTICO\nNIT: 890.701.459";

        $plantilla = new ConsentimientoPlantilla;
        $plantilla->nombre = 'C.I. Tomografía Contrastada';
        $plantilla->codigo = 'ADT-GIM-FOR-006';
        $plantilla->version_formato = '02';
        $plantilla->anio_formato = '2025';

        $html = $this->invocarPrivado('encabezadoSedeHtml', [$sede, $plantilla]);

        $this->assertStringContainsString('Hospital San Juan Bautista', $html);
        $this->assertStringContainsString('C.I. Tomografía Contrastada', $html);
        $this->assertStringContainsString('ADT-GIM-FOR-006', $html);
        $this->assertStringContainsString('02', $html);
        $this->assertStringContainsString('2025', $html);
    }
}
