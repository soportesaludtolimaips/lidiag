<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskCommentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'comentario' => 'required|string',
        ];
    }

    public function messages()
    {
        return [
            'comentario.required' => 'El comentario es obligatorio',
        ];
    }
}
