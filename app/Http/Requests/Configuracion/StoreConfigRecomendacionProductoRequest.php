<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreConfigRecomendacionProductoRequest extends FormRequest
{
    /**
     * Autoriza la petición a usuarios autenticados con el permiso configuracion.recomendaciones.create.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('configuracion.recomendaciones.create');
    }

    /**
     * Reglas de validación para registrar recomendaciones de producto.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'producto_id' => 'required|integer|exists:config_productos,id',
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
            'producto_id.required' => 'El producto es obligatorio.',
            'recomendacion.required' => 'La recomendación es obligatoria.',
        ];
    }
}
