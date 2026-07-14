<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskReassignRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'asignado_id' => 'required|integer|exists:users,id',
            'comentario' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'asignado_id.required' => 'El usuario asignado es obligatorio',
            'comentario.required' => 'El comentario es obligatorio',
        ];
    }
}
