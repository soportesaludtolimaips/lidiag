<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * El documento y el nombre del paciente forman parte del nombre de los archivos del informe
 * (`{estudio}-{documento}-{nombre}.pdf`, en storage/app/reporte_lecturas). Sin esta regla un valor con «../» o
 * «\» haría que se leyera, escribiera o borrara un archivo fuera de esa carpeta.
 *
 * Ningún documento ni nombre legítimo lleva estos caracteres (en Windows tampoco pueden ir en un nombre de archivo).
 */
class SinCaracteresDeRuta implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match('/[\/\\\\:*?"<>|\x00-\x1F]/', $value) === 1) {
            $fail('El campo :attribute contiene caracteres no permitidos ( / \ : * ? " < > | ).');
        }
    }
}
