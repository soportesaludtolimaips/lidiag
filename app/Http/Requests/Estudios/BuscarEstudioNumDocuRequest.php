<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class BuscarEstudioNumDocuRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
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
     * Prepara los datos antes de la validación inyectando el parámetro de ruta.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('numDocu') && $this->route('numDocu')) {
            $this->merge([
                'numDocu' => $this->route('numDocu'),
            ]);
        }
    }

    /**
     * Reglas de validación para búsqueda de estudios por número de documento.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'numDocu' => 'required|string|max:50',
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            'numDocu.required' => 'El número de documento es obligatorio.',
            'numDocu.max' => 'El número de documento no puede superar los 50 caracteres.',
        ];
    }
}
