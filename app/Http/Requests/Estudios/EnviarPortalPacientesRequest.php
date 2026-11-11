<?php

namespace App\Http\Requests\Estudios;

use App\Rules\SinCaracteresDeRuta;
use Illuminate\Foundation\Http\FormRequest;

class EnviarPortalPacientesRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para esta solicitud.
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
     * Reglas de validación para enviar un estudio al portal de pacientes.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer',
            // Forman parte del nombre de los archivos del informe: sin barras ni «..» de ruta (ver SinCaracteresDeRuta).
            'num_docu' => ['required', 'string', 'max:50', new SinCaracteresDeRuta],
            'nom_pacien' => ['required', 'string', 'max:255', new SinCaracteresDeRuta],
            'lectura' => 'required|string',
            'estudio_producto_id' => 'required|integer',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'fec_naci' => 'nullable|string|max:50',
            'nom_sede' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'fechor_lectura' => 'nullable|date',
        ];
    }
}
