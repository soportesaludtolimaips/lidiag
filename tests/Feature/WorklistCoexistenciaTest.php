<?php

namespace Tests\Feature;

use App\Models\Agenda\AgendaCita;
use App\Services\Dcm4chee\WorklistService as WorklistAdmision;
use App\Services\Worklist\WorklistService as WorklistAgenda;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Las dos formas de enviar un paciente a la lista de trabajo del equipo conviven:
 *  - desde la AGENDA (HL7/MLLP, al asignar la cita): App\Services\Worklist\WorklistService
 *  - desde la ADMISIÓN (directo a la BD del PACS): App\Services\Dcm4chee\WorklistService
 * La admisión no debe crear una segunda orden para un paciente cuya cita de hoy ya está en la lista.
 * Corre contra `lidiag_test` (los PACS de las pruebas son direcciones locales sin servicio).
 */
class WorklistCoexistenciaTest extends TestCase
{
    use DatabaseTransactions;

    private int $sedeId;

    private int $pacienteId;

    private int $productoId;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            // Puerto 1 en la propia máquina: si algo intentara conectarse, la conexión se rechaza al instante.
            $this->sedeId = (int) DB::table('config_sedes')->insertGetId([
                'nom_sede' => 'Sede de prueba worklist', 'ip_dcm' => '127.0.0.1', 'puerto_dcm' => 1, 'bd_dcm' => 'pacsdb',
                'usuario_dcm' => 'x', 'password_dcm' => 'x', 'worklist_activo' => 1,
            ]);
            $this->pacienteId = (int) DB::table('pacientes')->insertGetId(['num_docu' => '1098765432', 'nombres' => 'PACIENTE DE PRUEBA']);
            $this->productoId = (int) DB::table('config_productos')->insertGetId(['cod_cups' => '871101', 'nom_produc' => 'RX DE TORAX']);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function cita(array $extra = []): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            return (int) DB::table('agenda_citas')->insertGetId(array_merge([
                'sede_id' => $this->sedeId, 'servicio_id' => 1, 'paciente_id' => $this->pacienteId, 'producto_id' => $this->productoId,
                'estado' => 'asignada', 'fecha_hora_inicio' => Carbon::today()->setTime(10, 0), 'fecha_hora_fin' => Carbon::today()->setTime(10, 30),
            ], $extra));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function test_la_cita_asignada_de_hoy_se_detecta_como_ya_enviada_desde_la_agenda(): void
    {
        $id = $this->cita();

        $encontrada = app(WorklistAgenda::class)->citaDeHoyEnLista($this->pacienteId, $this->sedeId);

        $this->assertInstanceOf(AgendaCita::class, $encontrada);
        $this->assertSame($id, $encontrada->id);
    }

    public function test_no_hay_cita_en_lista_si_es_de_otro_dia_otro_estado_otra_sede_o_la_sede_no_usa_hl7(): void
    {
        $servicio = app(WorklistAgenda::class);

        $this->cita(['fecha_hora_inicio' => Carbon::tomorrow()->setTime(10, 0), 'fecha_hora_fin' => Carbon::tomorrow()->setTime(10, 30)]);
        $this->cita(['estado' => 'solicitada']);
        $this->cita(['estado' => 'cancelada']);
        $this->assertNull($servicio->citaDeHoyEnLista($this->pacienteId, $this->sedeId));

        // Otra sede
        $this->assertNull($servicio->citaDeHoyEnLista($this->pacienteId, $this->sedeId + 999));

        // La sede no envía por HL7 (worklist_activo apagado): la cita no está en la lista.
        $this->cita();
        DB::table('config_sedes')->where('id', $this->sedeId)->update(['worklist_activo' => 0]);
        $this->assertNull($servicio->citaDeHoyEnLista($this->pacienteId, $this->sedeId));
    }

    public function test_la_admision_no_crea_una_segunda_orden_si_la_cita_de_hoy_ya_esta_en_la_lista(): void
    {
        $this->cita();
        $ordenesAntes = DB::table('worklist_ordenes')->count();

        $resultado = app(WorklistAdmision::class)->enviarAWorklist([
            'paciente_id' => $this->pacienteId, 'sede_id' => $this->sedeId, 'producto_id' => $this->productoId, 'accession_no' => 'RX-PRUEBA-1',
        ], 1);

        $this->assertFalse($resultado['success']);
        $this->assertSame(409, $resultado['status']);
        $this->assertStringContainsString('ya está en la lista de trabajo', $resultado['message']);
        $this->assertSame($ordenesAntes, DB::table('worklist_ordenes')->count());
    }

    public function test_con_forzar_la_admision_sigue_adelante_aunque_la_cita_ya_este_en_la_lista(): void
    {
        $this->cita();

        // No hay PACS en 127.0.0.1:1: el envío falla al conectar, pero ya no por el bloqueo de duplicados (409).
        $resultado = app(WorklistAdmision::class)->enviarAWorklist([
            'paciente_id' => $this->pacienteId, 'sede_id' => $this->sedeId, 'producto_id' => $this->productoId,
            'accession_no' => 'RX-PRUEBA-2', 'forzar' => true,
        ], 1);

        $this->assertNotSame(409, $resultado['status']);
    }

    public function test_un_paciente_sin_cita_de_hoy_pasa_por_la_admision_con_normalidad(): void
    {
        // Sin cita no hay bloqueo: la admisión intenta conectar con el PACS (aquí inexistente) y falla por eso, no por duplicado.
        $resultado = app(WorklistAdmision::class)->enviarAWorklist([
            'paciente_id' => $this->pacienteId, 'sede_id' => $this->sedeId, 'producto_id' => $this->productoId, 'accession_no' => 'RX-PRUEBA-3',
        ], 1);

        $this->assertNotSame(409, $resultado['status']);
    }
}
