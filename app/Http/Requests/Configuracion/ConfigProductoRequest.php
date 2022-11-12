<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfigProductoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'cod_cups' => 'required|min:2|max:20',
            'nom_produc' => 'required|min:2|max:200',
        ];
    }

    public function messages()
    {
        return [
            'cod_cups.required' => 'Te hizo falta el código Cups',
            'cod_cups.min' => 'El código Cups debe tener mínimo 2 caracteres',
            'cod_cups.max' => 'El código Cups debe tener máximo 20 caracteres',
            'nom_produc.required' => 'Te hizo falta el nombre del producto',
            'nom_produc.min' => 'El nombre del producto debe tener mínimo 2 caracteres',
            'nom_produc.max' => 'El nombre del producto debe tener máximo 200 caracteres',
        ];
    }
}
