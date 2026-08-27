<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReporteEstudiosPACRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sedeId' => 'required|integer|min:1',
            'fechaIni' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaIni',
        ];
    }

    public function messages(): array
    {
        return [
            'sedeId.required' => 'Debes seleccionar una sede.',
            'sedeId.integer' => 'Sede inválida.',
            'fechaIni.required' => 'Debes seleccionar la fecha inicio.',
            'fechaFin.required' => 'Debes seleccionar la fecha fin.',
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ];
    }
}
