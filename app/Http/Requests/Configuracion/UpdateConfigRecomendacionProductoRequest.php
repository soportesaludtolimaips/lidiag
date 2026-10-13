<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateConfigRecomendacionProductoRequest extends FormRequest
{
    /**
     * Autoriza la petición a usuarios autenticados con el permiso configuracion.recomendaciones.edit.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.recomendaciones.edit');
    }

    /**
     * Reglas de validación para actualizar recomendaciones de producto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'recomendacion' => 'required|string',
            'estado' => 'nullable|boolean',
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
            'recomendacion.required' => 'La recomendación es obligatoria.',
        ];
    }
}
