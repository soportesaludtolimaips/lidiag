<?php

namespace App\Http\Requests\Consentimientos;

use Illuminate\Foundation\Http\FormRequest;

class ConsentimientoMarcaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'imagen_id' => 'nullable|integer|exists:consentimiento_plantilla_imagenes,id',
            'tipo_marca' => 'required|in:punto,circulo,flecha,mano_alzada',
            'posicion_x' => 'required|numeric',
            'posicion_y' => 'required|numeric',
            'ancho' => 'nullable|numeric',
            'alto' => 'nullable|numeric',
            'descripcion' => 'nullable|string|max:500',
            'datos_dibujo' => 'nullable|array',
        ];
    }
}
