<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class ListarEstudiosNotificarRequest extends FormRequest
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
     * Reglas de validación para el listado de estudios a notificar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'bus_num_docu' => 'nullable|string|max:50',
            'bus_nom' => 'nullable|string|max:255',
            'bus_fech_ini' => 'nullable|date',
            'bus_fecha_fin' => 'nullable|date',
        ];
    }
}
