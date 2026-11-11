<?php

namespace App\Http\Requests\Dcm4chee;

use Illuminate\Foundation\Http\FormRequest;

class StudyListRequest extends FormRequest
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
     * Reglas de validación para la lista de estudios de Dcm4chee.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_nom_num_docu' => 'nullable|string|max:30',
            'bus_fech_ini' => 'nullable|date|required_with:bus_fecha_fin',
            'bus_fecha_fin' => 'nullable|date|required_with:bus_fech_ini|after_or_equal:bus_fech_ini',
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
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.integer' => 'La sede debe ser un número entero.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
            'bus_nom_num_docu.string' => 'El nombre o número de documento debe ser una cadena de texto.',
            'bus_nom_num_docu.max' => 'El nombre o número de documento debe tener menos de 30 caracteres.',
            'bus_fech_ini.date' => 'La fecha de inicio debe ser una fecha válida.',
            'bus_fech_ini.required_with' => 'La fecha de inicio es obligatoria cuando se ingresa una fecha de fin.',
            'bus_fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'bus_fecha_fin.required_with' => 'La fecha de fin es obligatoria cuando se ingresa una fecha de inicio.',
            'bus_fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    /**
     * Atributos en español para los campos validados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function attributes(): array
    {
        return [
            'sede_id' => 'sede',
            'bus_nom_num_docu' => 'nombre o número de documento',
            'bus_fech_ini' => 'fecha de inicio',
            'bus_fecha_fin' => 'fecha de fin',
        ];
    }
}
