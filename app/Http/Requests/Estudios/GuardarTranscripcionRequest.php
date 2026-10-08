<?php

namespace App\Http\Requests\Estudios;

use Illuminate\Foundation\Http\FormRequest;

class GuardarTranscripcionRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para guardar la transcripción de un estudio.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function rules(): array
    {
        return [
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'id_estudio' => 'nullable|integer',
            'lectura' => 'required|string|min:3',
            'num_docu' => 'required|string|max:50',
            'nom_pacien' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'tel' => 'nullable|string|max:50',
            'sexo' => 'nullable|string|max:10',
            'direccion' => 'nullable|string|max:500',
            'fec_naci' => 'nullable|string|max:50',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'cod_cups' => 'required|string|max:50',
            'nom_produc' => 'required|string|max:255',
            'medico_id' => 'nullable|integer',
            'nom_sede' => 'nullable|string|max:255',
            'email_reportar' => 'nullable|email|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'destino_estudio' => 'nullable|string|max:255',
            'fechor_lectura' => 'nullable|date',
            'fec_lectura' => 'nullable|date',
        ];
    }

    /**
     * Mensajes de validación personalizados.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function messages(): array
    {
        return [
            'lectura.required' => 'Debe ingresar la lectura.',
            'lectura.min' => 'La lectura debe tener al menos 3 caracteres.',
        ];
    }
}
