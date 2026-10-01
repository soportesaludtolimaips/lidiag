<?php

namespace App\Services\Liquidacion;

use App\Models\Configuracion\ConfigPrioridad;
use App\Models\Contratacion\Contrato;
use App\Models\Estudio\EstudioProducto;
use App\Models\Liquidacion\LiquidacionLinea;
use App\Services\BaseService;
use DateTimeInterface;

/**
 * Calcula la liquidación de pagos a médicos por sede y rango de fechas.
 *
 * Une cada lectura con el contrato vigente de su sede en la fecha de lectura
 * y valora con la tarifa del detalle. Lo que no liquida cae en exclusiones
 * visibles con conteo, nunca en silencio.
 *
 * Flujo del proceso:
 * 1. Carga las lecturas del periodo (con fecha de lectura, de la sede).
 * 2. Precarga contratos de la sede que tocan el rango y el detalle activo.
 * 3. Clasifica cada lectura: línea liquidable o categoría de exclusión.
 * 4. Agrega totales por médico y gran total.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
class LiquidacionService extends BaseService
{
    /**
     * Calcula líneas, exclusiones y totales de un periodo.
     *
     * Flujo del proceso:
     * 1. Trae estudios_productos con fechor_lectura en el rango y su estudio.
     * 2. Resuelve prioridades de urgencias (nom_priori = 'Alta').
     * 3. Por cada lectura: valida asignado, activo, contrato, producto y médico.
     * 4. Valora con valor_urgencias (Alta) o valor_consulta (resto).
     * 5. Agrega por médico.
     *
     * @param  int  $sedeId  Sede a liquidar.
     * @param  string  $fechaInicio  Inicio del periodo en Y-m-d.
     * @param  string  $fechaFin  Fin del periodo en Y-m-d.
     * @param  int|null  $contratoId  Si se indica, vista acotada a ese contrato
     *                                (debe ser de la sede; lecturas fuera de su
     *                                rango caen en sin_contrato).
     * @param  int|null  $medicoId  Si se indica, solo lecturas de ese médico.
     *
     * @returns array Líneas, exclusiones con conteo y totales por médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcular(int $sedeId, string $fechaInicio, string $fechaFin, ?int $contratoId = null, ?int $medicoId = null): array
    {
        $urgentes = ConfigPrioridad::where("nom_priori", "Alta")->pluck("id")->toArray();

        $lecturas = EstudioProducto::query()
            ->join("estudios as e", "e.id", "=", "estudios_productos.estudio_id")
            ->where("e.sede_id", $sedeId)
            ->whereDate("estudios_productos.fechor_lectura", ">=", $fechaInicio)
            ->whereDate("estudios_productos.fechor_lectura", "<=", $fechaFin)
            ->when($medicoId !== null, fn ($q) => $q->where("e.medico_id", $medicoId))
            ->select("estudios_productos.*", "e.medico_id as lector_id", "e.prioridad_id")
            ->with(["producto:id,cod_cups,nom_produc"])
            ->get();

        if ($contratoId !== null) {
            $fijo = Contrato::find($contratoId);

            if (! $fijo || (int) $fijo->sede_id !== (int) $sedeId) {
                return $this->error("El contrato no pertenece a la sede indicada.", 422);
            }

            $contratos = collect([$fijo->load([
                "productos:id,contrato_id,producto_id",
                "medicos" => fn ($q) => $q->where("contratos_medicos.estado", 1),
                "medicos.medico:id,name",
            ])]);
        } else {
            $contratos = Contrato::where("sede_id", $sedeId)
                ->where("fecha_inicio", "<=", $fechaFin)
                ->where("fecha_fin", ">=", $fechaInicio)
                ->with([
                    "productos:id,contrato_id,producto_id",
                    "medicos" => fn ($q) => $q->where("contratos_medicos.estado", 1),
                    "medicos.medico:id,name",
                ])
                ->get();
        }

        $lineas = [];
        $excluidos = ["sin_asignado" => [], "sin_contrato" => [], "producto_sin_tarifa" => [], "medico_fuera_detalle" => [], "anulada" => []];

        foreach ($lecturas as $l) {
            $fecha = substr((string) $l->fechor_lectura, 0, 10);

            if (! $l->activo) {
                $excluidos["anulada"][] = $l->id;

                continue;
            }

            if ($l->lector_id === null) {
                $excluidos["sin_asignado"][] = $l->id;

                continue;
            }

            $contrato = $contratos->first(fn ($c) => $this->fecha($c->fecha_inicio) <= $fecha && $fecha <= $this->fecha($c->fecha_fin));

            if (! $contrato) {
                $excluidos["sin_contrato"][] = $l->id;

                continue;
            }

            $cp = $contrato->productos->firstWhere("producto_id", $l->producto_id);

            if (! $cp) {
                $excluidos["producto_sin_tarifa"][] = $l->id;

                continue;
            }

            $cm = $contrato->medicos->first(fn ($m) => $m->medico_id == $l->lector_id && $m->contratos_productos_id == $cp->id);

            if (! $cm) {
                $excluidos["medico_fuera_detalle"][] = $l->id;

                continue;
            }

            $esUrgencia = in_array((int) $l->prioridad_id, $urgentes, true);
            $valor = $esUrgencia ? (float) $cm->valor_urgencias : (float) $cm->valor_consulta;

            $lineas[] = [
                "medico_id" => (int) $l->lector_id,
                "medico_name" => $cm->medico->name ?? "",
                "producto_id" => (int) $l->producto_id,
                "cod_cups" => $l->producto->cod_cups ?? "",
                "nom_produc" => $l->producto->nom_produc ?? "",
                "clase" => $esUrgencia ? "urgencias" : "consulta",
                "valor" => $valor,
                "fecha_lectura" => $fecha,
                "contrato_id" => $contrato->id,
            ];
        }

        $porMedico = [];

        foreach ($lineas as $linea) {
            $id = $linea["medico_id"];

            if (! isset($porMedico[$id])) {
                $porMedico[$id] = ["medico_id" => $id, "medico_name" => $linea["medico_name"], "total_consulta" => 0, "total_urgencias" => 0, "total" => 0];
            }

            if ($linea["clase"] === "urgencias") {
                $porMedico[$id]["total_urgencias"] += $linea["valor"];
            } else {
                $porMedico[$id]["total_consulta"] += $linea["valor"];
            }

            $porMedico[$id]["total"] += $linea["valor"];
        }

        $exclusiones = [];

        foreach ($excluidos as $categoria => $ids) {
            $exclusiones[] = ["categoria" => $categoria, "conteo" => count($ids), "detalle" => array_values($ids)];
        }

        return $this->success([
            "lineas" => $lineas,
            "exclusiones" => $exclusiones,
            "totales" => ["por_medico" => array_values($porMedico), "gran_total" => array_sum(array_column($porMedico, "total"))],
        ]);
    }

    /**
     * Registra la línea de liquidación congelada de una lectura.
     *
     * Se invoca al guardar una lectura: valora con la tarifa vigente en ese
     * momento y la congela (cambios posteriores del contrato no la afectan).
     * Las lecturas no liquidables también se guardan, con valor null y su
     * categoría de exclusión, para el reporte de productividad. Es idempotente:
     * si la lectura ya tiene línea, no duplica.
     *
     * Flujo del proceso:
     * 1. Si ya existe línea para el producto, no hace nada.
     * 2. Resuelve sede, lector, contrato vigente, producto y médico del detalle.
     * 3. Clasifica la tarifa por prioridad y congela el valor (o la exclusión).
     * 4. Crea la línea con periodo YYYY-MM de la fecha de lectura.
     *
     * @param  EstudioProducto  $lectura  Producto leído con fechor_lectura fijada.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-01
     */
    public function registrarLectura(EstudioProducto $lectura): void
    {
        if (LiquidacionLinea::where("estudio_producto_id", $lectura->id)->exists()) {
            return;
        }

        $estudio = $lectura->estudio;
        $fecha = $this->fecha($lectura->fechor_lectura);
        $fila = [
            "estudio_producto_id" => $lectura->id,
            "estudio_id" => $lectura->estudio_id,
            "sede_id" => $estudio->sede_id,
            "contrato_id" => null,
            "medico_id" => $estudio->medico_id,
            "producto_id" => $lectura->producto_id,
            "clase" => null,
            "valor" => null,
            "exclusion" => null,
            "fecha_lectura" => $fecha,
            "periodo" => substr($fecha, 0, 7),
            "estado" => 1,
        ];

        if (! $lectura->activo) {
            $fila["exclusion"] = "anulada";
        } elseif ($estudio->medico_id === null) {
            $fila["exclusion"] = "sin_asignado";
        } else {
            $contrato = Contrato::where("sede_id", $estudio->sede_id)
                ->where("fecha_inicio", "<=", $fecha)
                ->where("fecha_fin", ">=", $fecha)
                ->first();

            if (! $contrato) {
                $fila["exclusion"] = "sin_contrato";
            } else {
                $fila["contrato_id"] = $contrato->id;
                $cp = $contrato->productos->firstWhere("producto_id", $lectura->producto_id);

                if (! $cp) {
                    $fila["exclusion"] = "producto_sin_tarifa";
                } else {
                    $cm = $contrato->medicos()
                        ->where("contratos_medicos.estado", 1)
                        ->where("medico_id", $estudio->medico_id)
                        ->where("contratos_productos_id", $cp->id)
                        ->with("medico:id,name")
                        ->first();

                    if (! $cm) {
                        $fila["exclusion"] = "medico_fuera_detalle";
                    } else {
                        $esUrgencia = ConfigPrioridad::where("nom_priori", "Alta")->pluck("id")->contains((int) $estudio->prioridad_id);
                        $fila["clase"] = $esUrgencia ? "urgencias" : "consulta";
                        $fila["valor"] = $esUrgencia ? $cm->valor_urgencias : $cm->valor_consulta;
                    }
                }
            }
        }

        LiquidacionLinea::create($fila);
    }

    /**
     * Reporte de productividad por médico para el corte de mes.
     *
     * Lee las líneas congeladas del periodo (no recalcula): cada médico con el
     * detalle de su producción (líneas con valor) y sus lecturas excluidas.
     *
     * Flujo del proceso:
     * 1. Trae las líneas activas de la sede y el periodo con médico y producto.
     * 2. Agrega por médico: líneas liquidables con detalle y totales por clase.
     * 3. Aparte lista las excluidas del periodo con su categoría.
     *
     * @param  int  $sedeId  Sede del corte.
     * @param  string  $periodo  Periodo en YYYY-MM.
     *
     * @returns array Productividad por médico con detalle, totales y excluidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-01
     */
    public function productividad(int $sedeId, string $periodo): array
    {
        $lineas = LiquidacionLinea::where("sede_id", $sedeId)
            ->where("periodo", $periodo)
            ->where("estado", 1)
            ->with(["medico:id,name", "producto:id,cod_cups,nom_produc"])
            ->orderBy("medico_id")
            ->orderBy("fecha_lectura")
            ->get();

        $medicos = [];

        foreach ($lineas as $l) {
            $id = (int) $l->medico_id;

            if (! isset($medicos[$id])) {
                $medicos[$id] = [
                    "medico_id" => $id,
                    "medico_name" => $l->medico->name ?? "",
                    "lineas" => [],
                    "total_consulta" => 0,
                    "total_urgencias" => 0,
                    "total" => 0,
                    "excluidas" => 0,
                ];
            }

            if ($l->valor === null) {
                $medicos[$id]["excluidas"]++;
            } else {
                $medicos[$id]["lineas"][] = $l;
                $clave = $l->clase === "urgencias" ? "total_urgencias" : "total_consulta";
                $medicos[$id][$clave] += (float) $l->valor;
                $medicos[$id]["total"] += (float) $l->valor;
            }
        }

        return $this->success([
            "periodo" => $periodo,
            "medicos" => array_values($medicos),
            "gran_total" => array_sum(array_column($medicos, "total")),
        ]);
    }

    /**
     * Normaliza una fecha (Carbon, DateTime o string) a Y-m-d para comparar.
     *
     * Flujo del proceso:
     * 1. Si es instancia de fecha, la formatea a Y-m-d.
     * 2. Si es string, recorta los primeros 10 caracteres.
     *
     * @param  mixed  $fecha  Fecha a normalizar.
     *
     * @returns string Fecha en formato Y-m-d.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function fecha(mixed $fecha): string
    {
        if ($fecha instanceof DateTimeInterface) {
            return $fecha->format("Y-m-d");
        }

        return substr((string) $fecha, 0, 10);
    }
}
