<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class EnviarEstudioTranscripcionRequest extends FormRequest
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
     * Reglas de validación para enviar un estudio a transcripción.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules()
    {
        return [
            'idAtencion' => ['required', 'integer'],
            'idMedico' => ['required', 'integer'],
            'reporteEstudio' => ['required', 'string'],
            'codCups' => ['required', 'string', 'max:20'],
            'fecHorLectura' => ['required', 'string', 'date_format:Y-m-d\TH:i:s'],
        ];
    }

    /**
     * Mensajes en español para las reglas de validación.
     *
     * @return array
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function messages()
    {
        return [
            'idAtencion.required' => 'El ID de atención es obligatorio.',
            'idAtencion.integer' => 'El ID de atención debe ser un número entero.',
            'idMedico.required' => 'El ID del médico es obligatorio.',
            'idMedico.integer' => 'El ID del médico debe ser un número entero.',
            'reporteEstudio.required' => 'El reporte del estudio es obligatorio.',
            'reporteEstudio.string' => 'El reporte del estudio debe ser un texto.',
            'codCups.required' => 'El código CUPS es obligatorio.',
            'codCups.string' => 'El código CUPS debe ser un texto.',
            'codCups.max' => 'El código CUPS no debe exceder los 20 caracteres.',
            'fecHorLectura.required' => 'La fecha y hora de lectura es obligatoria.',
            'fecHorLectura.date_format' => 'La fecha y hora de lectura debe tener el formato Y-m-d\TH:i:s.',
        ];
    }
}
