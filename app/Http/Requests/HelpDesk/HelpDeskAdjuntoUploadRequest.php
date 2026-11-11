<?php

namespace App\Http\Requests\HelpDesk;

use Illuminate\Foundation\Http\FormRequest;

class HelpDeskAdjuntoUploadRequest extends FormRequest
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
     * Reglas de validación para adjuntar archivos a un ticket.
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
            'comentario' => 'nullable|string|max:10000',
            'files' => 'required|array|max:10',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
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
            'files.required' => 'Debes adjuntar al menos un archivo',
            'files.*.file' => 'El archivo no es válido',
            'files.*.max' => 'El archivo no debe exceder 10MB',
            'files.*.mimes' => 'El tipo de archivo no está permitido',
        ];
    }
}
