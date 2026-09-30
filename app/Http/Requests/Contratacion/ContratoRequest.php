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
     * Decodifica el campo JSON `registro` y lo mezcla en los datos de la petición
     * para que las reglas anidadas (registro.sede_id, etc.) funcionen correctamente.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-16
     */
    public function prepareForValidation(): void
    {
        $decoded = json_decode($this->input('registro', '{}'), true);

        if (is_array($decoded)) {
            $this->merge(['registro_decoded' => $decoded]);
        }
    }

    /**
     * Reglas de validación para los campos del formulario.
     * Valida registro como JSON y los campos decodificados como anidados.
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
            'registro_decoded.sede_id' => 'required|integer|exists:config_sedes,id',
            'registro_decoded.fecha_inicio' => 'required|date',
            'registro_decoded.fecha_fin' => 'required|date|after_or_equal:registro_decoded.fecha_inicio',
            'registro_decoded.observaciones' => 'nullable|string|max:1000',
            'registro_decoded.estado' => 'nullable|in:0,1',
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
            'registro_decoded.sede_id.required' => 'La sede es obligatoria',
            'registro_decoded.sede_id.exists' => 'La sede seleccionada no existe',
            'registro_decoded.fecha_inicio.required' => 'La fecha de inicio es obligatoria',
            'registro_decoded.fecha_inicio.date' => 'La fecha de inicio no es válida',
            'registro_decoded.fecha_fin.required' => 'La fecha de fin es obligatoria',
            'registro_decoded.fecha_fin.date' => 'La fecha de fin no es válida',
            'registro_decoded.fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio',
            'registro_decoded.observaciones.max' => 'Las observaciones no deben exceder 1000 caracteres',
            'registro_decoded.estado.in' => 'El estado debe ser activo (1) o inactivo (0)',
        ];
    }
}
