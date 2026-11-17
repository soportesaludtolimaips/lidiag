<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfigWorklistDispositivoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Reglas de validación para dispositivos de flujo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function rules(): array
    {
        $dispositivoId = $this->route('dispositivo') ?? $this->route('id') ?? $this->route('config_worklist_dispositivo');
        $sedeId = $this->input('sede_id');

        $uniqueCodigo = Rule::unique('config_worklist_dispositivos', 'codigo')
            ->where(fn ($query) => $query->where('sede_id', $sedeId));

        if ($dispositivoId) {
            $uniqueCodigo->ignore($dispositivoId);
        }

        return [
            'sede_id' => ['required', 'integer', 'exists:config_sedes,id'],
            'modulo_id' => ['required', 'integer', 'exists:config_modulos,id'],
            'codigo' => ['required', 'string', 'max:50', $uniqueCodigo],
            'nombre' => ['required', 'string', 'max:150'],
            'ae_title' => ['nullable', 'string', 'max:64'],
            'modality' => ['nullable', 'string', 'max:16'],
            'ip_address' => ['nullable', 'ip'],
            'puerto' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'ubicacion' => ['nullable', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensajes descriptivos de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function messages(): array
    {
        return [
            'sede_id.required' => 'La sede activa es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no es válida.',
            'modulo_id.required' => 'El módulo del sistema es obligatorio.',
            'modulo_id.exists' => 'El módulo seleccionado no existe.',
            'codigo.required' => 'El código del dispositivo es obligatorio.',
            'codigo.unique' => 'Ya existe un dispositivo registrado con este código en la misma sede.',
            'nombre.required' => 'El nombre del dispositivo es obligatorio.',
            'ip_address.ip' => 'La dirección IP debe tener un formato de red válido.',
            'puerto.min' => 'El puerto de red debe ser mayor a 0.',
            'puerto.max' => 'El puerto de red debe ser menor o igual a 65535.',
        ];
    }
}
