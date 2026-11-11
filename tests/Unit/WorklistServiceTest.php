<?php

namespace Tests\Unit;

use App\Models\Agenda\AgendaCita;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\General\Paciente;
use App\Services\Worklist\MllpClient;
use App\Services\Worklist\WorklistService;
use Carbon\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * Pruebas del envío de citas a la lista de trabajo del PACS (HL7 ORM^O01).
 * No tocan la base de datos ni la red: modelos en memoria y un cliente MLLP
 * falso que registra los mensajes.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-11
 */
class WorklistServiceTest extends TestCase
{
    /** @var array<int, array{host: string, puerto: int, mensaje: string}> */
    private array $enviados = [];

    private string $ack = "MSH|^~\\&|DCM4CHEE|DCM4CHEE|LIDIAG|SEDE1|20261011||ACK^O01^ACK|1|P|2.5.1\rMSA|AA|LD1";

    private ?RuntimeException $falla = null;

    private function servicio(): WorklistService
    {
        $prueba = $this;
        $cliente = new class($prueba) extends MllpClient
        {
            public function __construct(private readonly WorklistServiceTest $prueba) {}

            public function enviar(string $host, int $puerto, string $mensaje, float $timeoutConexion, float $timeoutRespuesta): string
            {
                return $this->prueba->registrarEnvio($host, $puerto, $mensaje);
            }
        };

        return new WorklistService($cliente);
    }

    public function registrarEnvio(string $host, int $puerto, string $mensaje): string
    {
        if ($this->falla) {
            throw $this->falla;
        }
        $this->enviados[] = ['host' => $host, 'puerto' => $puerto, 'mensaje' => $mensaje];

        return $this->ack;
    }

    private function cita(array $paciente = [], array $producto = [], array $sede = [], int $id = 77): AgendaCita
    {
        $cita = new AgendaCita;
        $cita->id = $id;
        $cita->sede_id = $sede['id'] ?? 3;
        $cita->created_at = Carbon::parse('2026-10-01 08:00:00');
        $cita->fecha_hora_inicio = Carbon::parse('2026-10-12 09:30:00');

        $cita->setRelation('paciente', new Paciente(array_merge([
            'num_docu' => '1110222333',
            'nombres' => 'María',
            'seg_nom' => 'José',
            'pri_ape' => 'Peña',
            'seg_ape' => 'Gómez',
            'sexo' => 'F',
            'fec_naci' => '1985-03-15',
        ], $paciente)));

        $cita->setRelation('producto', new ConfigProducto(array_merge([
            'cod_cups' => '871121',
            'nom_produc' => 'RADIOGRAFIA DE TORAX (PA O AP Y LATERAL)',
        ], $producto)));

        $modeloSede = new ConfigSede(array_merge([
            'nom_sede' => 'Espinal',
            'ip_dcm' => '10.0.0.5',
            'worklist_activo' => true,
            'worklist_puerto' => 2575,
            'worklist_aet' => 'DCM4CHEE',
            'worklist_estacion_aet' => 'AGFA_NX',
        ], $sede));
        $modeloSede->id = $sede['id'] ?? 3;
        $cita->setRelation('sede', $modeloSede);

        return $cita;
    }

    private function segmento(string $mensaje, string $nombre): array
    {
        foreach (explode("\r", $mensaje) as $linea) {
            if (str_starts_with($linea, $nombre.'|')) {
                return explode('|', $linea);
            }
        }
        $this->fail("No está el segmento {$nombre}");
    }

    public function test_arma_orm_con_los_campos_que_leen_dcm4chee_2x_y_5x(): void
    {
        $mensaje = $this->servicio()->construirMensaje($this->cita(), 'NW');

        $msh = $this->segmento($mensaje, 'MSH');
        $this->assertSame('ORM^O01', $msh[8]);
        $this->assertSame('DCM4CHEE', $msh[4]);
        $this->assertSame('2.5.1', $msh[11]);
        $this->assertSame('8859/1', $msh[17], 'MSH-18 es el índice 17');

        $pid = $this->segmento($mensaje, 'PID');
        $this->assertSame('1110222333', $pid[3]);
        $this->assertSame('PEÑA GÓMEZ^MARÍA JOSÉ', $pid[5]);
        $this->assertSame('19850315', $pid[7]);
        $this->assertSame('F', $pid[8]);

        $orc = $this->segmento($mensaje, 'ORC');
        $this->assertSame('NW', $orc[1]);
        $this->assertSame('SC', $orc[5]);
        $this->assertSame('^^^20261012093000', $orc[7]);
        $this->assertSame('AGFA_NX', $orc[18]);

        $obr = $this->segmento($mensaje, 'OBR');
        $procedimiento = '871121^RADIOGRAFIA DE TORAX (PA O AP Y LATERAL)^CUPS';
        $this->assertSame($procedimiento.'^'.$procedimiento, $obr[4]);
        $this->assertSame('C77', $obr[18]);
        $this->assertSame('C77', $obr[19]);
        $this->assertSame('C77', $obr[20]);
        $this->assertSame('DX', $obr[24]);
        $this->assertSame('^^^20261012093000', $obr[27]);
        $this->assertSame($procedimiento, $obr[44]);

        $zds = $this->segmento($mensaje, 'ZDS');
        $this->assertMatchesRegularExpression('/^2\.25\.[1-9]\d*\^100\^Application\^DICOM$/', $zds[1]);
    }

