<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
     * Prepara los datos antes de la validación saneando archivos opcionales.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-28
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('firma') && in_array($this->input('firma'), ['null', 'undefined', '', null], true)) {
            $this->request->remove('firma');
        }
        if ($this->has('avatar') && in_array($this->input('avatar'), ['null', 'undefined', '', null], true)) {
            $this->request->remove('avatar');
        }
    }

    /**
     * Reglas de validación para registrar un usuario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
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
}
