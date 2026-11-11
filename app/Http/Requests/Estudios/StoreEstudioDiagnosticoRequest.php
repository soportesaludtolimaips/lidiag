<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudioDiagnosticoRequest extends FormRequest
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
     * Reglas de validación para crear diagnósticos de estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'estudio_id' => 'required|integer|exists:estudios,id',
            'cod_diagnos' => 'required|string|max:50',
            'nom_diagnos' => 'required|string|max:255',
        ];
    }
}
