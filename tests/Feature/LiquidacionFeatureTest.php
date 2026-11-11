<?php

namespace Tests\Feature;

use App\Models\Configuracion\ConfigSede;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Pruebas de integración para el módulo de Liquidación y Productividad Médica.
 *
 * Flujo del proceso:
 * 1. Verifica autenticación requerida para acceder a los endpoints de liquidación (401).
 * 2. Verifica autorización por permisos Spatie (liquidacion.index y liquidacion.exportar -> 403 sin permiso).
 * 3. Verifica respuesta estructurada con líneas, exclusiones enriquecidas y totales por médico (200).
 * 4. Verifica exportación a Excel de la liquidación del periodo (200).
 * 5. Verifica consulta y exportación a Excel de la productividad médica por corte mensual (200).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class LiquidacionFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected ConfigSede $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            "password_changed_at" => now(),
        ]);

        $this->sede = ConfigSede::firstOrCreate(
            ["id" => 1],
            ["cod_sede" => "IBAG", "nom_sede" => "SEDE PRINCIPAL IBAGUE", "estado" => 1]
        );
    }

    public function test_liquidacion_requiere_autenticacion(): void
    {
        $response = $this->getJson("/api/liquidacion?sede_id=1&fecha_inicio=2026-10-01&fecha_fin=2026-10-21");
        $response->assertStatus(401);
    }

    public function test_liquidacion_requiere_permiso_especifico(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/liquidacion?sede_id=1&fecha_inicio=2026-10-01&fecha_fin=2026-10-21");

        $response->assertStatus(403);
    }

    public function test_calcular_liquidacion_responde_correctamente_con_permiso(): void
    {
        $permiso = Permission::firstOrCreate(["name" => "liquidacion.index", "guard_name" => "web"]);
        $this->user->givePermissionTo($permiso);

        $response = $this->actingAs($this->user)
            ->getJson("/api/liquidacion?sede_id={$this->sede->id}&fecha_inicio=2026-10-01&fecha_fin=2026-10-21");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "status",
            "data" => [
                "lineas",
                "exclusiones" => [
                    "*" => [
                        "categoria",
                        "conteo",
                        "detalle",
                    ],
                ],
                "totales" => [
                    "por_medico",
                    "gran_total",
                ],
            ],
        ]);
    }

    public function test_exportar_liquidacion_genera_archivo_excel(): void
    {
        $permiso = Permission::firstOrCreate(["name" => "liquidacion.exportar", "guard_name" => "web"]);
        $this->user->givePermissionTo($permiso);

        $response = $this->actingAs($this->user)
            ->get("/api/liquidacion/exportar?sede_id={$this->sede->id}&fecha_inicio=2026-10-01&fecha_fin=2026-10-21");

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get("content-disposition"), "liquidacion.xlsx")
        );
    }

    public function test_productividad_responde_correctamente(): void
    {
        $permiso = Permission::firstOrCreate(["name" => "liquidacion.index", "guard_name" => "web"]);
        $this->user->givePermissionTo($permiso);

        $response = $this->actingAs($this->user)
            ->getJson("/api/liquidacion/productividad?sede_id={$this->sede->id}&periodo=2026-10");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            "status",
            "data" => [
                "periodo",
                "medicos",
                "gran_total",
            ],
        ]);
    }

    public function test_exportar_productividad_genera_archivo_excel(): void
    {
        $permiso = Permission::firstOrCreate(["name" => "liquidacion.exportar", "guard_name" => "web"]);
        $this->user->givePermissionTo($permiso);

        $response = $this->actingAs($this->user)
            ->get("/api/liquidacion/productividad/exportar?sede_id={$this->sede->id}&periodo=2026-10");

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get("content-disposition"), "productividad_2026-10_sede_{$this->sede->id}.xlsx")
        );
    }
}
