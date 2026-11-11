<?php

namespace Tests\Feature\Entrega;

use App\Services\Entrega\ContextoEstudio;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El contexto de la entrega sale de la BD (estudio → paciente y sede). Corre contra `lidiag_test`.
 */
class ContextoEstudioTest extends TestCase
{
    use DatabaseTransactions;

    /** Inserta el mínimo de filas (sin las tablas de las que solo dependen por llave foránea) y devuelve el id del producto. */
    private function estudio(string $numDocu, ?string $nombreSede, string $studyIuid): int
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            $sedeId = $nombreSede === null ? null : DB::table('config_sedes')->insertGetId(['nom_sede' => $nombreSede]);
            $pacienteId = DB::table('pacientes')->insertGetId(['num_docu' => $numDocu, 'nombres' => 'RIOS PEREZ MARIA FERNANDA']);
            $estudioId = DB::table('estudios')->insertGetId([
                'study_pk' => random_int(1, PHP_INT_MAX >> 8), 'study_iuid' => $studyIuid, 'fec_estudio' => now(),
                'paciente_id' => $pacienteId, 'medico_id' => 1, 'quien_registro_id' => 1, 'sede_id' => $sedeId ?? 0, 'prioridad_id' => 1,
            ]);

            return DB::table('estudios_productos')->insertGetId(['estudio_id' => $estudioId, 'producto_id' => 1, 'nom_produc' => 'RX DE TORAX']);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function test_resuelve_el_paciente_la_sede_y_el_uid_del_estudio(): void
    {
        $id = $this->estudio('  1098765432 ', 'Sede de prueba', '1.2.840.99');

        $contexto = (new ContextoEstudio)->resolver($id);

        $this->assertSame('1098765432', $contexto['num_docu']); // sin espacios
        $this->assertSame('RIOS PEREZ MARIA FERNANDA', $contexto['nom_pacien']);
        $this->assertSame('Sede de prueba', $contexto['nom_sede']);
        $this->assertSame('1.2.840.99', $contexto['study_iuid']);
        $this->assertIsInt($contexto['sede_id']);
    }

    public function test_un_estudio_sin_sede_valida_devuelve_la_sede_sin_nombre(): void
    {
        $id = $this->estudio('555', null, '1.2.3');

        $contexto = (new ContextoEstudio)->resolver($id);

        $this->assertSame('555', $contexto['num_docu']);
        $this->assertNull($contexto['nom_sede']);
    }

    public function test_un_estudio_que_no_existe_devuelve_null(): void
    {
        $this->assertNull((new ContextoEstudio)->resolver(987654321));
    }
}
