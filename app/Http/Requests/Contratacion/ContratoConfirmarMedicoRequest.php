<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar la confirmación y persistencia de tarifas de médico.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class ContratoConfirmarMedicoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'medico_id' => 'required|integer|exists:users,id',
            'altas' => 'sometimes|array',
            'altas.*.producto_id' => 'required|integer|exists:config_productos,id',
            'altas.*.valor_consulta' => 'required|numeric|min:0',
            'altas.*.valor_urgencias' => 'required|numeric|min:0',
            'cambios' => 'sometimes|array',
            'cambios.*.id' => 'required|integer|exists:contratos_medicos,id',
            'cambios.*.valor_consulta' => 'required|numeric|min:0',
            'cambios.*.valor_urgencias' => 'required|numeric|min:0',
            'retiros' => 'sometimes|array',
            'retiros.*.id' => 'required|integer|exists:contratos_medicos,id',
        ];
    }
}
