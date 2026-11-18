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
     * Prepara los datos antes de la validación.
     * Si el ID no viene en la raíz de la petición (FormData), se extrae del JSON 'registro'.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-18
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('id') && $this->filled('registro')) {
            $data = is_array($this->input('registro'))
                ? $this->input('registro')
                : json_decode($this->input('registro'), true);

            if (! empty($data['id'])) {
                $this->merge(['id' => (int) $data['id']]);
            }
        }
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
            'plantilla_rx' => 'nullable|file|max:10240',
            'plantilla_ecografia' => 'nullable|file|max:10240',
            'plantilla_rx_ecografia' => 'nullable|file|max:10240',
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
