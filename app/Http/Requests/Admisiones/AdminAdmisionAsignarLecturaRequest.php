<?php

namespace App\Http\Requests\Admisiones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para enviar a lectura médica un registro pendiente desde Pendientes de asignar.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionAsignarLecturaRequest extends FormRequest
{
    /**
     * Autoriza la petición delegando el control de acceso al middleware del controlador.
     *
     * Flujo del proceso:
     * 1. Retorna true habilitando la validación de campos.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación homologadas con StoreEstudioRequest para envío multipart con soportes HC.
     *
     * Flujo del proceso:
     * 1. Valida que el campo registro contenga una cadena JSON válida.
     * 2. Valida hasta 3 archivos adjuntos de soporte clínico de máximo 10 MB cada uno.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function rules(): array
    {
        return [
            "registro" => "required|json",
            "archivo1" => "nullable|file|max:10240",
            "archivo2" => "nullable|file|max:10240",
            "archivo3" => "nullable|file|max:10240",
        ];
    }

    /**
     * Decodifica el payload JSON de registro hacia un arreglo asociativo PHP.
     *
     * Flujo del proceso:
     * 1. Decodifica el campo registro asegurando retorno de arreglo vacío en caso de estructura nula.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function decoded(): array
    {
        return json_decode((string) $this->input("registro"), true) ?? [];
    }

    /**
     * Mensajes de validación en español para el envío a lectura.
     *
     * Flujo del proceso:
     * 1. Retorna los textos descriptivos ante errores de formato o tamaño de adjuntos.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function messages(): array
    {
        return [
            "registro.required" => "Los datos de la asignación del estudio son obligatorios.",
            "registro.json" => "El formato de datos de la asignación no es un JSON válido.",
            "archivo1.max" => "El Adjunto 1 no debe superar los 10 MB.",
            "archivo2.max" => "El Adjunto 2 no debe superar los 10 MB.",
            "archivo3.max" => "El Adjunto 3 no debe superar los 10 MB.",
        ];
    }
}
