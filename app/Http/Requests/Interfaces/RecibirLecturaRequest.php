<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class RecibirLecturaRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'study_iuid' => ['required', 'string'],
            'cod_cups' => ['required', 'string'],
            'paciente.num_docu' => ['required', 'string'],
            'medico.num_docu' => ['required_without:adendum.medico.num_docu', 'string'],
        ];
    }

    public function messages()
    {
        return [
            'study_iuid.required' => 'El study_iuid es obligatorio.',
            'cod_cups.required' => 'El código CUPS es obligatorio.',
            'paciente.num_docu.required' => 'El número de documento del paciente es obligatorio.',
        ];
    }
}
