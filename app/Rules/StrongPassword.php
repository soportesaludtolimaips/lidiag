<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
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