    public function test_cancelacion_usa_ca_y_el_mismo_uid(): void
    {
        $servicio = $this->servicio();
        $nueva = $servicio->construirMensaje($this->cita(), 'NW');
        $cancelada = $servicio->construirMensaje($this->cita(), 'CA');

        $this->assertSame('CA', $this->segmento($cancelada, 'ORC')[1]);
        $this->assertSame('CA', $this->segmento($cancelada, 'ORC')[5]);
        $this->assertSame($this->segmento($nueva, 'ZDS'), $this->segmento($cancelada, 'ZDS'));
    }

    public function test_escapa_delimitadores_hl7_en_los_datos(): void
    {
        $mensaje = $this->servicio()->construirMensaje(
            $this->cita(['pri_ape' => 'O|Neil', 'seg_ape' => null, 'nombres' => "Ana^Luz\r\nX", 'seg_nom' => null]),
            'NW'
        );

        $this->assertSame('O\\F\\NEIL^ANA\\S\\LUZ X', $this->segmento($mensaje, 'PID')[5]);
        $this->assertCount(6, explode("\r", trim($mensaje)), 'un salto de línea en los datos no crea segmentos');
    }

    public function test_caracteres_de_control_y_delimitadores_de_ancho_completo_no_rompen_la_trama(): void
    {
        $cita = $this->cita([
            'pri_ape' => "Smith｜X＾Y～Z＆",
            'seg_ape' => null,
            'nombres' => "Ana\x1c\x0bMSH|^~\\&|FALSO",
            'seg_nom' => null,
            'num_docu' => "123\x1c456",
        ]);

        $resultado = $this->servicio()->programar($cita);

        $this->assertTrue($resultado['enviado']);
        $bytes = $this->enviados[0]['mensaje'];
        $this->assertDoesNotMatchRegularExpression('/[\x00-\x0C\x0E-\x1F]/', $bytes);
        $this->assertSame(6, substr_count($bytes, "\r"));

        $pid = explode('|', explode("\r", $bytes)[1]);
        $this->assertSame('123456', $pid[3]);
        $this->assertSame('SMITH?X?Y?Z?^ANAMSH\\F\\\\S\\\\R\\\\E\\\\T\\\\F\\FALSO', $pid[5]);
    }

    public function test_si_el_pacs_rechaza_xo_reintenta_una_vez_como_nw(): void
    {
        $respuestas = ["MSA|AE|LD1", "MSA|AA|LD2"];
        $prueba = $this;
        $cliente = new class($respuestas, $prueba) extends MllpClient
        {
            public function __construct(private array $respuestas, private readonly WorklistServiceTest $prueba) {}

            public function enviar(string $host, int $puerto, string $mensaje, float $timeoutConexion, float $timeoutRespuesta): string
            {
                $this->prueba->registrarEnvio($host, $puerto, $mensaje);

                return array_shift($this->respuestas) ?? 'MSA|AE|LD9';
            }
        };

        $resultado = (new WorklistService($cliente))->programar($this->cita(), true);

        $this->assertTrue($resultado['enviado']);
        $this->assertCount(2, $this->enviados);
        $this->assertStringContainsString("\rORC|XO|", $this->enviados[0]['mensaje']);
        $this->assertStringContainsString("\rORC|NW|", $this->enviados[1]['mensaje']);
    }

    public function test_rechazo_definitivo_no_reintenta_mas_de_una_vez(): void
    {
        $this->ack = 'MSA|AR|LD1';

        $resultado = $this->servicio()->programar($this->cita());

        $this->assertFalse($resultado['enviado']);
        $this->assertCount(2, $this->enviados, 'NW y un solo reintento XO');
    }

    public function test_ip_del_pacs_invalida_no_se_usa(): void
    {
        $resultado = $this->servicio()->programar($this->cita([], [], ['ip_dcm' => '10.0.0.5:80/x']));

        $this->assertFalse($resultado['enviado']);
        $this->assertCount(0, $this->enviados);
    }

    public function test_modalidad_por_prefijo_cups_mas_largo(): void
    {
        $servicio = $this->servicio();

        $this->assertSame('DX', $servicio->modalidadPorCups('871121'));
        $this->assertSame('MG', $servicio->modalidadPorCups('876802'));
        $this->assertSame('CT', $servicio->modalidadPorCups('879111'));
        $this->assertSame('US', $servicio->modalidadPorCups('881201'));
        $this->assertSame('MR', $servicio->modalidadPorCups('883101'));
        $this->assertNull($servicio->modalidadPorCups('890201'));
        $this->assertNull($servicio->modalidadPorCups(''));
    }

