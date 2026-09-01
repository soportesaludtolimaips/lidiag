<?php

namespace App\Http\Requests\Contratacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest ContratoRequest: validación de datos para crear/actualizar contratos.
 *
 * Acepta un campo `registro` en formato JSON que contiene los datos
 * del contrato (sede_id, fecha_inicio, fecha_fin, observaciones, estado).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class ContratoRequest extends FormRequest
{
    /**
     * Autoriza que cualquier usuario autenticado realice la petición.
     *
     * @returns bool true siempre.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para los campos del formulario.
     *
     * @returns array<string, string> Reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'registro' => 'required|json',
        ];
    }

    /**
     * Decodifica el campo JSON `registro` a un array asociativo.
     *
     * @returns array<int|string, mixed> Datos decodificados del contrato o array vacío si falla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }

    /**
     * Mensajes de error personalizados para las reglas de validación.
     *
     * @returns array<string, string> Mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function messages(): array
    {
        return [
            'registro.required' => 'Los datos del contrato son obligatorios',
            'registro.json' => 'El formato de datos no es válido',
        ];
    }
}
