<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoGenerarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'required|integer|exists:config_productos,id',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'origen_tipo' => 'nullable|in:ECOGRAFIA,ESTUDIO,ATENCION,MANUAL',
            'origen_id' => 'nullable|integer',
            'num_docu' => 'required|string|max:50',
            'nombres' => 'required|string|max:255',
            'apellidos' => 'nullable|string|max:255',
            'medico_id' => 'nullable|integer|exists:users,id',
            'observaciones' => 'nullable|string|max:2000',
        ];
    }
}
