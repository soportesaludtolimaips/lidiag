<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskReporteRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'fechaIni' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaIni',
            'sedeId' => 'nullable',
            'estadoId' => 'nullable|integer',
            'categoriaId' => 'nullable|integer',
            'prioridadId' => 'nullable|integer',
            'solicitanteId' => 'nullable|integer',
            'tecnicoId' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'fechaIni.required' => 'Debes seleccionar fecha inicio.',
            'fechaFin.required' => 'Debes seleccionar fecha fin.',
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ];
    }
}
