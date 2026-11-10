<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request para validar la petición de vista previa de importación de tarifas de médico.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class ContratoPreviewMedicoRequest extends FormRequest
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
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
            'medico_id' => 'required|integer|exists:users,id',
        ];
    }

    /**
     * Mensajes de error en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'archivo.required' => 'El archivo Excel de tarifas de médico es obligatorio.',
            'archivo.file' => 'El archivo debe ser válido.',
            'archivo.mimes' => 'El archivo debe tener formato .xlsx o .xls.',
            'archivo.max' => 'El tamaño del archivo no puede superar 10 MB.',
            'medico_id.required' => 'Debe seleccionar el médico lector a configurar.',
            'medico_id.exists' => 'El médico seleccionado no existe en el sistema.',
        ];
    }
}
