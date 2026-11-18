<?php

namespace App\Http\Requests\Ecografias;

use App\Models\Configuracion\ConfigTipoRecaudo;
use Illuminate\Foundation\Http\FormRequest;

class EcografiaStoreRequest extends FormRequest
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
     * Reglas de validación para registrar una ecografía.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        $tipoRecaudoId = $this->input('tipo_recaudo_id');
        $tipoRecaudo = $tipoRecaudoId ? ConfigTipoRecaudo::find($tipoRecaudoId) : null;
        $requiereAtencion = ! $tipoRecaudo || (bool) $tipoRecaudo->envia_sahi;

        return [
            'num_docu' => 'required|string',
            'nombres' => 'required|string',
            'seg_nom' => 'sometimes|nullable|string',
            'pri_ape' => 'nullable|string',
            'seg_ape' => 'sometimes|nullable|string',
            'sexo' => 'sometimes|nullable|string',
            'fec_naci' => 'sometimes|nullable|string',
            'direccion' => 'sometimes|nullable|string',
            'tel' => 'sometimes|nullable|string',
            'cel' => 'sometimes|nullable|string',
            'email' => 'sometimes|nullable|string',
            'sede_id' => 'required|integer',
            'atencion_id' => $requiereAtencion ? 'required|string|max:20' : 'sometimes|nullable|string|max:20',
            'medico_id' => 'required|integer',
            'tipo_recaudo_id' => 'sometimes|nullable|integer|exists:config_tipos_recaudo,id',
            'productosAgenda' => 'required|array',
            'contrato' => 'sometimes|nullable|string',
            'plan' => 'sometimes|nullable|string',
            'admin_salud' => 'sometimes|nullable|string',
            'quien_ordeno' => 'sometimes|nullable|string',
            'ubicacion' => 'sometimes|nullable|string|max:150',
            'cama' => 'sometimes|nullable|string|max:50',
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
            'num_docu.required' => 'El número de documento es obligatorio.',
            'nombres.required' => 'El nombre es obligatorio.',
            'sede_id.required' => 'La sede es obligatoria.',
            'atencion_id.required' => 'La atención es obligatoria.',
            'medico_id.required' => 'El médico es obligatorio.',
            'productosAgenda.required' => 'Debe agregar al menos un producto a la ecografía.',
        ];
    }
}
