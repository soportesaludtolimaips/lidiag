<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pruebas de integración para los flujos de Citas y Agendamiento.
 *
 * Flujo del proceso:
 * 1. Verifica autenticación requerida para acceder al módulo de citas.
 * 2. Verifica listado de servicios de citas disponibles.
 * 3. Verifica acceso al endpoint de citas pendientes con permisos Spatie.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-20
 */
class AgendaCitaFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password_changed_at' => now(),
        ]);

        $permiso = Permission::firstOrCreate(['name' => 'agenda.index', 'guard_name' => 'web']);
        $this->user->givePermissionTo($permiso);
    }

    public function test_citas_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/citas');
        $response->assertStatus(401);
    }

    public function test_citas_lista_servicios_exitosamente(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/citas/servicios');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);
    }

    public function test_citas_pendientes_responde_correctamente(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/citas/pendientes');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data',
        ]);
    }
}
