<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Backup\BackupService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pruebas de integración para el Panel de Monitoreo de Backups y Sincronización.
 *
 * Flujo del proceso:
 * 1. Verifica autenticación requerida para acceder al estado de backups (401).
 * 2. Verifica autorización por permisos Spatie (seguridad.backups.index -> 403 sin permiso).
 * 3. Verifica respuesta estructurada con métricas de almacenamiento, sedes y backups centrales (200).
 * 4. Verifica autorización requerida para disparo manual de backup de base de datos (seguridad.backups.ejecutar).
 * 5. Verifica ejecución exitosa del endpoint de backup manual con mock de BackupService.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class BackupMonitorFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            "password_changed_at" => now(),
        ]);
    }

    public function test_estado_backups_requiere_autenticacion(): void
    {
        $response = $this->getJson("/api/seguridad-backups/estado");
        $response->assertStatus(401);
    }

    public function test_estado_backups_requiere_permiso_especifico(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/seguridad-backups/estado");

        $response->assertStatus(403);
    }

    public function test_estado_backups_responde_correctamente_con_permiso(): void
    {
        $permiso = Permission::firstOrCreate(["name" => "seguridad.backups.index", "guard_name" => "web"]);
        $this->user->givePermissionTo($permiso);

        $response = $this->actingAs($this->user)
            ->getJson("/api/seguridad-backups/estado");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "status",
            "data" => [
                "almacenamiento" => [
                    "unidad",
                    "directorio",
                    "espacio_total_humano",
                    "espacio_libre_humano",
                    "porcentaje_usado",
                    "estado_espacio",
                ],
                "centrales" => [
                    "ultimo_db",
                    "ultimo_storage",
                ],
                "resumen_sedes" => [
                    "total",
                    "al_dia",
                    "advertencia",
                    "critico",
                    "salud_porcentaje",
                ],
                "sedes",
                "logs",
                "fecha_consulta",
            ],
        ]);
    }

    public function test_ejecutar_backup_db_requiere_permiso(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/seguridad-backups/ejecutar-db");

        $response->assertStatus(403);
    }

    public function test_ejecutar_backup_db_ejecuta_correctamente_con_permiso(): void
    {
        $permiso = Permission::firstOrCreate(["name" => "seguridad.backups.ejecutar", "guard_name" => "web"]);
        $this->user->givePermissionTo($permiso);

        $mockBackup = Mockery::mock(BackupService::class);
        $mockBackup->shouldReceive("ejecutar")
            ->once()
            ->with(["only_db" => true])
            ->andReturn([
                "success" => true,
                "message" => "Backup generado exitosamente.",
                "data" => [
                    "archivo_db" => "backup_db_lidiag_test.zip",
                    "tamano_db_humano" => "2.5 MB",
                ],
            ]);

        $this->app->instance(BackupService::class, $mockBackup);

        $response = $this->actingAs($this->user)
            ->postJson("/api/seguridad-backups/ejecutar-db");

        $response->assertStatus(200);
        $response->assertJson([
            "success" => true,
            "message" => "Backup de base de datos generado exitosamente.",
        ]);
    }
}
