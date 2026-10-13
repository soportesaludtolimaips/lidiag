<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfigAdminSaludRequest extends FormRequest
{
    /**
     * Autoriza la petición (sin restricciones adicionales).
     *
     * @return bool
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Reglas de validación de administradoras de salud (código, NIT, nombre y estado).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $adminSaludId = $this->route('config.admin.salud')
            ? $this->route('config.admin.salud')->id
            : null;

        return [
            'cod_admin_salud' => [
                'required',
                'string',
                'min:2',
                'max:20',
                Rule::unique('config_admin_salud', 'cod_admin_salud')->ignore($adminSaludId),
            ],
            'nit_admin_salud' => [
                'required',
                'string',
                'min:3',
                'max:20',
                Rule::unique('config_admin_salud', 'nit_admin_salud')->ignore($adminSaludId),
            ],
            'nom_admin_salud' => [
                'required',
                'string',
                'min:5',
                'max:150',
            ],
            'estado' => 'required|boolean',
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
            'cod_admin_salud.required' => 'El código de la administradora de salud es obligatorio.',
            'cod_admin_salud.min' => 'El código debe tener mínimo 2 caracteres.',
            'cod_admin_salud.max' => 'El código debe tener máximo 20 caracteres.',
            'cod_admin_salud.unique' => 'Ya existe una administradora de salud con ese código.',
            'nit_admin_salud.required' => 'El NIT de la administradora de salud es obligatorio.',
            'nit_admin_salud.min' => 'El NIT debe tener mínimo 3 caracteres.',
            'nit_admin_salud.max' => 'El NIT debe tener máximo 20 caracteres.',
            'nit_admin_salud.unique' => 'Ya existe una administradora de salud con ese NIT.',
            'nom_admin_salud.required' => 'El nombre de la administradora de salud es obligatorio.',
            'nom_admin_salud.min' => 'El nombre debe tener mínimo 5 caracteres.',
            'nom_admin_salud.max' => 'El nombre debe tener máximo 150 caracteres.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.boolean' => 'El estado debe ser verdadero o falso.',
        ];
    }
}
