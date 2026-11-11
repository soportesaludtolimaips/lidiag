<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstudioRequest extends FormRequest
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
     * Reglas de validación para registrar una asignación de estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'registro' => 'required|json',
            'archivo1' => 'nullable|file|max:10240',
            'archivo2' => 'nullable|file|max:10240',
            'archivo3' => 'nullable|file|max:10240',
        ];
    }

    /**
     * Decodifica el campo registro (JSON) a arreglo asociativo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function messages(): array
    {
        return [
            'registro.required' => 'Los datos del registro son obligatorios.',
            'registro.json' => 'El registro debe ser un JSON válido.',
        ];
    }
}
