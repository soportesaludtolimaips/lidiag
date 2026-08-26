<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoMarcaUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_marca' => 'sometimes|in:punto,circulo,flecha,mano_alzada',
            'posicion_x' => 'sometimes|numeric',
            'posicion_y' => 'sometimes|numeric',
            'ancho' => 'nullable|numeric',
            'alto' => 'nullable|numeric',
            'descripcion' => 'nullable|string|max:500',
            'datos_dibujo' => 'nullable|array',
        ];
    }
}
