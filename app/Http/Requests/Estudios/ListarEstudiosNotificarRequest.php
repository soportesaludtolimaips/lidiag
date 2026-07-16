<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class ListarEstudiosNotificarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
