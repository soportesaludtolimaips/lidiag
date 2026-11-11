<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class HelpDeskReporteRequest extends FormRequest
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
     * Reglas de validación para el reporte de tickets.
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
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ];
    }

    /**
     * Valida que las fechas de inicio y fin se proporcionen de forma conjunta.
     *
     * @param  Validator  $validator  Instancia del validador.
     * @return void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
