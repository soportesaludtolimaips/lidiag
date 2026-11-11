<?php

namespace App\Services\Worklist;

use App\Models\Agenda\AgendaCita;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Envía las citas al PACS de la sede como HL7 ORM^O01 (MLLP) para que
 * aparezcan en la lista de trabajo (Modality Worklist) del equipo.
 *
 * El mismo mensaje sirve para dcm4chee 2.x y 5.x: ambos leen el Study
 * Instance UID del segmento ZDS, el procedimiento de OBR-4/OBR-44, la
 * modalidad de OBR-24 y la fecha programada de ORC-7.
 *
 * Nunca lanza excepciones: si el PACS no responde, la cita sigue su curso y
 * el fallo queda en el log.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-11
 */
class WorklistService
{
    public function __construct(
        private readonly MllpClient $cliente,
    ) {}

    /**
     * Sincroniza la lista de trabajo tras asignar o reprogramar una cita.
     *
     * Si la cita ya estaba enviada se actualiza (XO); si cambió de sede o el
     * producto dejó de ser de imagen, se retira la anterior (CA) antes.
     *
     * @param  AgendaCita|null  $anterior  copia de la cita antes del cambio si ya estaba asignada.
     *
     * @returns array ['enviado' => bool, 'motivo' => string]
     */
    public function asignar(AgendaCita $cita, ?AgendaCita $anterior = null): array
    {
        $anteriorEnviada = $anterior !== null && $this->aplica($anterior);
        $mismaSede = $anterior !== null && (int) $anterior->sede_id === (int) $cita->sede_id;

        if ($anteriorEnviada && (! $mismaSede || ! $this->aplica($cita))) {
            $this->cancelar($anterior);
            $anteriorEnviada = false;
        }

        return $this->programar($cita, $anteriorEnviada);
    }

    /**
     * Programa la cita en la lista de trabajo (NW) o actualiza una ya enviada (XO).
     *
     * @param  bool  $esCambio  true si la cita ya estaba en la lista (reprogramación).
     *
     * @returns array ['enviado' => bool, 'motivo' => string]
     */
    public function programar(AgendaCita $cita, bool $esCambio = false): array
    {
        return $this->procesar($cita, $esCambio ? 'XO' : 'NW');
    }

