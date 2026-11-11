<?php

namespace Tests\Unit;

use App\Services\Worklist\MllpClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Pruebas del cliente MLLP contra un servidor TCP real de prueba
 * (tests/Fixtures/fake_mllp_server.php) en otro proceso.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-11
 */
class MllpClientTest extends TestCase
{
    /** @var resource|null */
    private $proceso = null;

    private string $archivo = '';

    protected function tearDown(): void
    {
        if (is_resource($this->proceso)) {
            proc_terminate($this->proceso);
            proc_close($this->proceso);
        }
        if ($this->archivo !== '' && is_file($this->archivo)) {
            unlink($this->archivo);
        }
        parent::tearDown();
    }

    private function levantarServidor(string $codigo = 'AA'): int
    {
        $this->archivo = tempnam(sys_get_temp_dir(), 'mllp');
        $this->proceso = proc_open(
            [PHP_BINARY, __DIR__.'/../Fixtures/fake_mllp_server.php', $this->archivo, $codigo],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $tubos
        );
        $this->assertIsResource($this->proceso);

        $puerto = (int) trim((string) fgets($tubos[1]));
        $this->assertGreaterThan(0, $puerto, 'el servidor de prueba no arrancó');

        return $puerto;
    }

    public function test_envia_enmarcado_y_devuelve_el_ack_sin_marco(): void
    {
        $puerto = $this->levantarServidor();
        $mensaje = "MSH|^~\\&|LIDIAG|SEDE1|DCM4CHEE|DCM4CHEE|20261011||ORM^O01|LD1|P|2.5.1\rPID|1||123\r";

        $ack = (new MllpClient)->enviar('127.0.0.1', $puerto, $mensaje, 3, 5);

        $this->assertStringStartsWith('MSH|', $ack);
        $this->assertStringContainsString("\rMSA|AA|LD1", $ack);
        $this->assertStringNotContainsString("\x1c", $ack);

        proc_close($this->proceso);
        $this->proceso = null;
        $this->assertSame("\x0b".$mensaje."\x1c\x0d", file_get_contents($this->archivo));
    }

    public function test_pacs_que_no_contesta_respeta_el_plazo_y_lanza_excepcion(): void
    {
        $puerto = $this->levantarServidor('MUDO');
        $inicio = microtime(true);

        try {
            (new MllpClient)->enviar('127.0.0.1', $puerto, 'MSH|^~\\&|X', 2, 1);
            $this->fail('debía lanzar RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('no respondió', $e->getMessage());
        }

        $this->assertLessThan(3, microtime(true) - $inicio);
    }

    public function test_sin_conexion_lanza_runtime_exception(): void
    {
        // Puerto que se acaba de liberar: nadie escucha ahí.
        $libre = stream_socket_server('tcp://127.0.0.1:0');
        $puerto = (int) parse_url('tcp://'.stream_socket_get_name($libre, false), PHP_URL_PORT);
        fclose($libre);

        $this->expectException(RuntimeException::class);
        (new MllpClient)->enviar('127.0.0.1', $puerto, 'MSH|^~\\&|X', 1, 1);
    }
}
