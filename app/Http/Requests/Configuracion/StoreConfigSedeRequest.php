<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigSedeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro' => 'required|json',
            'plantilla_rx' => 'nullable|file|max:2048',
            'plantilla_rx_ecografia' => 'nullable|file|max:2048',
        ];
    }

    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }
}
