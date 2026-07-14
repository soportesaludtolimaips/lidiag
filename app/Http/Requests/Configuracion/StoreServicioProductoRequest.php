<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'servicio_cita_id' => 'required|integer|exists:config_servicio_citas,id',
            'producto_id' => 'required|integer|exists:config_productos,id',
        ];
    }
}
