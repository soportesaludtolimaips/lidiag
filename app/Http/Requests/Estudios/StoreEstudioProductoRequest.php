<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudioProductoRequest extends FormRequest
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
     * Reglas de validación para crear productos de estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'estudio_id' => 'required|integer|exists:estudios,id',
            'producto_id' => 'nullable|integer',
            'transcriptor_id' => 'nullable|integer',
            'cod_cups' => 'required|string|max:50',
            'nom_produc' => 'required|string|max:255',
            'fechor_lectura' => 'nullable|date',
            'lectura' => 'nullable|string',
            'fechor_trascrito' => 'nullable|date',
            'medico_ordena' => 'nullable|string|max:255',
            'visible' => 'nullable|boolean',
        ];
    }
}
