<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreConfigPlantillaTipoRequest extends FormRequest
{
    /**
     * Autoriza la petición a usuarios autenticados con el permiso config.plantillas.tipos.create.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('config.plantillas.tipos.create');
    }

    /**
     * Reglas de validación para registrar tipos de plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'nom_tipo' => 'required|string|max:255',
            'producto_id' => 'nullable|integer|exists:config_productos,id',
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
            'nom_tipo.required' => 'El nombre del tipo de plantilla es obligatorio.',
        ];
    }
}
