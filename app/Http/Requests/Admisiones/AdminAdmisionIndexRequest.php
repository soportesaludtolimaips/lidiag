<?php

namespace App\Http\Requests\Admisiones;

use App\Models\Configuracion\ConfigSede;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Solicitud validada para consulta y filtrado de registros en admin_admisiones y Pendientes de asignar.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionIndexRequest extends FormRequest
{
    /**
     * Autoriza la petición delegando el control de permisos al middleware de ruta/controlador.
     *
     * Flujo del proceso:
     * 1. Retorna true permitiendo continuar hacia la validación de reglas.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza la sede activa desde sesión o usuario autenticado cuando no se envía explícitamente.
     *
     * Flujo del proceso:
     * 1. Si sede_id viene vacío, resuelve la sede en sesión o primera sede asignada al usuario.
     * 2. Fusiona el sede_id resuelto en los datos del request antes de validar.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    protected function prepareForValidation(): void
    {
        if (empty($this->sede_id)) {
            $sedeId = session("sede_id")
                ?? auth()->user()?->sedes()?->first()?->id
                ?? ConfigSede::where("estado", true)->value("id");

            if ($sedeId) {
                $this->merge(["sede_id" => (int) $sedeId]);
            }
        }
    }

    /**
     * Define las reglas de validación para los criterios de filtrado de admisiones.
     *
     * Flujo del proceso:
     * 1. Valida existencia de la sede asistencial.
     * 2. Declara explícitamente filtros opcionales de documento/nombre, estado, modalidad y rango de fechas.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function rules(): array
    {
        return [
            "sede_id" => "required|integer|exists:config_sedes,id",
            "estado" => "nullable|string|max:40",
            "bus_nom_num_docu" => "nullable|string|max:120",
            "bus_fech_ini" => "nullable|date",
            "bus_fecha_fin" => "nullable|date|after_or_equal:bus_fech_ini",
            "modality" => "nullable|string|max:15",
            "reconciliar" => "sometimes|boolean",
        ];
    }

    /**
     * Mensajes descriptivos en español para los errores de validación.
     *
     * Flujo del proceso:
     * 1. Retorna el mapa de mensajes personalizados por regla.
     *
     * @return array<string, string>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function messages(): array
    {
        return [
            "sede_id.required" => "Debes seleccionar una sede asistencial.",
            "sede_id.exists" => "La sede seleccionada no existe en el sistema.",
            "bus_fecha_fin.after_or_equal" => "La fecha final no puede ser menor a la fecha inicial.",
        ];
    }
}
