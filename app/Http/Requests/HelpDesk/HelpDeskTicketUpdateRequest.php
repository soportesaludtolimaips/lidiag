<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskTicketUpdateRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'nullable|integer|exists:help_desk_categorias,id',
            'prioridad_id' => 'nullable|integer|exists:help_desk_prioridades,id',
        ];
    }
}
