<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfigPrioridadRequest extends FormRequest
{
    /**
     * Autoriza la petición únicamente a usuarios autenticados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Reglas de validación de prioridades (nombre).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $prioridadId = $this->route('config.prioridad')?->id ?? $this->route('id');
        $uniqueRule = Rule::unique('config_prioridad', 'nom_priori');

        if ($prioridadId) {
            $uniqueRule->ignore($prioridadId);
        }

        return [
            'nom_priori' => ['required', 'string', 'min:2', 'max:50', $uniqueRule],
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            'nom_priori.required' => 'El nombre de la prioridad es obligatorio.',
            'nom_priori.unique' => 'Ya existe una prioridad con este nombre.',
            'nom_priori.min' => 'La prioridad debe tener mínimo 2 caracteres.',
            'nom_priori.max' => 'La prioridad debe tener máximo 50 caracteres.',
        ];
    }
}
