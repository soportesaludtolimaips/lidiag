<?php

namespace App\Http\Requests\Dosimetria;

use Illuminate\Foundation\Http\FormRequest;

class DosimetriaFiltroRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para consultar el consolidado analítico.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para los filtros analíticos del dashboard de dosimetría.
     *
     * Flujo del proceso:
     * 1. Valida filtros opcionales de sede, modalidad, métrica y rango de fechas.
     * 2. Aplica saneamiento con fallback a todos los registros del año en curso.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function rules(): array
    {
        return [
            'sede_id' => 'sometimes|nullable|integer|exists:config_sedes,id',
            'modalidad' => 'sometimes|nullable|string|in:CT,MG,DX,CR,TODAS',
            'tipo_metrica' => 'sometimes|nullable|string|in:CTDIvol,DLP,DGM,DAP,TODAS',
            'fecha_desde' => 'sometimes|nullable|date',
            'fecha_hasta' => 'sometimes|nullable|date|after_or_equal:fecha_desde',
            'limit' => 'sometimes|nullable|integer|min:10|max:1000',
        ];
    }
}
