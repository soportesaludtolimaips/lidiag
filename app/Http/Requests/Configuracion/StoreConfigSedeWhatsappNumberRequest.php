<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class StoreConfigSedeWhatsappNumberRequest extends FormRequest
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
     * Reglas de validación para registrar números de WhatsApp de sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'phone_number_id' => 'nullable|string|max:255',
            'numero' => 'required|string|max:50|unique:config_sede_whatsapp_numbers,numero',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ];
    }
}
