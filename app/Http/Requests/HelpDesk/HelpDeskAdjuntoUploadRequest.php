<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskAdjuntoUploadRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'comentario' => 'nullable|string',
            'files' => 'required',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ];
    }

    public function messages()
    {
        return [
            'files.required' => 'Debes adjuntar al menos un archivo',
            'files.*.file' => 'El archivo no es válido',
            'files.*.max' => 'El archivo no debe exceder 10MB',
            'files.*.mimes' => 'El tipo de archivo no está permitido',
        ];
    }
}
