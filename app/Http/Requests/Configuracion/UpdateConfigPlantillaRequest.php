<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateConfigPlantillaRequest extends FormRequest
{
    /**
     * Autoriza la petición a usuarios autenticados con el permiso config.plantillas.edit.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('config.plantillas.edit');
    }

    /**
     * Reglas de validación para actualizar plantillas de reporte.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'plantilla_tipo_id' => 'required|integer|exists:config_plantillas_tipos,id',
            'medico_id' => 'nullable|integer|exists:users,id',
            'nom_plantilla' => 'required|string|max:255',
            'plantilla' => 'required|string',
            'opinion' => 'nullable|string',
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
            'plantilla_tipo_id.required' => 'El tipo de plantilla es obligatorio.',
            'nom_plantilla.required' => 'El nombre de la plantilla es obligatorio.',
            'plantilla.required' => 'El contenido de la plantilla es obligatorio.',
        ];
    }
}
