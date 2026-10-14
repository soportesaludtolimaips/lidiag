<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar la importación masiva de médicos-producto desde Excel.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class ContratoImportarMedicosRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permiso para realizar esta petición.
     *
     * @returns bool siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define las reglas de validación para importar médicos desde Excel.
     *
     * @returns array reglas de validación: archivo, medico_id, crear_faltantes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "archivo" => "required|file|mimes:xlsx,xls|max:10240",
            "medico_id" => "required|integer|exists:users,id",
            "crear_faltantes" => "nullable|boolean",
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            "archivo.required" => "El archivo Excel es obligatorio.",
            "archivo.file" => "El archivo debe ser un archivo válido.",
            "archivo.mimes" => "El archivo debe tener formato .xlsx o .xls.",
            "archivo.max" => "El tamaño del archivo no puede exceder los 10 MB.",
            "medico_id.required" => "El médico es obligatorio.",
            "medico_id.exists" => "El médico seleccionado no existe.",
        ];
    }
}
