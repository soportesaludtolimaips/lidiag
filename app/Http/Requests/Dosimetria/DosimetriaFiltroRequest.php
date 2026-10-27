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
     * Normaliza los alias de parámetros provenientes del módulo de reportes (sedeId, fechaIni, fechaFin).
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('sedeId') && ! $this->has('sede_id')) {
            $merge['sede_id'] = (int) $this->input('sedeId') > 0 ? (int) $this->input('sedeId') : null;
        } elseif ($this->has('sede_id') && (int) $this->input('sede_id') === 0) {
            $merge['sede_id'] = null;
        }

        if ($this->has('fechaIni') && ! $this->has('fecha_desde')) {
            $merge['fecha_desde'] = $this->input('fechaIni');
        }

        if ($this->has('fechaFin') && ! $this->has('fecha_hasta')) {
            $merge['fecha_hasta'] = $this->input('fechaFin');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
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
            'region_anatomica' => 'sometimes|nullable|string|in:CRANEO,TORAX,ABDOMEN,PELVIS,COLUMNA,EXTREMIDADES,MAMA,GENERAL,TODAS',
            'fecha_desde' => 'sometimes|nullable|date',
            'fecha_hasta' => 'sometimes|nullable|date|after_or_equal:fecha_desde',
            'limit' => 'sometimes|nullable|integer|min:10|max:1000',
        ];
    }
}
