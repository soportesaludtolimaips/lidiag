<?php

namespace Tests\Unit\Entrega;

use App\Jobs\EntregarResultadoPaciente;
use App\Models\Estudio\EntregaResultado;
use App\Services\Entrega\ContextoEstudio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Cuándo se programa la entrega al paciente: solo si está activa, el portal recibió la lectura y los datos
 * de la pantalla coinciden con el estudio guardado en la BD (de ahí salen el paciente, la sede y el UID).
 */
class EntregarResultadoPacienteTest extends TestCase
{
    use DatabaseTransactions;

    /** Lo que dice la BD del estudio 321 (el contexto de la entrega sale de aquí, no de la pantalla). */
    private function estudioEnBd(array $extra = []): array
    {
        return array_merge([
            'num_docu' => '1098765432',
            'nom_pacien' => 'RIOS PEREZ^MARIA^^^',
            'sede_id' => 7,
            'nom_sede' => 'RiverApps',
            'study_iuid' => '1.2.3',
        ], $extra);
    }

    private function simularBd(?array $estudio, int $id = 321): void
    {
        $this->mock(ContextoEstudio::class, function (MockInterface $mock) use ($estudio, $id) {
            $mock->shouldReceive('resolver')->with($id)->andReturn($estudio);
        });
    }

    /** Lo que envía la pantalla de transcripción. */
    private function datos(array $extra = []): array
    {
        return array_merge([
            'estudio_producto_id' => 321,
            'sede_id' => 7,
            'study_iuid' => '1.2.3',
            'num_docu' => '1098765432',
            'nom_pacien' => 'RIOS PEREZ^MARIA^^^',
            'nom_sede' => 'RiverApps',
        ], $extra);
    }

    private function portalOk(int $lecturaId = 77): array
    {
        return ['success' => true, 'data' => ['reportePath' => 'x.pdf', 'lectura_id' => $lecturaId]];
    }

