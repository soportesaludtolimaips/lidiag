<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class EnviarOrdenTeleRadiologiaRequest extends FormRequest
{
    /**
     * Determina si el usuario puede autorizar la solicitud.
     *
     * @returns bool Siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Define las reglas de validación para enviar orden de tele-radiología.
     *
     * @returns array<string, array> Reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules()
    {
        return [
            'orden' => ['required', 'array'],
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array<string, string> Mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function messages()
    {
        return [
            'orden.required' => 'No se recibieron datos de la orden.',
            'orden.array' => 'La orden debe ser un arreglo.',
        ];
    }
}
