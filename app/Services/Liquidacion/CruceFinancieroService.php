<?php

namespace App\Services\Liquidacion;

use App\Services\BaseService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

/**
 * Servicio de Cruce Financiero entre Producción Asistencial y Contratación.
 *
 * Determina las tarifas pactadas con la Entidad contratante y los honorarios
 * del Médico Lector para cada estudio producido, evaluando la vigencia por sede,
 * la categoría del producto y la prioridad de la atención médica (Urgencias vs Consulta).
 *
 * Flujo del proceso:
 * 1. Recibe los parámetros de consulta (periodo, sede, usuario asignador y médico).
 * 2. Realiza uniones indexadas (LEFT JOIN) con contratos vigentes de la sede.
 * 3. Cruza el detalle de tarifas de la entidad (contratos_productos) por producto CUPS.
 * 4. Cruza el desglose de tarifas del médico lector (contratos_medicos) priorizando urgencias o consulta.
 * 5. Genera métricas financieras directas (valor entidad, valor médico, margen bruto) y trazabilidad.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-10
 */
class CruceFinancieroService extends BaseService
{
    /**
     * Construye y devuelve el Query Builder optimizado para la producción detallada valorada.
     *
     * @param  string  $fechaInicio  Fecha de inicio en formato Y-m-d.
     * @param  string  $fechaFin  Fecha de fin en formato Y-m-d.
     * @param  int|null  $sedeId  Identificador de la sede hospitalaria o null para todas.
     * @param  int|null  $quienAsignoId  Identificador del usuario que registró o asignó.
     * @param  int|null  $medicoId  Identificador del médico radiólogo o null para todos.
     *
     * @returns Builder Constructor de consulta SQL listo para ejecución o streaming.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function obtenerQueryProduccionValorada(
        string $fechaInicio,
        string $fechaFin,
        ?int $sedeId = null,
        ?int $quienAsignoId = null,
        ?int $medicoId = null
    ): Builder {
        return DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->join("config_prioridades", "estudios.prioridad_id", "=", "config_prioridades.id")
            ->join("users as registro", "estudios.quien_registro_id", "=", "registro.id")
            ->join("users as medico", "medico.id", "=", "estudios.medico_id")
            ->join("pacientes", "estudios.paciente_id", "=", "pacientes.id")
            ->leftJoin("users as transcrip", "transcrip.id", "=", "estudios_productos.transcriptor_id")
            // 1. Cruce con Contratos vigentes de la sede para la fecha de corte
            ->leftJoin("contratos as c", function ($join) {
                $join->on("estudios.sede_id", "=", "c.sede_id")
                    ->where("c.estado", "=", 1)
                    ->whereRaw("DATE(COALESCE(estudios_productos.fechor_lectura, estudios.fec_estudio, estudios_productos.created_at)) BETWEEN c.fecha_inicio AND c.fecha_fin");
            })
            // 2. Cruce con Productos contratados a nivel de Entidad
            ->leftJoin("contratos_productos as cp", function ($join) {
                $join->on("c.id", "=", "cp.contrato_id")
                    ->on("estudios_productos.producto_id", "=", "cp.producto_id")
                    ->where("cp.estado", "=", 1);
            })
            // 3. Cruce con Tarifas específicas asignadas al Médico Lector
            ->leftJoin("contratos_medicos as cm", function ($join) {
                $join->on("c.id", "=", "cm.contrato_id")
                    ->on("cp.id", "=", "cm.contratos_productos_id")
                    ->on("estudios.medico_id", "=", "cm.medico_id")
                    ->where("cm.estado", "=", 1);
            })
            ->whereBetween("estudios_productos.fechor_lectura", [
                $fechaInicio." 00:00:00",
                $fechaFin." 23:59:59",
            ])
            ->when($sedeId, fn ($q) => $q->where("estudios.sede_id", $sedeId))
            ->when($quienAsignoId, fn ($q) => $q->where("estudios.quien_registro_id", $quienAsignoId))
            ->when($medicoId, fn ($q) => $q->where("estudios.medico_id", $medicoId))
            ->where("estudios_productos.activo", "=", 1)
            ->where("estudios.estado", "=", 1)
            ->select(
                "estudios.id",
                "config_sedes.nom_sede",
                "estudios.fec_estudio",
                "estudios_productos.created_at",
                "estudios_productos.fechor_lectura",
                "estudios.atencion",
                "config_prioridades.nom_priori",
                "registro.name as quien_registro",
                "medico.name as medico",
                "pacientes.num_docu",
                "pacientes.nombres",
                "estudios_productos.cod_cups",
                "estudios_productos.nom_produc",
                "estudios.mods_in_study",
                "transcrip.name as transcriptor",
                "estudios.eps",
                "estudios.contrato",
                "estudios.plan",
                DB::raw("(SELECT GROUP_CONCAT('- ', nom_diagnos SEPARATOR '\n')
                            FROM estudios_diagnosticos
                            WHERE estudio_id = estudios.id) as diagnosticos"),
                "estudios_productos.lectura",
                "c.id as contrato_lidiag_id",

                // Métrica 1: Valor Contratado con la Entidad (Tarifa cliente)
                DB::raw("COALESCE(cp.valor_contrato, 0.00) as valor_entidad"),

                // Métrica 2: Valor Contratado con el Médico Lector (Tarifa médico según prioridad)
                DB::raw("CASE 
                    WHEN estudios.medico_id IS NULL THEN 0.00
                    WHEN config_prioridades.nom_priori = 'Alta' THEN COALESCE(cm.valor_urgencias, cm.valor_contrato, 0.00)
                    ELSE COALESCE(cm.valor_consulta, cm.valor_contrato, 0.00)
                END as valor_medico"),

                // Margen Bruto = Valor Entidad - Valor Médico
                DB::raw("(COALESCE(cp.valor_contrato, 0.00) - CASE 
                    WHEN estudios.medico_id IS NULL THEN 0.00
                    WHEN config_prioridades.nom_priori = 'Alta' THEN COALESCE(cm.valor_urgencias, cm.valor_contrato, 0.00)
                    ELSE COALESCE(cm.valor_consulta, cm.valor_contrato, 0.00)
                END) as margen_bruto"),

                // Tipo de tarifa aplicada al médico
                DB::raw("CASE 
                    WHEN estudios.medico_id IS NULL THEN 'Sin Asignar'
                    WHEN config_prioridades.nom_priori = 'Alta' AND cm.valor_urgencias IS NOT NULL THEN 'Urgencias'
                    WHEN config_prioridades.nom_priori <> 'Alta' AND cm.valor_consulta IS NOT NULL THEN 'Consulta'
                    WHEN cm.valor_contrato IS NOT NULL THEN 'Base'
                    ELSE 'Sin Tarifa'
                END as tarifa_tipo"),

                // Estado de auditoría y trazabilidad
                DB::raw("CASE 
                    WHEN c.id IS NULL THEN 'Sin Contrato Sede'
                    WHEN cp.id IS NULL THEN 'Producto No Contratado'
                    WHEN estudios.medico_id IS NOT NULL AND cm.id IS NULL THEN 'Médico Sin Tarifa'
                    ELSE 'Tarificado'
                END as estado_tarifa")
            )
            ->orderBy("estudios_productos.fechor_lectura", "desc");
    }

    /**
     * Consulta y retorna la colección completa de estudios valorados.
     *
     * @returns Collection Colección de registros valorados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function consultarProduccionValorada(
        string $fechaInicio,
        string $fechaFin,
        ?int $sedeId = null,
        ?int $quienAsignoId = null,
        ?int $medicoId = null
    ): Collection {
        return $this->obtenerQueryProduccionValorada($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId)
            ->get();
    }

    /**
     * Genera una colección Lazy/Streaming para consumo de bajo consumo de memoria.
     *
     * @returns LazyCollection
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function cursorProduccionValorada(
        string $fechaInicio,
        string $fechaFin,
        ?int $sedeId = null,
        ?int $quienAsignoId = null,
        ?int $medicoId = null
    ): LazyCollection {
        return $this->obtenerQueryProduccionValorada($fechaInicio, $fechaFin, $sedeId, $quienAsignoId, $medicoId)
            ->cursor();
    }
}
