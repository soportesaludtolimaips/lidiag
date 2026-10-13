<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class RecibirLecturaRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
     *
     * @return bool
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Reglas de validación para recibir una lectura de estudio.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules()
    {
        return [
            'study_iuid' => ['required', 'string'],
            'cod_cups' => ['required', 'string'],
            'paciente.num_docu' => ['required', 'string'],
            'medico.num_docu' => ['required_without:adendum.medico.num_docu', 'string'],
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages()
    {
        return [
            'study_iuid.required' => 'El study_iuid es obligatorio.',
            'cod_cups.required' => 'El código CUPS es obligatorio.',
            'paciente.num_docu.required' => 'El número de documento del paciente es obligatorio.',
        ];
    }
}
