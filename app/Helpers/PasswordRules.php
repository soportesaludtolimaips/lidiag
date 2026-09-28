<?php

namespace App\Helpers;

use Illuminate\Validation\Rules\Password;

class PasswordRules
{
    public static function nueva(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
    }

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
