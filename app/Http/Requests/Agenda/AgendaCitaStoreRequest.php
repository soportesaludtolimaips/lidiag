<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

class AgendaCitaStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
            'numero_orden' => 'nullable|string|max:50',
            'numero_autorizacion' => 'nullable|string|max:50',
            'telefono' => 'required|string',
            'num_docu' => 'nullable|string',
            'nombres' => 'nullable|string',
            'direccion' => 'nullable|string',
            'email' => 'nullable|email',
        ];
    }

    public function messages(): array
    {
        return [
            'servicio_cita_id.required' => 'El servicio de cita es obligatorio.',
            'servicio_cita_id.exists' => 'El servicio de cita seleccionado no existe.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
        ];
    }
}
