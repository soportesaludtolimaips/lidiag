<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar la confirmación y persistencia de tarifas de entidad.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class ContratoConfirmarEntidadRequest extends FormRequest
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
            'altas' => 'sometimes|array',
            'altas.*.producto_id' => 'required|integer|exists:config_productos,id',
            'altas.*.valor_contrato' => 'required|numeric|min:0',
            'cambios' => 'sometimes|array',
            'cambios.*.id' => 'required|integer|exists:contratos_productos,id',
            'cambios.*.valor_contrato' => 'required|numeric|min:0',
            'retiros' => 'sometimes|array',
            'retiros.*.id' => 'required|integer|exists:contratos_productos,id',
        ];
    }
}
