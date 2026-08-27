<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ConfigSedeBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'logo' => 'nullable|file|image|mimes:jpg,jpeg,png|max:2048',
            'encabezado' => 'nullable|string|max:1000',
            'pie_pagina' => 'nullable|string|max:1000',
        ];
    }
}
