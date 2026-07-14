<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class CargarAudioTranscribirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'max:102400'],
            'estudio_id' => ['required', 'integer'],
            'estudio_producto_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'audio.required' => 'No se proporcionó un archivo de audio.',
            'audio.max' => 'El archivo de audio no debe superar los 100 MB.',
            'estudio_id.required' => 'El ID del estudio es obligatorio.',
            'estudio_producto_id.required' => 'El ID del producto es obligatorio.',
        ];
    }
}
