<?php

namespace App\Helpers;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    /**
     * Crea la regla de validación de contraseña segura.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function nueva(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
    }

    /**
     * Mensajes de validación en español para el campo de contraseña.
     *
     * @param  string  $campo  Nombre del campo al que se asocian los mensajes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public static function mensajes(string $campo = 'password'): array
    {
        return [
            "{$campo}.min" => 'La contraseña debe tener al menos 8 caracteres.',
            "{$campo}.mixed" => 'La contraseña debe contener al menos una letra mayúscula y una minúscula.',
            "{$campo}.numbers" => 'La contraseña debe contener al menos un número.',
            "{$campo}.symbols" => 'La contraseña debe contener al menos un carácter especial.',
        ];
    }
}
