<?php

namespace App\Http\Requests\Interfaces;

use Illuminate\Foundation\Http\FormRequest;

class EnviarOrdenTeleRadiologiaRequest extends FormRequest
{
    /**
     * Determina si el usuario puede autorizar la solicitud.
     *
     * @returns bool Siempre retorna true.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepara y normaliza los datos de la orden antes de su validación.
     *
     * Flujo del proceso:
     * 1. Si no viene study_id explícito, utiliza study_pk para evitar nulos.
     * 2. Si no viene cod_cups directo pero sí el arreglo producto, toma el primer código CUPS.
     * 3. Si no viene medico remitente, asigna los datos del usuario autenticado en sesión.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    protected function prepareForValidation(): void
    {
        $orden = $this->input('orden');
        if (is_array($orden)) {
            if (empty($orden['study_id']) && ! empty($orden['study_pk'])) {
                $orden['study_id'] = (string) $orden['study_pk'];
            }

            if (empty($orden['cod_cups']) && ! empty($orden['producto']) && is_array($orden['producto'])) {
                $orden['cod_cups'] = (string) ($orden['producto'][0]['cod_cups'] ?? '');
            }

            if (empty($orden['medico']) || ! is_array($orden['medico'])) {
                $user = auth()->user();
                $orden['medico'] = [
                    'num_docu' => (string) ($user ? ($user->num_docu ?? $user->id) : '0'),
                    'name' => (string) ($user ? $user->name : 'Usuario Sistema'),
                ];
            }

            $this->merge(['orden' => $orden]);
        }
    }

    /**
     * Define las reglas de validación para enviar orden de tele-radiología.
     *
     * @returns array<string, array> Reglas de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function rules()
    {
        return [
            'orden' => ['required', 'array'],
            'orden.study_pk' => ['required'],
            'orden.study_iuid' => ['required', 'string'],
            'orden.study_id' => ['nullable'],
            'orden.paciente.num_docu' => ['required', 'string'],
            'orden.paciente.nom_pacien' => ['nullable', 'string'],
            'orden.medico' => ['nullable', 'array'],
            'orden.medico.num_docu' => ['nullable', 'string'],
            'orden.medico.name' => ['nullable', 'string'],
            'orden.cod_cups' => ['nullable', 'string'],
            'orden.producto' => ['sometimes', 'array'],
            'orden.priori' => ['sometimes', 'array'],
            'orden.sede' => ['sometimes', 'array'],
        ];
    }

    /**
     * Define los mensajes de error personalizados para las reglas de validación.
     *
     * @returns array<string, string> Mensajes de error en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function messages()
    {
        return [
            'orden.required' => 'No se recibieron datos de la orden.',
            'orden.array' => 'La orden debe ser un arreglo.',
            'orden.study_pk.required' => 'El study_pk es obligatorio.',
            'orden.study_iuid.required' => 'El study_iuid es obligatorio.',
            'orden.study_iuid.string' => 'El study_iuid debe ser texto.',
            'orden.paciente.num_docu.required' => 'El documento del paciente es obligatorio.',
        ];
    }
}
