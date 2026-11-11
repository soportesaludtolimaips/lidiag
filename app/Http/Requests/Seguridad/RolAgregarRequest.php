<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class RolAgregarRequest extends FormRequest
{
    /**
     * Detalle y descripción de la función
     *
     * @param  void  $args  Sin parámetros.
     * @return bool true: la autorización la resuelven los middleware de permisos.
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
     * Detalle y descripción de la función
     *
     * @param  void  $args  Sin parámetros.
     * @return array Reglas de validación del nombre y la lista de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'name' => 'required|unique:roles|min:2|max:100',
            'permisosRol' => 'nullable|array',
            'permisosRol.*' => 'nullable|array',
            'permisosRol.*.id' => 'required_with:permisosRol.*|integer',
            'permisosRol.*.name' => 'required_with:permisosRol.*|string',
            'permisosRol.*.checked' => 'required_with:permisosRol.*|boolean',
        ];
    }

    /**
     * Detalle y descripción de la función
     *
     * @param  void  $args  Sin parámetros.
     * @return array Mensajes de validación en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El campo nombre es obligatorio.',
            'name.min' => 'El nombre del rol debe tener como mínimo 2 caracteres.',
            'name.max' => 'El nombre del rol debe tener como máximo 100 caracteres.',
            'name.unique' => 'El nombre del rol ya se encuentra registrado.',
        ];
    }
}
