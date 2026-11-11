<?php

namespace App\Services\Entrega;

/**
 * Normaliza y enmascara los datos de contacto del paciente.
 *
 * Solo se envía por WhatsApp a un celular colombiano válido; un teléfono fijo o un número
 * mal digitado se descarta. En los registros y pantallas solo se guarda el destino enmascarado.
 */
final class Contacto
{
    /**
     * Primer candidato que sea un celular colombiano, en formato internacional (57 + 10 dígitos),
     * o null si ninguno lo es.
     */
    public static function celular(?string ...$candidatos): ?string
    {
        foreach ($candidatos as $candidato) {
            $digitos = preg_replace('/\D+/', '', (string) $candidato) ?? '';

            if (preg_match('/^(?:0057|57)?(3\d{9})$/', $digitos, $coincidencia)) {
                return '57'.$coincidencia[1];
            }
        }

        return null;
    }

    /** Correo válido y en minúsculas, o null. */
    public static function correo(?string $correo): ?string
    {
        $correo = strtolower(trim((string) $correo));

        return filter_var($correo, FILTER_VALIDATE_EMAIL) ? $correo : null;
    }

    /** +57 3** *** 4567 */
    public static function enmascararCelular(string $internacional): string
    {
        return '+57 '.substr($internacional, 2, 1).'** *** '.substr($internacional, -4);
    }

    /** j***@dominio.com */
    public static function enmascararCorreo(string $correo): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $correo, 2), 2, '');

        return mb_substr($usuario, 0, 1).'***@'.$dominio;
    }

    /**
     * Primer nombre cuando el nombre viene en formato DICOM (APELLIDO^NOMBRE^...), o null.
     * Con otros formatos no se adivina: es mejor un saludo sin nombre que uno equivocado.
     */
    public static function primerNombre(?string $nombrePaciente): ?string
    {
        if ($nombrePaciente === null || ! str_contains($nombrePaciente, '^')) {
            return null;
        }

        $nombre = trim(explode(' ', trim(explode('^', $nombrePaciente)[1] ?? ''))[0]);

        return $nombre === '' ? null : mb_convert_case(mb_strtolower($nombre), MB_CASE_TITLE);
    }
}
