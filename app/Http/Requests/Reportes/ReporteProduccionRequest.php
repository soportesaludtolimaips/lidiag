<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReporteProduccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo' => 'required|in:Rx,Ecografia',
            'fechaIni' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaIni',
            'sedeId' => 'nullable',
            'quien_asigno' => 'nullable',
            'medico' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'Debes seleccionar el tipo de reporte.',
            'tipo.in' => 'Tipo de reporte inválido.',
            'fechaIni.required' => 'Debes seleccionar la fecha inicio.',
            'fechaFin.required' => 'Debes seleccionar la fecha fin.',
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ];
    }
}
