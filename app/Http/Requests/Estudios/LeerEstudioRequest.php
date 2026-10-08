<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class LeerEstudioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
     *
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
     * Reglas de validación para marcar un estudio como leído.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'lectura' => 'required|string|min:3',
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function messages(): array
    {
        return [
            'lectura.required' => 'Debe ingresar la lectura.',
            'lectura.min' => 'La lectura debe tener al menos 3 caracteres.',
        ];
    }
}
