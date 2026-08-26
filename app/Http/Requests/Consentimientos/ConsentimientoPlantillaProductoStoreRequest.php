<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoPlantillaProductoStoreRequest extends FormRequest
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
            'requiere_firma_medico' => 'nullable|boolean',
        ];
    }
}
