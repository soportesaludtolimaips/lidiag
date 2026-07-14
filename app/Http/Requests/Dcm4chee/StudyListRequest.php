<?php

namespace App\Http\Requests\Dcm4chee;

use Illuminate\Foundation\Http\FormRequest;

class StudyListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_nom_num_docu' => 'nullable|string|max:30',
            'bus_fehc_ini' => 'nullable|date|required_with:bus_fecha_fin',
            'bus_fecha_fin' => 'nullable|date|required_with:bus_fehc_ini|after_or_equal:bus_fehc_ini',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.integer' => 'La sede debe ser un número entero.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'bus_nom_num_docu.string' => 'El nombre o número de documento debe ser una cadena de texto.',
            'bus_nom_num_docu.max' => 'El nombre o número de documento debe tener menos de 30 caracteres.',
            'bus_fehc_ini.date' => 'La fecha de inicio debe ser una fecha válida.',
        ];
    }
}