    /**
     * Indica si la cita va a la lista de trabajo: sede con worklist activo y
     * producto de imagen con paciente identificado.
     */
    public function aplica(AgendaCita $cita): bool
    {
        try {
            $cita->loadMissing(['paciente', 'producto', 'sede']);
            $sede = $cita->sede;

            return $sede && $sede->worklist_activo && ! empty($sede->ip_dcm)
                && $this->construirMensaje($cita, 'NW') !== null;
        } catch (Throwable $e) {
            Log::warning('Worklist: no se pudo evaluar la cita', ['cita_id' => $cita->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Cita de HOY del paciente en la sede que va a la lista de trabajo desde la agenda (se envió al asignarla), o null.
     *
     * La admisión directa (Pacientes ▸ Admisión, que escribe en la BD del PACS) la usa para no crear una segunda orden
     * del mismo paciente: el tecnólogo lo vería dos veces en la lista del equipo.
     */
    public function citaDeHoyEnLista(int $pacienteId, int $sedeId): ?AgendaCita
    {
        $citas = AgendaCita::query()
            ->where('paciente_id', $pacienteId)
            ->where('sede_id', $sedeId)
            ->whereIn('estado', ['asignada', 'confirmada'])
            ->whereDate('fecha_hora_inicio', Carbon::today())
            ->get();

        return $citas->first(fn (AgendaCita $cita) => $this->aplica($cita));
    }

    /**
     * Retira la cita de la lista de trabajo (CA).
     *
     * @returns array ['enviado' => bool, 'motivo' => string]
     */
    public function cancelar(AgendaCita $cita): array
    {
        return $this->procesar($cita, 'CA');
    }

    /**
     * Arma el mensaje ORM^O01 en UTF-8 o null si la cita no aplica
     * (sede sin worklist, producto sin modalidad o paciente sin documento).
     *
     * @param  string  $control  NW, XO o CA (ORC-1).
     */
    public function construirMensaje(AgendaCita $cita, string $control): ?string
    {
        $cita->loadMissing(['paciente', 'producto', 'sede']);
        $sede = $cita->sede;
        $paciente = $cita->paciente;
        $producto = $cita->producto;

        if (! $sede || ! $paciente || ! $producto || trim((string) $paciente->num_docu) === '') {
            return null;
        }

        $modalidad = $this->modalidadPorCups((string) $producto->cod_cups);
        if ($modalidad === null) {
            return null;
        }

        $acceso = $this->numeroAcceso($cita);
        $inicio = $cita->fecha_hora_inicio ? $cita->fecha_hora_inicio->format('YmdHis') : '';
        $receptor = $this->limpiar($sede->worklist_aet ?: 'DCM4CHEE', 16);
        $cups = $this->limpiar($producto->cod_cups, 16);
        $nombreProducto = $this->limpiar($producto->nom_produc, 64);
        $procedimiento = "{$cups}^{$nombreProducto}^CUPS";

        $msh = [
            'MSH', '^~\\&',
            $this->limpiar(config('worklist.aplicacion', 'LIDIAG'), 20),
            'SEDE'.$sede->id,
            $receptor,
            $receptor,
            now()->format('YmdHis'),
            '',
            'ORM^O01',
            'LD'.now()->format('ymdHis').random_int(1000, 9999),
            'P',
            '2.5.1',
        ];
        $msh[17] = '8859/1';

        $pid = [
            'PID', '1', '',
            $this->limpiar($paciente->num_docu, 64),
            '',
            $this->nombrePaciente($paciente),
            '',
            $this->fecha($paciente->fec_naci),
            $this->sexo($paciente->sexo),
        ];

        $orc = [
            'ORC',
            $control,
            $acceso,
            $acceso,
            '',
            $control === 'CA' ? 'CA' : 'SC',
            '',
            '^^^'.$inicio,
        ];
        $orc[18] = $this->limpiar($sede->worklist_estacion_aet, 16);

        $obr = array_fill(0, 45, '');
        $obr[0] = 'OBR';
        $obr[1] = '1';
        $obr[2] = $acceso;
        $obr[3] = $acceso;
        $obr[4] = "{$procedimiento}^{$procedimiento}";
        $obr[18] = $acceso;
        $obr[19] = $acceso;
        $obr[20] = $acceso;
        $obr[24] = $modalidad;
        $obr[27] = '^^^'.$inicio;
        $obr[44] = $procedimiento;

        $segmentos = [
            $this->segmento($msh),
            $this->segmento($pid),
            'PV1|1|O',
            $this->segmento($orc),
            $this->segmento($obr),
            'ZDS|'.$this->studyUid($cita).'^100^Application^DICOM',
        ];

        return implode("\r", $segmentos)."\r";
    }

    /**
     * Modalidad DICOM según el prefijo CUPS más largo configurado, o null.
     */
    public function modalidadPorCups(string $cups): ?string
    {
        $cups = trim($cups);
        $mejor = null;
        $largo = 0;
        foreach (config('worklist.modalidades', []) as $prefijo => $modalidad) {
            $prefijo = (string) $prefijo;
            if (strlen($prefijo) > $largo && str_starts_with($cups, $prefijo)) {
                $mejor = $modalidad;
                $largo = strlen($prefijo);
            }
        }

        return $mejor;
    }

    /**
     * Número de acceso de la cita; también es el ID del procedimiento y del paso.
     */
    public function numeroAcceso(AgendaCita $cita): string
    {
        return substr(config('worklist.prefijo_acceso', 'C').$cita->id, 0, 16);
    }

    /**
     * Study Instance UID estable por cita (2.25.<UUID en decimal>), para que la
     * reprogramación y la cancelación lleguen al mismo ítem del PACS.
     */
    public function studyUid(AgendaCita $cita): string
    {
        $creada = $cita->created_at ? $cita->created_at->format('YmdHis') : '';
        $bytes = md5("lidiag|cita|{$cita->id}|{$creada}", true);
        // UUID versión 3 (basado en MD5), variante RFC 4122.
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x30);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        return '2.25.'.$this->hexADecimal(bin2hex($bytes));
    }

    /**
     * Envía el mensaje a la sede de la cita y registra el resultado.
     */
    private function procesar(AgendaCita $cita, string $control, bool $esReintento = false): array
    {
        try {
            if (! $this->aplica($cita)) {
                return ['enviado' => false, 'motivo' => 'la cita no aplica para worklist'];
            }

            $sede = $cita->sede;
            $host = trim((string) $sede->ip_dcm);
            if (! preg_match('/^[A-Za-z0-9.\-]+$/', $host)) {
                throw new RuntimeException('IP del PACS inválida en la sede');
            }

            $ack = $this->cliente->enviar(
                $host,
                (int) ($sede->worklist_puerto ?: 2575),
                $this->aLatin1($this->construirMensaje($cita, $control)),
                (float) config('worklist.timeout_conexion', 3),
                (float) config('worklist.timeout_respuesta', 5),
            );

            $codigo = $this->codigoAck($ack);
            $contexto = ['cita_id' => $cita->id, 'sede_id' => $sede->id, 'control' => $control, 'ack' => $codigo];

            if (in_array($codigo, ['AA', 'CA'], true)) {
                Log::info('Worklist: cita enviada al PACS', $contexto);

                return ['enviado' => true, 'motivo' => $codigo];
            }

            // Solo el código: el texto del error del PACS puede repetir datos del paciente.
            Log::warning('Worklist: el PACS rechazó la cita', $contexto);

            // NW sobre un ítem que ya existe o XO sobre uno que no existe: un intento con el otro.
            if (! $esReintento && in_array($control, ['NW', 'XO'], true) && in_array($codigo, ['AE', 'AR'], true)) {
                return $this->procesar($cita, $control === 'NW' ? 'XO' : 'NW', true);
            }

            return ['enviado' => false, 'motivo' => 'rechazada por el PACS ('.$codigo.')'];
        } catch (Throwable $e) {
            Log::warning('Worklist: no se pudo enviar la cita', [
                'cita_id' => $cita->id,
                'control' => $control,
                'error' => $e->getMessage(),
            ]);

            return ['enviado' => false, 'motivo' => $e->getMessage()];
        }
    }

    /**
     * MSA-1 del ACK (AA, AE, AR, CA, CE, CR) o cadena vacía.
     */
    public function codigoAck(string $ack): string
    {
        foreach (preg_split('/\r\n|\r|\n/', $ack) as $segmento) {
            if (str_starts_with($segmento, 'MSA|')) {
                return trim(explode('|', $segmento)[1] ?? '');
            }
        }

        return '';
    }

    private function nombrePaciente(object $paciente): string
    {
        $apellidos = trim(($paciente->pri_ape ?? '').' '.($paciente->seg_ape ?? ''));
        $nombres = trim(($paciente->nombres ?? '').' '.($paciente->seg_nom ?? ''));

        return $this->limpiar(mb_strtoupper($apellidos), 64).'^'.$this->limpiar(mb_strtoupper($nombres), 64);
    }

    private function fecha(mixed $valor): string
    {
        if (empty($valor)) {
            return '';
        }

        try {
            return Carbon::parse($valor)->format('Ymd');
        } catch (Throwable) {
            return '';
        }
    }

    private function sexo(?string $sexo): string
    {
        return match (strtoupper(trim((string) $sexo))) {
            'M', 'H' => 'M',
            'F' => 'F',
            default => 'O',
        };
    }

    /**
     * Deja solo texto ISO-8859-1 imprimible, escapa los delimitadores HL7 y recorta.
     * Lo que no existe en ISO-8859-1 queda como '?' sin transliterar: así
     * '｜' o '＾' no se convierten en un delimitador real al codificar.
     */
    private function limpiar(?string $valor, int $maximo): string
    {
        $valor = preg_replace('/[\r\n\t]+/', ' ', (string) $valor);
        $valor = mb_convert_encoding(mb_convert_encoding($valor, 'ISO-8859-1', 'UTF-8'), 'UTF-8', 'ISO-8859-1');
        $valor = preg_replace('/[\x{00}-\x{1F}\x{7F}-\x{9F}]/u', '', $valor);
        $valor = mb_substr(trim($valor), 0, $maximo);

        return strtr($valor, [
            '\\' => '\\E\\',
            '|' => '\\F\\',
            '^' => '\\S\\',
            '&' => '\\T\\',
            '~' => '\\R\\',
        ]);
    }

    private function segmento(array $campos): string
    {
        $ultimo = max(array_keys($campos));
        $lista = [];
        for ($i = 0; $i <= $ultimo; $i++) {
            $lista[] = $campos[$i] ?? '';
        }

        return rtrim(implode('|', $lista), '|');
    }

    /**
     * Pasa el mensaje a ISO-8859-1 (declarado en MSH-18, lo entienden 2.x y 5.x)
     * y verifica que ningún dato traiga caracteres que rompan la trama MLLP.
     */
    private function aLatin1(string $mensaje): string
    {
        $bytes = mb_convert_encoding($mensaje, 'ISO-8859-1', 'UTF-8');
        if (preg_match('/[\x00-\x0C\x0E-\x1F]/', $bytes) || substr_count($bytes, "\r") !== 6) {
            throw new RuntimeException('Mensaje HL7 inválido: un dato trae caracteres de control');
        }

        return $bytes;
    }

    /**
     * Convierte un hexadecimal de cualquier largo a decimal sin bcmath.
     */
    private function hexADecimal(string $hex): string
    {
        $digitos = [0];
        foreach (str_split(strtolower($hex)) as $caracter) {
            $acarreo = hexdec($caracter);
            for ($i = 0, $n = count($digitos); $i < $n; $i++) {
                $valor = $digitos[$i] * 16 + $acarreo;
                $digitos[$i] = $valor % 10;
                $acarreo = intdiv($valor, 10);
            }
            while ($acarreo > 0) {
                $digitos[] = $acarreo % 10;
                $acarreo = intdiv($acarreo, 10);
            }
        }

        $decimal = ltrim(implode('', array_reverse($digitos)), '0');

        return $decimal === '' ? '0' : $decimal;
    }
}
