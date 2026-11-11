<?php

namespace App\Services\Worklist;

use RuntimeException;

/**
 * Cliente MLLP mínimo: abre la conexión TCP, envía un mensaje HL7 enmarcado
 * (0x0B ... 0x1C 0x0D) y devuelve el ACK sin el marco.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-11
 */
class MllpClient
{
    private const INICIO = "\x0b";

    private const FIN = "\x1c\x0d";

    private const MAXIMO_RESPUESTA = 65536;

    /**
     * Envía un mensaje HL7 y espera el ACK.
     *
     * @param  string  $mensaje  mensaje HL7 ya codificado, segmentos separados por \r.
     *
     * @returns string ACK recibido, sin el marco MLLP.
     *
     * @throws RuntimeException si no hay conexión, no se puede escribir o no llega respuesta.
     */
    public function enviar(string $host, int $puerto, string $mensaje, float $timeoutConexion, float $timeoutRespuesta): string
    {
        $socket = @stream_socket_client(
            "tcp://{$host}:{$puerto}",
            $errno,
            $errstr,
            $timeoutConexion
        );

        if ($socket === false) {
            throw new RuntimeException("Sin conexión con {$host}:{$puerto} ({$errstr})");
        }

        try {
            $limite = microtime(true) + $timeoutRespuesta;
            $this->ajustarEspera($socket, $timeoutRespuesta);

            $trama = self::INICIO.$mensaje.self::FIN;
            if (@fwrite($socket, $trama) !== strlen($trama)) {
                throw new RuntimeException("No se pudo enviar el mensaje a {$host}:{$puerto}");
            }

            return $this->leerRespuesta($socket, $host, $puerto, $limite);
        } finally {
            fclose($socket);
        }
    }

    /**
     * Lee hasta el fin de trama MLLP, el cierre de la conexión, el plazo total
     * o el tamaño máximo (un ACK ocupa unos cientos de bytes).
     *
     * @param  resource  $socket
     */
    private function leerRespuesta($socket, string $host, int $puerto, float $limite): string
    {
        $respuesta = '';
        while (! feof($socket) && strlen($respuesta) < self::MAXIMO_RESPUESTA) {
            $restante = $limite - microtime(true);
            if ($restante <= 0) {
                break;
            }
            $this->ajustarEspera($socket, $restante);

            $bloque = fread($socket, 8192);
            if ($bloque === false || $bloque === '') {
                if (stream_get_meta_data($socket)['timed_out']) {
                    break;
                }

                continue;
            }
            $respuesta .= $bloque;
            if (str_contains($respuesta, "\x1c")) {
                break;
            }
        }

        $respuesta = trim($respuesta, "\x0b\x1c\r\n");
        if ($respuesta === '') {
            throw new RuntimeException("{$host}:{$puerto} no respondió el ACK");
        }

        return $respuesta;
    }

    /**
     * @param  resource  $socket
     */
    private function ajustarEspera($socket, float $segundos): void
    {
        $enteros = (int) floor($segundos);
        stream_set_timeout($socket, $enteros, (int) (($segundos - $enteros) * 1_000_000));
    }
}
