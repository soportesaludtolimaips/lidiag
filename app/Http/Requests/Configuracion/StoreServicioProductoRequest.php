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
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'servicio_id' => 'required|integer|exists:config_servicios,id',
            'producto_id' => 'required|integer|exists:config_productos,id',
        ];
    }
}
