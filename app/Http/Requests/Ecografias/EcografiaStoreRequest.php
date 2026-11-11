<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaStoreRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para registrar una ecografía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'num_docu' => 'required|string',
            'nombres' => 'required|string',
            'pri_ape' => 'nullable|string',
            'sede_id' => 'required|integer',
            'atencion_id' => 'required|string|max:20',
            'medico_id' => 'required|integer',
            'productosAgenda' => 'required|array',
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            'num_docu.required' => 'El número de documento es obligatorio.',
            'nombres.required' => 'El nombre es obligatorio.',
            'sede_id.required' => 'La sede es obligatoria.',
            'atencion_id.required' => 'La atención es obligatoria.',
            'medico_id.required' => 'El médico es obligatorio.',
            'productosAgenda.required' => 'Debe agregar al menos un producto a la ecografía.',
        ];
    }
}
