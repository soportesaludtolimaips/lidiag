<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ConfigDiagnosticoRequest extends FormRequest
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
     * Reglas de validación de diagnósticos (código, nombre y estado).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $diagnosticoId = $this->route('config.diagnostico')?->id ?? $this->route('id');

        $uniqueCode = Rule::unique('config_diagnostico', 'cod_diagnos');
        $uniqueName = Rule::unique('config_diagnostico', 'nom_diagnos');

        if ($diagnosticoId) {
            $uniqueCode->ignore($diagnosticoId);
            $uniqueName->ignore($diagnosticoId);
        }

        return [
            'cod_diagnos' => ['required', 'string', 'min:2', 'max:20', $uniqueCode],
            'nom_diagnos' => ['required', 'string', 'min:2', 'max:200', $uniqueName],
            'estado' => 'nullable|boolean',
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
            'cod_diagnos.required' => 'El código del diagnóstico es obligatorio.',
            'cod_diagnos.unique' => 'Ya existe un diagnóstico con este código.',
            'cod_diagnos.min' => 'El código del diagnóstico debe tener mínimo 2 caracteres.',
            'cod_diagnos.max' => 'El código del diagnóstico debe tener máximo 20 caracteres.',
            'nom_diagnos.required' => 'El nombre del diagnóstico es obligatorio.',
            'nom_diagnos.unique' => 'Ya existe un diagnóstico con este nombre.',
            'nom_diagnos.min' => 'El nombre del diagnóstico debe tener mínimo 2 caracteres.',
            'nom_diagnos.max' => 'El nombre del diagnóstico debe tener máximo 200 caracteres.',
        ];
    }
}
