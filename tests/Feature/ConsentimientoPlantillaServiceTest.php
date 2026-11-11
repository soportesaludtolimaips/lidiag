<?php

namespace Tests\Feature;

use App\Services\Consentimientos\ConsentimientoPlantillaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pruebas de integración del servicio de plantillas de consentimientos contra
 * la base de datos de test aislada (`lidiag_test`). Usa DatabaseTransactions:
 * cada prueba se envuelve en una transacción y se revierte al terminar.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-09-02
 */
class ConsentimientoPlantillaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function crearModulo(): int
    {
        return DB::table('config_modulos')->insertGetId([
            'modulo' => 'Rx',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearProducto(): int
    {
        return DB::table('config_productos')->insertGetId([
            'cod_cups' => '870121',
            'nom_produc' => 'RX DE TORAX',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearUsuario(): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Tester',
            'email' => 'tester_'.uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_crear_plantilla_persiste_en_bd(): void
    {
        $moduloId = $this->crearModulo();
        $productoId = $this->crearProducto();
        $userId = $this->crearUsuario();

        $res = app(ConsentimientoPlantillaService::class)->create([
            'config_modulo_id' => $moduloId,
            'producto_id' => $productoId,
            'nombre' => 'C.I. de Prueba',
            'html' => '<p>Hola ${nombres}</p>',
        ], $userId);

        $this->assertTrue($res['success']);
        $this->assertDatabaseHas('consentimiento_plantillas', [
            'config_modulo_id' => $moduloId,
            'producto_id' => $productoId,
            'nombre' => 'C.I. de Prueba',
            'version' => 1,
            'vigente' => 1,
        ]);
    }

    public function test_segunda_version_desactiva_la_anterior(): void
    {
        $moduloId = $this->crearModulo();
        $productoId = $this->crearProducto();
        $userId = $this->crearUsuario();
        $svc = app(ConsentimientoPlantillaService::class);

        $svc->create([
            'config_modulo_id' => $moduloId, 'producto_id' => $productoId,
            'nombre' => 'C.I. v1', 'html' => '<p>v1</p>',
        ], $userId);

        $res2 = $svc->create([
            'config_modulo_id' => $moduloId, 'producto_id' => $productoId,
            'nombre' => 'C.I. v2', 'html' => '<p>v2</p>',
        ], $userId);

        $this->assertTrue($res2['success']);
        $this->assertSame(2, (int) $res2['data']->version);

        // Solo debe quedar UNA plantilla vigente para ese módulo+producto.
        $vigentes = DB::table('consentimiento_plantillas')
            ->where('config_modulo_id', $moduloId)
            ->where('producto_id', $productoId)
            ->where('vigente', 1)
            ->count();
        $this->assertSame(1, $vigentes);
    }

    public function test_listar_devuelve_envelope_exitoso(): void
    {
        $res = app(ConsentimientoPlantillaService::class)->list([
            'config_modulo_id' => 999999,
            'q' => '',
            'vigentes' => '1',
        ]);

        $this->assertTrue($res['success']);
        $this->assertSame(200, $res['status']);
    }
}
