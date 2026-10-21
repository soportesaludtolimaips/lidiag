<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Interfaces\SAHIService;
use App\Services\Interfaces\SAHITranscibirService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pruebas de integración y resiliencia para la interoperabilidad HIS / SAHI (SQL Server).
 *
 * Flujo del proceso:
 * 1. Verifica autenticación requerida para acceder al endpoint de verificación (401).
 * 2. Verifica autorización por permisos Spatie (ecografias.index / admision / estudio.index -> 403 sin permiso).
 * 3. Verifica respuesta estructurada con métricas de conexión y latencia (200).
 * 4. Verifica método de diagnóstico SAHIService::verificarConexion().
 * 5. Verifica validación de documento obligatorio en SAHIService::listarAtenciones().
 * 6. Verifica validación de ID de atención obligatorio en SAHIService::listarProductosDiagnosticos().
 * 7. Verifica validación de médico y atención en SAHITranscibirService::enviarEstudio().
 * 8. Verifica ejecución segura de EcografiasSahiReconcileCommand vía Artisan.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class SahiInteroperabilidadTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password_changed_at' => now(),
        ]);
    }

    public function test_verificar_conexion_sahi_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/interface-sahi/test-conexion');
        $response->assertStatus(401);
    }

    public function test_verificar_conexion_sahi_requiere_permiso(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/interface-sahi/test-conexion');

        $response->assertStatus(403);
    }

    public function test_verificar_conexion_sahi_responde_con_permiso(): void
    {
        $permiso = Permission::firstOrCreate(['name' => 'estudio.index', 'guard_name' => 'web']);
        $this->user->givePermissionTo($permiso);

        $response = $this->actingAs($this->user)
            ->getJson('/api/interface-sahi/test-conexion');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status',
                'data' => [
                    'conectado',
                    'latencia_ms',
                    'host',
                    'database',
                ],
                'message',
            ]);

        $this->assertTrue($response->json('data.conectado'));
    }

    public function test_servicio_verificar_conexion_retorna_estructura_valida(): void
    {
        /** @var SAHIService $service */
        $service = app(SAHIService::class);
        $result = $service->verificarConexion();

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status']);
        $this->assertTrue($result['data']['conectado']);
        $this->assertArrayHasKey('latencia_ms', $result['data']);
        $this->assertGreaterThanOrEqual(0, $result['data']['latencia_ms']);
    }

    public function test_listar_atenciones_valida_documento_obligatorio(): void
    {
        /** @var SAHIService $service */
        $service = app(SAHIService::class);
        $result = $service->listarAtenciones(null);

        $this->assertFalse($result['success']);
        $this->assertEquals(422, $result['status']);
        $this->assertStringContainsString('obligatorio', $result['message']);
    }

    public function test_listar_productos_diagnosticos_valida_id_atencion_obligatorio(): void
    {
        /** @var SAHIService $service */
        $service = app(SAHIService::class);
        $result = $service->listarProductosDiagnosticos(null);

        $this->assertFalse($result['success']);
        $this->assertEquals(422, $result['status']);
        $this->assertStringContainsString('obligatorio', $result['message']);
    }

    public function test_enviar_estudio_valida_medico_y_atencion_obligatorios(): void
    {
        /** @var SAHITranscibirService $service */
        $service = app(SAHITranscibirService::class);

        // Sin médico
        $resultSinMedico = $service->enviarEstudio(12345, null, 'Reporte', 'CUPS123', '2026-10-21 10:00:00');
        $this->assertFalse($resultSinMedico['success']);
        $this->assertEquals(422, $resultSinMedico['status']);

        // Sin atención
        $resultSinAtencion = $service->enviarEstudio(null, $this->user->id, 'Reporte', 'CUPS123', '2026-10-21 10:00:00');
        $this->assertFalse($resultSinAtencion['success']);
        $this->assertEquals(422, $resultSinAtencion['status']);
    }

    public function test_reconcile_command_se_ejecuta_correctamente(): void
    {
        $this->artisan('ecografias:sahi-reconcile --minutes=10 --limit=10')
            ->assertSuccessful();
    }
}
