<?php

namespace Tests\Unit;

use App\Services\Estudios\EstudioLockService;
use Tests\TestCase;

/**
 * Pruebas unitarias para EstudioLockService.
 *
 * Flujo del proceso:
 * 1. Verifica resolución determinista de claves con namespace (ep:id para lectura).
 * 2. Verifica resolución con namespace (iuid:... o pk:... para asignación).
 * 3. Verifica descarte de claves inválidas.
 * 4. Verifica cálculo de minutos disponibles por defecto.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
class EstudioLockServiceTest extends TestCase
{
    protected EstudioLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lockService = new EstudioLockService;
    }

    public function test_resolver_clave_para_lectura(): void
    {
        $reflection = new \ReflectionClass($this->lockService);
        $method = $reflection->getMethod("resolverClave");
        $method->setAccessible(true);

        $clave = $method->invoke($this->lockService, "lectura", ["estudio_producto_id" => 54321]);
        $this->assertSame("ep:54321", $clave);

        $claveInvalida = $method->invoke($this->lockService, "lectura", []);
        $this->assertNull($claveInvalida);
    }

    public function test_resolver_clave_para_asignacion_con_iuid(): void
    {
        $reflection = new \ReflectionClass($this->lockService);
        $method = $reflection->getMethod("resolverClave");
        $method->setAccessible(true);

        $clave = $method->invoke($this->lockService, "asignacion", ["study_iuid" => "1.2.840.113619.2.55"]);
        $this->assertSame("iuid:1.2.840.113619.2.55", $clave);
    }

    public function test_resolver_clave_para_asignacion_con_sede_y_pk(): void
    {
        $reflection = new \ReflectionClass($this->lockService);
        $method = $reflection->getMethod("resolverClave");
        $method->setAccessible(true);

        $clave = $method->invoke($this->lockService, "asignacion", [
            "sede_id" => 2,
            "study_pk" => 987,
        ]);
        $this->assertSame("pk:987", $clave);
    }

    public function test_resolver_clave_tipo_invalido_retorna_null(): void
    {
        $reflection = new \ReflectionClass($this->lockService);
        $method = $reflection->getMethod("resolverClave");
        $method->setAccessible(true);

        $clave = $method->invoke($this->lockService, "tipo_desconocido", ["id" => 1]);
        $this->assertNull($clave);
    }

    public function test_minutos_disponibles_retorna_default_cuando_no_hay_sede(): void
    {
        $reflection = new \ReflectionClass($this->lockService);
        $method = $reflection->getMethod("minutosDisponibles");
        $method->setAccessible(true);

        $minutos = $method->invoke($this->lockService, "asignacion", []);
        $this->assertSame(EstudioLockService::MINUTOS_DEFAULT, $minutos);
    }
}
