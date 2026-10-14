<?php

namespace App\Services\Agenda;

use App\Models\Agenda\AgendaCita;
use App\Models\Agenda\AgendaDisponibilidaDetalle;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigServicio;
use App\Services\BaseService;
use Carbon\Carbon;

/**
 * Servicio especializado en consultas y lecturas del módulo de citas y agendamiento.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-13
 */
class AgendaCitaQueryService extends BaseService
{
    /**
     * Obtiene el listado de citas aplicando filtros de búsqueda y paginación.
     *
     * Flujo del proceso:
     * 1. Carga la relación con paciente, servicio, producto, sede y usuario registrador.
     * 2. Aplica filtros opcionales de estado, rango de fechas, servicio, sede y búsqueda de paciente.
     * 3. Ordena descendentemente por fecha de creación y retorna resultados paginados.
     *
     * @param  array  $filters  criterios de filtrado y paginación.
     *
     * @returns array resultado con la colección paginada de citas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function all(array $filters): array
    {
        $query = AgendaCita::with(["paciente", "servicio", "producto", "sede", "quienRegistra"]);

        if (! empty($filters["estado"])) {
            $query->where("estado", $filters["estado"]);
        }

        if (! empty($filters["fecha_inicio"]) && ! empty($filters["fecha_fin"])) {
            $query->whereBetween("fecha_hora_inicio", [
                $filters["fecha_inicio"]." 00:00:00",
                $filters["fecha_fin"]." 23:59:59",
            ]);
        }

        if (! empty($filters["servicio_id"])) {
            $query->where("servicio_id", $filters["servicio_id"]);
        }

        if (! empty($filters["sede_id"])) {
            $query->where("sede_id", $filters["sede_id"]);
        }

        if (! empty($filters["busqueda"])) {
            $busqueda = $this->sanitizeLike($filters["busqueda"]);
            $query->whereHas("paciente", function ($q) use ($busqueda) {
                $q->where("num_docu", "like", "%".$busqueda."%")
                    ->orWhere("nombres", "like", "%".$busqueda."%")
                    ->orWhere("pri_ape", "like", "%".$busqueda."%");
            });
        }

        $perPage = (int) ($filters["per_page"] ?? 20);
        $citas = $query->orderByDesc("created_at")->paginate($perPage);

        return $this->success($citas);
    }

    /**
     * Obtiene las citas en estado solicitada que están pendientes de revisión.
     *
     * Flujo del proceso:
     * 1. Filtra citas con estado 'solicitada'.
     * 2. Aplica filtros opcionales por servicio, sede y búsqueda de paciente.
     * 3. Retorna la colección ordenada por fecha de creación descendente.
     *
     * @param  array  $filters  criterios de filtrado (servicio_id, sede_id, busqueda).
     *
     * @returns array resultado con las citas pendientes.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function pendientes(array $filters): array
    {
        $query = AgendaCita::with(["paciente", "servicio", "producto", "sede", "quienRegistra", "rechazoMotivo"])
            ->where("estado", "solicitada");

        if (! empty($filters["servicio_id"])) {
            $query->where("servicio_id", $filters["servicio_id"]);
        }

        if (! empty($filters["sede_id"])) {
            $query->where("sede_id", $filters["sede_id"]);
        }

        if (! empty($filters["busqueda"])) {
            $busqueda = $this->sanitizeLike($filters["busqueda"]);
            $query->whereHas("paciente", function ($q) use ($busqueda) {
                $q->where("num_docu", "like", "%".$busqueda."%")
                    ->orWhere("nombres", "like", "%".$busqueda."%")
                    ->orWhere("pri_ape", "like", "%".$busqueda."%");
            });
        }

        $citas = $query->orderByDesc("created_at")->get();

        return $this->success($citas);
    }

    /**
     * Consulta los slots disponibles para una fecha, sede y servicio específicos.
     *
     * Flujo del proceso:
     * 1. Consulta slots asociados a agendas activas en la sede y servicio dados.
     * 2. Filtra por la fecha solicitada y estado 'Disponible'.
     * 3. Mapea la información con formato legible para la interfaz de usuario.
     *
     * @param  array  $data  parámetros: fecha, sede_id, servicio_id.
     *
     * @returns array resultado con la lista de slots disponibles.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function disponibilidad(array $data): array
    {
        $fecha = Carbon::parse($data["fecha"]);

        $detalles = AgendaDisponibilidaDetalle::query()
            ->whereHas("agendaDisponibilida", function ($q) use ($data) {
                $q->where("sede_id", $data["sede_id"])
                    ->where("servicio_id", $data["servicio_id"])
                    ->where("estado", "Activo");
            })
            ->whereDate("fecha_hora_inicio", $fecha)
            ->where("estado", "Disponible")
            ->orderBy("fecha_hora_inicio")
            ->get(["id", "fecha_hora_inicio", "fecha_hora_fin", "estado"]);

        return $this->success([
            "fecha" => $data["fecha"],
            "detalles" => $detalles->map(function ($detalle) {
                return [
                    "id" => $detalle->id,
                    "inicio" => $detalle->fecha_hora_inicio->format("Y-m-d H:i"),
                    "fin" => $detalle->fecha_hora_fin->format("Y-m-d H:i"),
                    "disponible" => $detalle->estado === "Disponible",
                ];
            }),
        ]);
    }

    /**
     * Obtiene el listado de servicios de cita que se encuentran activos.
     *
     * @returns array resultado con los servicios activos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function servicios(): array
    {
        $servicios = ConfigServicio::where("estado", true)->get();

        return $this->success($servicios);
    }

    /**
     * Obtiene los productos activos asociados a un servicio de cita, con filtro opcional por sede.
     *
     * @param  int  $servicioCitaId  identificador del servicio.
     * @param  string|null  $sedeId  identificador opcional de la sede.
     *
     * @returns array resultado con la colección de productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function productosPorServicio(int $servicioCitaId, ?string $sedeId = null): array
    {
        $servicio = ConfigServicio::with(["productos" => function ($query) {
            $query->where("config_productos.estado", true);
        }])->findOrFail($servicioCitaId);

        if ($sedeId && (string) $servicio->sede_id !== (string) $sedeId) {
            return $this->success([]);
        }

        return $this->success($servicio->productos);
    }

    /**
     * Obtiene la información de una cita junto con su conversación de WhatsApp registrada.
     *
     * @param  AgendaCita  $cita  modelo de la cita.
     *
     * @returns array resultado con la cita y la conversación asociada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function verConversacion(AgendaCita $cita): array
    {
        $conversacion = null;

        if ($cita->paciente && $cita->paciente->cel) {
            $cel = preg_replace("/[^0-9]/", "", $cita->paciente->cel);
            $conversacion = WhatsAppConversation::whereRaw(
                "REPLACE(REPLACE(REPLACE(telefono, ' ', ''), '-', ''), '+', '') LIKE ?",
                ["%{$cel}"]
            )->first();
        }

        return $this->success([
            "cita" => $cita->load(["paciente", "servicio", "producto", "sede"]),
            "conversacion" => $conversacion,
        ]);
    }

    /**
     * Escapa caracteres especiales de comodín SQL para cláusulas LIKE.
     *
     * @param  string  $value  valor a sanitizar.
     *
     * @returns string valor con caracteres de comodín escapados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function sanitizeLike(string $value): string
    {
        return str_replace(["%", "_"], ["\\%", "\\_"], $value);
    }
}
