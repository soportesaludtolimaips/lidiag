<?php

namespace App\Exports;

use App\Exports\Sheets\ProductividadDetalleSheet;
use App\Exports\Sheets\ProductividadEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportación a Excel del Reporte de Productividad Médica y Honorarios por corte mensual.
 *
 * Flujo del proceso:
 * 1. Recibe la sede y el periodo (YYYY-MM) a liquidar.
 * 2. Consulta la producción congelada mediante LiquidacionService::productividad().
 * 3. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas y Detalle de Productividad.
 * 4. Aplica diseño institucional Salud Tolima con formatos numéricos y gráficos nativos.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class ProductividadExport implements WithMultipleSheets
{
    protected string $nomSede = "";

    protected float $granTotal = 0.0;

    /**
     * @var array<int, int>
     */
    protected array $filasResaltadas = [];

    /**
     * @var array<int, mixed>|null
     */
    protected ?array $cachedMedicosData = null;

    protected ?Collection $cachedColeccion = null;

    /**
     * Inicializa el exportador con los filtros de sede y periodo.
     *
     * @param  int  $sedeId  Identificador de la sede.
     * @param  string  $periodo  Periodo en formato YYYY-MM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(
        protected int $sedeId,
        protected string $periodo
    ) {
        $sede = ConfigSede::find($this->sedeId);
        $this->nomSede = $sede ? $sede->nom_sede : "Sede #{$this->sedeId}";
    }

    /**
     * Retorna las hojas del libro de cálculo institucional.
     *
     * @return array<int, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function sheets(): array
    {
        $this->loadData();

        return [
            new ProductividadEstadisticasSheet(
                $this->cachedMedicosData ?? [],
                $this->nomSede,
                $this->periodo,
                $this->granTotal
            ),
            new ProductividadDetalleSheet(
                $this->cachedColeccion ?? collect(),
                $this->nomSede,
                $this->periodo,
                $this->filasResaltadas
            ),
        ];
    }

    /**
     * Carga y procesa la productividad médica.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function loadData(): void
    {
        if ($this->cachedColeccion !== null) {
            return;
        }

        $result = app(LiquidacionService::class)->productividad($this->sedeId, $this->periodo);
        $medicos = $result["data"]["medicos"] ?? [];
        $this->cachedMedicosData = $medicos;
        $this->granTotal = (float) ($result["data"]["gran_total"] ?? 0.0);

        $filas = [];
        $numeroFila = 5; // A4 es encabezado, datos empiezan en fila 5

        foreach ($medicos as $m) {
            $nombreMedico = $m["medico_name"] ?: "Sin médico asignado";

            // Líneas liquidadas
            foreach ($m["lineas"] as $l) {
                $fecha = $l->fecha_lectura ? substr((string) $l->fecha_lectura, 0, 10) : "";
                $filas[] = [
                    "medico" => $nombreMedico,
                    "fecha" => $fecha,
                    "cod_cups" => $l->producto->cod_cups ?? "",
                    "nom_produc" => $l->producto->nom_produc ?? "",
                    "clase" => $l->clase === "urgencias" ? "Urgencias" : "Consulta",
                    "valor" => (float) $l->valor,
                    "observacion" => "Liquidado",
                ];
                $numeroFila++;
            }

            // Líneas excluidas
            foreach ($m["excluidas_detalle"] as $x) {
                $fecha = $x->fecha_lectura ? substr((string) $x->fecha_lectura, 0, 10) : "";
                $filas[] = [
                    "medico" => $nombreMedico,
                    "fecha" => $fecha,
                    "cod_cups" => $x->producto->cod_cups ?? "",
                    "nom_produc" => $x->producto->nom_produc ?? "",
                    "clase" => "Excluida",
                    "valor" => 0.0,
                    "observacion" => "Excluido: ".($x->exclusion ?? "No parametrizado"),
                ];
                $numeroFila++;
            }

            // Fila de subtotal por médico
            $filas[] = [
                "medico" => "SUBTOTAL: {$nombreMedico}",
                "fecha" => "",
                "cod_cups" => "",
                "nom_produc" => count($m["lineas"])." liquidables | ".count($m["excluidas_detalle"])." excluidas",
                "clase" => "Subtotal",
                "valor" => (float) $m["total"],
                "observacion" => "Consulta: $".number_format($m["total_consulta"], 0, ",", ".")." | Urg: $".number_format($m["total_urgencias"], 0, ",", "."),
            ];
            $this->filasResaltadas[] = $numeroFila;
            $numeroFila++;
        }

        // Fila de gran total
        if (! empty($filas)) {
            $filas[] = [
                "medico" => "GRAN TOTAL PERIODO {$this->periodo}",
                "fecha" => "",
                "cod_cups" => "",
                "nom_produc" => "Total general acumulado",
                "clase" => "Total",
                "valor" => $this->granTotal,
                "observacion" => "Sede: {$this->nomSede}",
            ];
            $this->filasResaltadas[] = $numeroFila;
        }

        $this->cachedColeccion = collect($filas);
    }

    /**
     * Construye la colección de datos a exportar, aplanando lecturas y subtotales.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function collection(): Collection
    {
        $this->loadData();

        return $this->cachedColeccion ?? collect();
    }
}
