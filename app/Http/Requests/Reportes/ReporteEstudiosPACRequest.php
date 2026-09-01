<?php

namespace App\Http\Requests\Reportes;

use Illuminate\Foundation\Http\FormRequest;

class ReporteEstudiosPACRequest extends FormRequest
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
     * Define las reglas de validación para el reporte de estudios PAC.
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
            'sedeId' => 'required|integer|min:1',
            'fechaIni' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaIni',
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
            'sedeId.required' => 'Debes seleccionar una sede.',
            'sedeId.integer' => 'Sede inválida.',
            'fechaIni.required' => 'Debes seleccionar la fecha inicio.',
            'fechaFin.required' => 'Debes seleccionar la fecha fin.',
            'fechaFin.after_or_equal' => 'La fecha fin no puede ser menor que la fecha inicio.',
        ];
    }
}
