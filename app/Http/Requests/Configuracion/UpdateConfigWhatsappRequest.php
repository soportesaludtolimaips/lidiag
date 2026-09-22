<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigWhatsappRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'servicio_id' => 'nullable|integer|exists:config_servicios,id',
            'mensaje_bienvenida' => 'required|string',
            'activo' => 'nullable|boolean',
        ];
    }
}
