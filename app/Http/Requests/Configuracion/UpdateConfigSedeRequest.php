<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigSedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro' => 'required|json',
            'id' => 'required|integer|exists:config_sedes,id',
            'plantilla_rx' => 'nullable|file|max:2048',
            'plantilla_rx_ecografia' => 'nullable|file|max:2048',
        ];
    }

    public function decoded(): array
    {
        $data = json_decode($this->input('registro'), true) ?? [];
        $data['id'] = (int) $this->input('id');

        return $data;
    }
}
