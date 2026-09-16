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
            'orden.study_pk' => ['required'],
            'orden.study_iuid' => ['required', 'string'],
            'orden.study_id' => ['required'],
            'orden.paciente.num_docu' => ['required', 'string'],
            'orden.paciente.nom_pacien' => ['nullable', 'string'],
            'orden.medico.num_docu' => ['required', 'string'],
            'orden.medico.name' => ['required', 'string'],
            'orden.cod_cups' => ['required', 'string'],
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
            'orden.study_pk.required' => 'El study_pk es obligatorio.',
            'orden.study_iuid.required' => 'El study_iuid es obligatorio.',
            'orden.study_iuid.string' => 'El study_iuid debe ser texto.',
            'orden.study_id.required' => 'El study_id es obligatorio.',
            'orden.paciente.num_docu.required' => 'El documento del paciente es obligatorio.',
            'orden.medico.num_docu.required' => 'El documento del médico es obligatorio.',
            'orden.medico.name.required' => 'El nombre del médico es obligatorio.',
            'orden.cod_cups.required' => 'El código CUPS es obligatorio.',
        ];
    }
}
