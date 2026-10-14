<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class ListarEstudiosAsignadosRequest extends FormRequest
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
     * Reglas de validación para listar estudios asignados a médicos.
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
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_fech_ini' => 'nullable|date',
            'bus_fecha_fin' => 'nullable|date',
            'bus_nom_num_docu' => 'nullable|string|max:255',
            'quien_asigno_id' => 'nullable|integer|exists:users,id',
            'medico_id' => 'nullable|integer|exists:users,id',
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
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ];
    }
}
