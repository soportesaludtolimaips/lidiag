<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskChangeStatusRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'estado_id' => 'required|integer|exists:help_desk_estados,id',
            'comentario' => 'nullable|string|max:10000|required_without:descripcion_solucion',
            'descripcion_solucion' => 'nullable|string|max:10000',
        ];
    }

    public function messages()
    {
        return [
            'estado_id.required' => 'El estado es obligatorio',
        ];
    }
}
