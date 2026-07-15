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
            'fechaIni' => 'nullable|date',
            'fechaFin' => 'nullable|date',
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
            'sedeId' => 'nullable|integer',
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
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $hasIni = $this->filled('fechaIni') || $this->filled('desde');
            $hasFin = $this->filled('fechaFin') || $this->filled('hasta');

            if ($hasIni && ! $hasFin) {
                $validator->errors()->add('fechaFin', 'Debes seleccionar fecha fin.');
            }
            if (! $hasIni && $hasFin) {
                $validator->errors()->add('fechaIni', 'Debes seleccionar fecha inicio.');
            }

            if ($hasIni && ! $hasFin && ! $validator->errors()->has('fechaFin')) {
                $validator->errors()->add('fechaFin', 'Debes seleccionar fecha fin.');
            }
        });
    }
}
