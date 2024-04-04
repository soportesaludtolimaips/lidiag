<?php

namespace App\Http\Requests\Estudio;

use Illuminate\Foundation\Http\FormRequest;

class estudioLeerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'lectura' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'lectura.required' => 'Te hizo falta la lectura del estudio',
        ];
    }
}
