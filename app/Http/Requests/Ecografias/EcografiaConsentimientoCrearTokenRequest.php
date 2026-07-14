<?php

namespace App\Http\Requests\Ecografias;

use Illuminate\Foundation\Http\FormRequest;

class EcografiaConsentimientoCrearTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ecografia_producto_id' => 'required|integer|exists:ecografias_productos,id',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'firmante_tipo' => 'required|in:PACIENTE,ACOMPANANTE',
            'acompanante' => 'nullable|array',
            'acompanante.cedula' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:30',
            'acompanante.nombres' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:200',
            'acompanante.email' => 'required_if:firmante_tipo,ACOMPANANTE|email|max:150',
            'acompanante.direccion' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:255',
            'acompanante.telefono' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:50',
        ];
    }
}
