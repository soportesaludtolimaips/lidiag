<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfigProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $productoId = $this->route('config.producto')?->id ?? $this->route('id');
        $uniqueCode = Rule::unique('config_producto', 'cod_cups');
        $uniqueName = Rule::unique('config_producto', 'nom_produc');

        if ($productoId) {
            $uniqueCode->ignore($productoId);
            $uniqueName->ignore($productoId);
        }

        return [
            'cod_cups' => ['required', 'string', 'min:2', 'max:20', $uniqueCode],
            'nom_produc' => ['required', 'string', 'min:2', 'max:200', $uniqueName],
            'porcen' => 'nullable|numeric|min:0|max:100',
            'val_produc' => 'nullable|numeric|min:0',
            'requi_lectura' => 'nullable|boolean',
            'tipo_oral' => 'nullable|boolean',
            'tipo_venoso' => 'nullable|boolean',
            'factura' => 'nullable|boolean',
            'estado' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'cod_cups.required' => 'El código Cups es obligatorio.',
            'cod_cups.unique' => 'Ya existe un producto con este código Cups.',
            'cod_cups.min' => 'El código Cups debe tener mínimo 2 caracteres.',
            'cod_cups.max' => 'El código Cups debe tener máximo 20 caracteres.',
            'nom_produc.required' => 'El nombre del producto es obligatorio.',
            'nom_produc.unique' => 'Ya existe un producto con este nombre.',
            'nom_produc.min' => 'El nombre del producto debe tener mínimo 2 caracteres.',
            'nom_produc.max' => 'El nombre del producto debe tener máximo 200 caracteres.',
        ];
    }
}
