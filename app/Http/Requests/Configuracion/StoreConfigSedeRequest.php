<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigSedeRequest extends FormRequest
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
     * Reglas de validación para registrar sedes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'registro' => 'required|json',
            'plantilla_rx' => 'nullable|file|max:2048',
            'plantilla_rx_ecografia' => 'nullable|file|max:2048',
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
