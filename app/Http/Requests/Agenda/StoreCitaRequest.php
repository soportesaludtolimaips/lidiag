<?php

namespace App\Http\Requests\Agenda;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para validar los datos de creación de una solicitud de cita médica.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-14
 */
class StoreCitaRequest extends FormRequest
{
    /**
     * Determina si el usuario tiene permiso para realizar esta petición.
     *
     * @returns bool siempre retorna true.
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
     * Define las reglas de validación para la creación de una solicitud de cita.
     *
     * @returns array reglas de validación: servicio_cita_id, telefono, num_docu, nombres, etc.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function rules(): array
    {
        return [
            'servicio_cita_id' => 'required|exists:config_servicio_citas,id',
            'numero_orden' => 'nullable|string|max:50',
            'numero_autorizacion' => 'nullable|string|max:50',
            'telefono' => 'required|string',
            'num_docu' => 'nullable|string',
            'nombres' => 'nullable|string',
            'direccion' => 'nullable|string',
            'email' => 'nullable|email',
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function messages(): array
    {
        return [
            'servicio_cita_id.required' => 'El servicio de cita es obligatorio.',
            'servicio_cita_id.exists' => 'El servicio de cita seleccionado no existe.',
            'telefono.required' => 'El teléfono es obligatorio.',
            'email.email' => 'El correo electrónico no es válido.',
        ];
    }
}
