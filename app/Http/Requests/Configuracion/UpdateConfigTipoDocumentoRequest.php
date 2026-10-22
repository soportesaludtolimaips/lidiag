<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateConfigTipoDocumentoRequest extends FormRequest
{
    /**
     * Autoriza la petición a usuarios autenticados con el permiso config.tipo.documento.edit.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->can('config.tipo.documento.edit');
    }

    /**
     * Reglas de validación para actualizar tipos de documento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $tipoDocumentoId = $this->route('config.tipo.documento')?->id ?? $this->route('id');

        return [
            'codigo' => ['required', 'string', 'max:20', Rule::unique('config_tipo_documento', 'codigo')->ignore($tipoDocumentoId)],
            'tipo_documento' => ['required', 'string', 'max:255', Rule::unique('config_tipo_documento', 'tipo_documento')->ignore($tipoDocumentoId)],
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
            'codigo.required' => 'El código es obligatorio.',
            'codigo.unique' => 'Ya existe un tipo de documento con este código.',
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.unique' => 'Ya existe un tipo de documento con este nombre.',
        ];
    }
}