    public function test_productos_sin_modalidad_o_paciente_sin_documento_no_aplican(): void
    {
        $servicio = $this->servicio();

        $this->assertNull($servicio->construirMensaje($this->cita([], ['cod_cups' => '890201']), 'NW'));
        $this->assertNull($servicio->construirMensaje($this->cita(['num_docu' => ' ']), 'NW'));
    }

    public function test_study_uid_es_estable_valido_y_distinto_por_cita(): void
    {
        $servicio = $this->servicio();
        $uid = $servicio->studyUid($this->cita());

        $this->assertSame($uid, $servicio->studyUid($this->cita()));
        $this->assertNotSame($uid, $servicio->studyUid($this->cita([], [], [], 78)));
        $this->assertMatchesRegularExpression('/^2\.25\.[1-9]\d{0,38}$/', $uid);
        $this->assertLessThanOrEqual(64, strlen($uid));
    }

    public function test_sexo_y_fecha_desconocidos_quedan_neutros(): void
    {
        $pid = $this->segmento(
            $this->servicio()->construirMensaje($this->cita(['sexo' => null, 'fec_naci' => 'no-es-fecha']), 'NW'),
            'PID'
        );

        $this->assertSame('O', $pid[8]);
        $this->assertSame('', $pid[7]);
    }

    public function test_programar_envia_en_latin1_a_la_ip_y_puerto_de_la_sede(): void
    {
        $resultado = $this->servicio()->programar($this->cita());

        $this->assertTrue($resultado['enviado']);
        $this->assertCount(1, $this->enviados);
        $this->assertSame('10.0.0.5', $this->enviados[0]['host']);
        $this->assertSame(2575, $this->enviados[0]['puerto']);
        $this->assertStringContainsString("PE\xD1A G\xD3MEZ^MAR\xCDA JOS\xC9", $this->enviados[0]['mensaje']);
    }

    public function test_no_envia_si_la_sede_no_tiene_worklist(): void
    {
        $resultado = $this->servicio()->programar($this->cita([], [], ['worklist_activo' => false]));

        $this->assertFalse($resultado['enviado']);
        $this->assertCount(0, $this->enviados);
    }

    public function test_rechazo_del_pacs_o_falla_de_red_no_lanzan_excepcion(): void
    {
        $this->ack = "MSH|^~\\&|DCM4CHEE|DCM4CHEE|LIDIAG|SEDE1|20261011||ACK^O01^ACK|1|P|2.5.1\rMSA|AE|LD1|Unknown receiving application";
        $this->assertFalse($this->servicio()->programar($this->cita())['enviado']);

        $this->falla = new RuntimeException('Sin conexión con 10.0.0.5:2575');
        $resultado = $this->servicio()->cancelar($this->cita());
        $this->assertFalse($resultado['enviado']);
        $this->assertStringContainsString('Sin conexión', $resultado['motivo']);
    }

    public function test_reprogramar_en_la_misma_sede_actualiza_con_xo(): void
    {
        $anterior = $this->cita();
        $nueva = $this->cita();
        $nueva->fecha_hora_inicio = Carbon::parse('2026-10-13 07:00:00');

        $this->servicio()->asignar($nueva, $anterior);

        $this->assertCount(1, $this->enviados);
        $this->assertStringContainsString("\rORC|XO|", $this->enviados[0]['mensaje']);
    }

    public function test_reprogramar_a_otra_sede_cancela_en_la_anterior_y_crea_en_la_nueva(): void
    {
        $anterior = $this->cita();
        $nueva = $this->cita([], [], ['id' => 4, 'ip_dcm' => '10.0.0.9']);

        $this->servicio()->asignar($nueva, $anterior);

        $this->assertCount(2, $this->enviados);
        $this->assertSame('10.0.0.5', $this->enviados[0]['host']);
        $this->assertStringContainsString("\rORC|CA|", $this->enviados[0]['mensaje']);
        $this->assertSame('10.0.0.9', $this->enviados[1]['host']);
        $this->assertStringContainsString("\rORC|NW|", $this->enviados[1]['mensaje']);
    }

    public function test_primera_asignacion_usa_nw(): void
    {
        $this->servicio()->asignar($this->cita());

        $this->assertStringContainsString("\rORC|NW|", $this->enviados[0]['mensaje']);
    }

    public function test_lee_el_codigo_del_ack(): void
    {
        $servicio = $this->servicio();

        $this->assertSame('AA', $servicio->codigoAck("MSH|^~\\&|A\rMSA|AA|1"));
        $this->assertSame('AR', $servicio->codigoAck("MSH|^~\\&|A\nMSA|AR|1|x"));
        $this->assertSame('', $servicio->codigoAck('basura'));
    }
}
