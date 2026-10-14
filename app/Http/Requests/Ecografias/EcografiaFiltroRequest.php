<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaFiltroRequest extends FormRequest
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
     * Reglas de validación para filtrar ecografías.
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
            'bus_nom_num_docu' => 'nullable|string|max:255',
            'bus_fech_ini' => 'nullable|date',
            'bus_fecha_fin' => 'nullable|date',
        ];
    }
}
