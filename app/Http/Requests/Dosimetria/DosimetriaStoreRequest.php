<?php

namespace App\Http\Requests\Dosimetria;

use Illuminate\Foundation\Http\FormRequest;

class DosimetriaStoreRequest extends FormRequest
{
    /**
     * Determina si el usuario o servicio está autorizado para realizar la solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para la recepción en lote (bulk) de telemetría de dosimetría.
     *
     * Flujo del proceso:
     * 1. Valida que el sede_id exista en la tabla config_sedes.
     * 2. Exige un arreglo de registros con al menos un elemento.
     * 3. Valida estrictamente cada estudio: study_uid, patient_id_hashed, modalidad, tipo_metrica y valor_dosis.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function rules(): array
    {
        return [
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'registros' => 'required|array|min:1',
            'registros.*.study_uid' => 'required|string|max:128',
            'registros.*.patient_id_hashed' => 'required|string|max:64',
            'registros.*.modalidad' => 'required|string|in:CT,MG,DX,CR',
            'registros.*.aet_title' => 'nullable|string|max:50',
            'registros.*.tipo_metrica' => 'required|string|in:CTDIvol,DLP,DGM,DAP',
            'registros.*.valor_dosis' => 'required|numeric|min:0',
            'registros.*.unidad_medida' => 'sometimes|nullable|string|max:20',
            'registros.*.fecha_estudio' => 'required|date',
            'registros.*.accession_no' => 'sometimes|nullable|string|max:50',
            'registros.*.study_desc' => 'sometimes|nullable|string|max:255',
        ];
    }

    /**
     * Mensajes descriptivos de validación.
     */
    public function messages(): array
    {
        return [
            'sede_id.required' => 'El identificador de la sede es obligatorio.',
            'sede_id.exists' => 'La sede especificada no existe en el sistema LIDiag.',
            'registros.required' => 'El lote de registros de dosimetría es obligatorio.',
            'registros.min' => 'El lote debe contener al menos un registro de dosimetría.',
            'registros.*.study_uid.required' => 'El Study Instance UID del estudio es obligatorio.',
            'registros.*.patient_id_hashed.required' => 'El identificador anonimizado del paciente es obligatorio.',
            'registros.*.tipo_metrica.in' => 'El tipo de métrica debe ser uno de: CTDIvol, DLP, DGM, DAP.',
        ];
    }
}
