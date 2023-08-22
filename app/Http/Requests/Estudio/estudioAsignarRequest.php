<?php

namespace App\Http\Requests\Estudio;

use Illuminate\Foundation\Http\FormRequest;

class estudioAsignarRequest extends FormRequest
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
            'study_datetime' => 'required',
            //'accession_no' => 'required|min:4|max:50',
            //'study_desc' => 'required',
            'num_docu' => 'required|min:3|max:20',
            'nombres' => 'required|min:3|max:50',
            'medico_id' => 'required',
            'prioridad_id' => 'required',
            'productosEstudio' => 'required',
            'diagnosticosEstudio' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'study_datetime.required' => 'Te hizo falta la fecha del estudio.',
            //'accession_no.required' => 'Te hizo falta el detalle del estudio.',
            //'study_desc.required' => 'Te hizo falta la descripción del estudio.',
            'num_docu.required' => 'Te hizo falta el número de documento del paciente.',
            'num_docu.min' => 'El número de documento del paciente debe tener mínimo 3 caracteres.',
            'num_docu.max' => 'El número de documento del paciente debe tener máximo 20 caracteres.',
            'nombres.required' => 'Te hizo falta el nombre del paciente.',
            'nombres.min' => 'El nombre del paciente debe tener mínimo 3 caracteres.',
            'nombres.max' => 'El nombre del paciente debe tener máximo 50 caracteres.',
            'medico_id.required' => 'Te hizo falta el médico.',
            'prioridad_id.required' => 'Te hizo falta la prioridad.',
            'productosEstudio.required' => 'Te hizo falta almenos un producto.',
            'diagnosticosEstudio.required' => 'Te hizo falta almenos un diagnostico.'
        ];
    }
}
