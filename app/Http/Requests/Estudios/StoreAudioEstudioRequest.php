<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class StoreAudioEstudioRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el registro de audios de estudio.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'audio' => ['required', 'file', 'max:102400', 'mimes:mp3,wav,m4a,webm,ogg,flac'],
            'estudio_id' => ['required', 'integer'],
            'estudio_producto_id' => ['required', 'integer'],
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function messages(): array
    {
        return [
            'audio.required' => 'No se proporcionó un archivo de audio.',
            'audio.file' => 'El audio debe ser un archivo válido.',
            'audio.max' => 'El archivo de audio no debe superar los 100 MB.',
            'audio.mimes' => 'El formato del audio no es válido. Use mp3, wav, m4a, webm, ogg o flac.',
            'estudio_id.required' => 'El ID del estudio es obligatorio.',
            'estudio_producto_id.required' => 'El ID del producto es obligatorio.',
        ];
    }
}
