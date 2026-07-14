<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigServicioCitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'estado' => 'nullable|boolean',
        ];
    }
}
