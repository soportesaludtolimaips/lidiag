<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReporteProduccionRequest extends FormRequest
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
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para el reporte de producción.
     *
     * @returns array<string, string> Reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array<string, string> Mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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
