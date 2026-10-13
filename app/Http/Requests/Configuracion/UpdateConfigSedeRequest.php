<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfigSedeRequest extends FormRequest
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
     * Reglas de validación para actualizar sedes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'registro' => 'required|json',
            'id' => 'required|integer|exists:config_sedes,id',
            'plantilla_rx' => 'nullable|file|max:2048',
            'plantilla_rx_ecografia' => 'nullable|file|max:2048',
        ];
    }

    /**
     * Decodifica el campo registro (JSON) y agrega el ID de la sede al arreglo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function decoded(): array
    {
        $data = json_decode($this->input('registro'), true) ?? [];
        $data['id'] = (int) $this->input('id');

        return $data;
    }
}
