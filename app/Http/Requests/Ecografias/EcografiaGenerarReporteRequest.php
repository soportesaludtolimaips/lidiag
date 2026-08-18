<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaGenerarReporteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'medico_id' => 'required|integer',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'tipo_estudio' => 'nullable|string',
            'lectura' => 'nullable|string',
            'opinion' => 'nullable|string',
            'fec_estudio' => 'nullable|date',
            'fehor_lectura' => 'nullable|date',
            'fehor_trascrito' => 'nullable|date',
            'cod_cups' => 'nullable|string',
            'nom_produc' => 'nullable|string',
            'nom_sede' => 'nullable|string',
            'fec_naci' => 'nullable|date',
            'sexo' => 'nullable|string|max:1',
            'edad' => 'nullable|string',
            'dir' => 'nullable|string',
            'tel' => 'nullable|string',
            'cel' => 'nullable|string',
            'atencion' => 'nullable|string',
            'admin_salud' => 'nullable|string',
            'contrato' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
            'medico_id.required' => 'El médico es obligatorio.',
            'num_docu.required' => 'El número de documento es obligatorio.',
            'nom_pacien.required' => 'El nombre del paciente es obligatorio.',
            'fec_estudio.date' => 'La fecha del estudio debe ser una fecha válida.',
            'fehor_lectura.date' => 'La fecha de lectura debe ser una fecha válida.',
            'fehor_trascrito.date' => 'La fecha de transcripción debe ser una fecha válida.',
            'fec_naci.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'sexo.max' => 'El sexo no debe exceder 1 carácter.',
        ];
    }
}
