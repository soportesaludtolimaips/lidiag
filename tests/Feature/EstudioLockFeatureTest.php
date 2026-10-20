<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pruebas de integración para EstudioLockController y concurrencia PACS.
 *
 * Flujo del proceso:
 * 1. Usuario autenticado toma una reserva de asignación con study_iuid.
 * 2. El mismo usuario renueva la reserva sin conflicto.
 * 3. Un segundo usuario intenta tomar la misma reserva y recibe HTTP 409 Conflict.
 * 4. El primer usuario libera la reserva y el segundo usuario puede tomarla.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
class EstudioLockFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $medico1;

    protected User $medico2;

    protected string $testIuid;

    protected function setUp(): void
    {
        parent::setUp();

        $permiso = Permission::firstOrCreate([
            'name' => 'estudio.asignar',
            'guard_name' => 'web',
        ]);

        $this->medico1 = User::factory()->create([
            'password_changed_at' => now(),
        ]);
        $this->medico1->givePermissionTo($permiso);

        $this->medico2 = User::factory()->create([
            'password_changed_at' => now(),
        ]);
        $this->medico2->givePermissionTo($permiso);

        $this->testIuid = '1.2.840.113619.2.55.'.uniqid();
    }

    public function test_usuario_puede_tomar_y_renovar_reserva_de_asignacion(): void
    {
        $payload = [
            'tipo' => 'asignacion',
            'study_iuid' => $this->testIuid,
        ];

        // 1. Tomar reserva
        $res = $this->actingAs($this->medico1)->postJson('/api/estudio-lock/tomar', $payload);
        $res->assertStatus(200);
        $res->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseHas('estudio_locks', [
            'tipo' => 'asignacion',
            'clave' => 'iuid:'.$this->testIuid,
            'user_id' => $this->medico1->id,
        ]);

        // 2. Renovar reserva por el mismo usuario
        $resRenovacion = $this->actingAs($this->medico1)->postJson('/api/estudio-lock/tomar', $payload);
        $resRenovacion->assertStatus(200);
        $resRenovacion->assertJson([
            'success' => true,
        ]);
    }

    public function test_segundo_usuario_recibe_409_conflicto_si_esta_reservado(): void
    {
        $payload = [
            'tipo' => 'asignacion',
            'study_iuid' => $this->testIuid,
        ];

        // Medico 1 toma la reserva
        $this->actingAs($this->medico1)->postJson('/api/estudio-lock/tomar', $payload)->assertStatus(200);

        // Medico 2 intenta tomar la misma reserva
        $resConflicto = $this->actingAs($this->medico2)->postJson('/api/estudio-lock/tomar', $payload);
        $resConflicto->assertStatus(409);
        $resConflicto->assertJson([
            'success' => false,
        ]);
    }

    public function test_usuario_puede_liberar_reserva(): void
    {
        $payload = [
            'tipo' => 'asignacion',
            'study_iuid' => $this->testIuid,
        ];

        // Medico 1 toma la reserva
        $this->actingAs($this->medico1)->postJson('/api/estudio-lock/tomar', $payload)->assertStatus(200);

        // Medico 1 libera la reserva
        $resLiberar = $this->actingAs($this->medico1)->postJson('/api/estudio-lock/liberar', $payload);
        $resLiberar->assertStatus(200);

        $this->assertDatabaseMissing('estudio_locks', [
            'tipo' => 'asignacion',
            'clave' => 'iuid:'.$this->testIuid,
        ]);

        // Ahora Medico 2 puede tomarla sin conflicto
        $resMedico2 = $this->actingAs($this->medico2)->postJson('/api/estudio-lock/tomar', $payload);
        $resMedico2->assertStatus(200);
    }
}