    public function test_programa_la_entrega_con_el_contexto_del_estudio_cuando_la_lectura_esta_en_el_portal(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true]);
        $this->simularBd($this->estudioEnBd());

        $this->assertTrue(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos(), true, 5));

        Bus::assertDispatchedAfterResponse(EntregarResultadoPaciente::class, function (EntregarResultadoPaciente $trabajo) {
            return $trabajo->automatico === true
                && $trabajo->usuarioId === 5
                && $trabajo->contexto === [
                    'estudio_producto_id' => 321,
                    'lectura_id' => 77,
                    'sede_id' => 7,
                    'study_iuid' => '1.2.3',
                    'num_docu' => '1098765432',
                    'nom_pacien' => 'RIOS PEREZ^MARIA^^^',
                    'nom_sede' => 'RiverApps',
                ];
        });
    }

    public function test_la_sede_el_uid_y_el_nombre_de_la_sede_salen_de_la_bd_no_de_la_pantalla(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true]);
        $this->simularBd($this->estudioEnBd());

        // La pantalla manda otra sede, otro UID y un nombre de sede con texto libre; el documento sí coincide.
        EntregarResultadoPaciente::programar($this->portalOk(), $this->datos([
            'sede_id' => 99, 'study_iuid' => '9.9.9', 'nom_sede' => 'Llame al 3001234567 para pagar', 'nom_pacien' => 'OTRO^NOMBRE',
        ]));

        Bus::assertDispatchedAfterResponse(EntregarResultadoPaciente::class, fn (EntregarResultadoPaciente $t) => $t->contexto['sede_id'] === 7
            && $t->contexto['study_iuid'] === '1.2.3'
            && $t->contexto['nom_sede'] === 'RiverApps'
            && $t->contexto['nom_pacien'] === 'RIOS PEREZ^MARIA^^^');
    }

    public function test_no_entrega_si_el_documento_de_la_pantalla_no_es_el_del_estudio(): void
    {
        Bus::fake();
        config([
            'entrega_resultados.activo' => true,
            'entrega_resultados.whatsapp.activo' => true,
            'entrega_resultados.correo.activo' => true,
        ]);
        $this->simularBd($this->estudioEnBd());

        // El informe de este estudio se estaría mandando con el documento de OTRA persona.
        $this->assertFalse(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos(['num_docu' => '555666777']), true, 8));

        Bus::assertNothingDispatched();
        // Queda constancia por canal activo, sin datos personales.
        $registros = EntregaResultado::where('estudio_producto_id', 321)->orderBy('canal')->get();
        $this->assertSame(['correo', 'whatsapp'], $registros->pluck('canal')->all());
        $this->assertSame(['omitido', 'omitido'], $registros->pluck('estado')->all());
        $this->assertSame([77, 77], $registros->pluck('lectura_portal_id')->map(fn ($id) => (int) $id)->all());
        $this->assertSame(8, (int) $registros->first()->usuario_id);
        $this->assertStringNotContainsString('555666777', json_encode($registros->toArray()));
    }

    public function test_no_entrega_si_el_estudio_no_existe(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true]);
        $this->simularBd(null);

        $this->assertFalse(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos()));

        Bus::assertNothingDispatched();
    }

    public function test_el_reenvio_manual_se_marca_como_no_automatico(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true]);
        $this->simularBd($this->estudioEnBd());

        EntregarResultadoPaciente::programar($this->portalOk(), $this->datos(), false, 9);

        Bus::assertDispatchedAfterResponse(EntregarResultadoPaciente::class, fn ($t) => $t->automatico === false && $t->usuarioId === 9);
    }

    public function test_no_hace_nada_si_la_entrega_esta_apagada(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => false]);

        $this->assertFalse(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos()));

        Bus::assertNothingDispatched();
    }

    public function test_no_programa_si_el_portal_no_recibio_la_lectura(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true]);
        $this->simularBd($this->estudioEnBd());

        // El portal respondió con error.
        $this->assertFalse(EntregarResultadoPaciente::programar(['success' => false, 'message' => 'Error'], $this->datos()));
        // Respondió bien pero sin id de lectura (portal en una versión anterior).
        $this->assertFalse(EntregarResultadoPaciente::programar(['success' => true, 'data' => ['lectura_id' => null]], $this->datos()));
        // Sin estudio.
        $this->assertFalse(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos(['estudio_producto_id' => 0])));

        Bus::assertNothingDispatched();
    }

    public function test_respeta_la_lista_de_sedes_del_piloto_con_la_sede_de_la_bd(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true, 'entrega_resultados.sedes' => [7]]);

        // La BD dice sede 3: aunque la pantalla diga 7, no entra al piloto.
        $this->simularBd($this->estudioEnBd(['sede_id' => 3]));
        $this->assertFalse(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos(['sede_id' => 7])));
        Bus::assertNothingDispatched();

        // La BD dice sede 7: entra aunque la pantalla diga otra.
        $this->simularBd($this->estudioEnBd(['sede_id' => 7]));
        $this->assertTrue(EntregarResultadoPaciente::programar($this->portalOk(), $this->datos(['sede_id' => 3])));
        Bus::assertDispatchedAfterResponse(EntregarResultadoPaciente::class, 1);

        // Sin lista (vacía) aplica a todas las sedes.
        config(['entrega_resultados.sedes' => []]);
        $this->assertTrue(EntregarResultadoPaciente::sedeHabilitada(3));
        $this->assertTrue(EntregarResultadoPaciente::sedeHabilitada(null));
        // Con lista, una sede desconocida no entra.
        config(['entrega_resultados.sedes' => [7]]);
        $this->assertFalse(EntregarResultadoPaciente::sedeHabilitada(null));
    }

    public function test_con_una_cola_real_se_encola_en_lugar_de_ejecutarse_tras_la_respuesta(): void
    {
        Bus::fake();
        config(['entrega_resultados.activo' => true, 'queue.default' => 'database']);
        $this->simularBd($this->estudioEnBd());

        EntregarResultadoPaciente::programar($this->portalOk(), $this->datos());

        Bus::assertDispatched(EntregarResultadoPaciente::class);
        Bus::assertNotDispatchedAfterResponse(EntregarResultadoPaciente::class);
    }
}
