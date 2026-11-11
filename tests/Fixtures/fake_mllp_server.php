<?php

/**
 * Servidor MLLP de prueba: escucha en un puerto libre, imprime el puerto,
 * atiende una conexión, guarda el mensaje recibido en el archivo indicado y
 * responde un ACK con el código pedido (AA por defecto).
 *
 * Uso: php fake_mllp_server.php <archivo_salida> [codigo_ack]
 */
$salida = $argv[1];
$codigo = $argv[2] ?? 'AA';

$servidor = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
if ($servidor === false) {
    fwrite(STDERR, $errstr);
    exit(1);
}

echo parse_url('tcp://'.stream_socket_get_name($servidor, false), PHP_URL_PORT).PHP_EOL;
flush();

$conexion = stream_socket_accept($servidor, 15);
if ($conexion === false) {
    exit(2);
}

$recibido = '';
while (! str_contains($recibido, "\x1c\x0d") && ! feof($conexion)) {
    $recibido .= fread($conexion, 8192);
}
file_put_contents($salida, $recibido);

if ($codigo === 'MUDO') {
    // Simula un PACS que recibe y nunca contesta.
    sleep(4);
    exit(0);
}

fwrite($conexion, "\x0bMSH|^~\\&|DCM4CHEE|DCM4CHEE|LIDIAG|SEDE1|20261011||ACK^O01^ACK|1|P|2.5.1\rMSA|{$codigo}|LD1\r\x1c\x0d");
fclose($conexion);
fclose($servidor);
