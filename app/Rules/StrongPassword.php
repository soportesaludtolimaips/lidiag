<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Valida que la contraseña cumpla los requisitos de fortaleza.
     *
     * Comprueba longitud mínima, mayúscula, número y carácter especial,
     * y reporta el primer incumplimiento mediante el callback de fallo.
     *
     * @param  string  $attribute  Nombre del campo validado.
     * @param  mixed  $value  Valor de la contraseña a validar.
     * @param  Closure  $fail  Callback que registra el mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strlen($value) < 8) {
            $fail('Min 8 caracteres.');

            return;
        }
        if (! preg_match('/[A-Z]/', $value)) {
            $fail('Requiere mayúscula.');

            return;
        }
        if (! preg_match('/[0-9]/', $value)) {
            $fail('Requiere número.');

            return;
        }
        if (! preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $fail('Requiere especial.');
        }
    }
}
