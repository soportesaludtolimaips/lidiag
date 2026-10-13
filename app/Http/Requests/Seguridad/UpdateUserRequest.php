<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
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
     * Reglas de validación para actualizar un usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer|exists:users,id',
            'registro' => 'required|json',
            'firma' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'avatar' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg|max:10240',
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
            'registro.required' => 'Los datos del usuario son obligatorios.',
            'registro.json' => 'Los datos del usuario deben tener un formato válido.',
            'firma.mimes' => 'La firma debe ser una imagen de tipo: jpeg, png, jpg, gif, svg.',
            'firma.max' => 'La firma no debe exceder los 10 MB.',
            'avatar.mimes' => 'El avatar debe ser una imagen de tipo: jpeg, png, jpg, gif, svg.',
            'avatar.max' => 'El avatar no debe exceder los 10 MB.',
        ];
    }

    /**
     * Decodifica el campo registro (JSON) a arreglo asociativo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function decoded(): array
    {
        return json_decode($this->input('registro'), true) ?? [];
    }

    /**
     * Obtiene el ID del usuario a actualizar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function targetId(): int
    {
        return (int) $this->input('id');
    }
}
