<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Foundation\Http\FormRequest;

class ServicioProductoNoVinculadosRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
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
     * Reglas de validación para listar productos no vinculados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function rules(): array
    {
        return [
            "sede_id" => ["required", "integer"],
            "servicio_id" => ["nullable", "integer"],
        ];
    }
}
