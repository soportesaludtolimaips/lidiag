<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class RolActualizarRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado.
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
     * Reglas de validación para actualizar un rol y sus permisos asignados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "registro" => ["required", "array"],
            "registro.name" => ["required", "string", "min:2", "max:100"],
            "registro.permisosRol" => ["nullable", "array"],
            "registro.permisosRol.*" => ["nullable", "array"],
            "registro.permisosRol.*.id" => ["required_with:registro.permisosRol.*", "integer"],
            "registro.permisosRol.*.name" => ["required_with:registro.permisosRol.*", "string"],
            "registro.permisosRol.*.checked" => ["required_with:registro.permisosRol.*", "boolean"],
        ];
    }

    /**
     * Mensajes de error personalizados para la validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages(): array
    {
        return [
            "registro.required" => "Los datos del rol son obligatorios.",
            "registro.name.required" => "El nombre del rol es obligatorio.",
            "registro.name.min" => "El nombre del rol debe tener al menos 2 caracteres.",
            "registro.name.max" => "El nombre del rol no debe superar 100 caracteres.",
        ];
    }
}
