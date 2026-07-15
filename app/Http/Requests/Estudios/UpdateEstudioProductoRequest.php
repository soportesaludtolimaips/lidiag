<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstudioProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'producto_id' => 'nullable|integer',
            'transcriptor_id' => 'nullable|integer',
            'cod_cups' => 'nullable|string|max:50',
            'nom_produc' => 'nullable|string|max:255',
            'fechor_lectura' => 'nullable|date',
            'lectura' => 'nullable|string',
            'fechor_trascrito' => 'nullable|date',
            'medico_ordena' => 'nullable|string|max:255',
            'visible' => 'nullable|boolean',
        ];
    }
}
