<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstudioDiagnosticoRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para actualizar diagnósticos de estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'cod_diagnos' => 'nullable|string|max:50',
            'nom_diagnos' => 'nullable|string|max:255',
        ];
    }
}
