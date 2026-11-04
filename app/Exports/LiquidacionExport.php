<?php

namespace App\Exports;

use App\Exports\Sheets\LiquidacionDetalleSheet;
use App\Exports\Sheets\LiquidacionEstadisticasSheet;
use App\Models\Configuracion\ConfigSede;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exportación de la liquidación de honorarios médicos a Excel.
 *
 * Flujo del proceso:
 * 1. Recibe sede y rango de fechas por constructor.
 * 2. Delega el cálculo a LiquidacionService::calcular().
 * 3. Genera un libro Excel con 2 hojas: Estadísticas Ejecutivas de Honorarios y Detalle de Líneas.
 * 4. Aplica gráficos nativos y formato de moneda corporativo Salud Tolima.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class LiquidacionExport implements WithMultipleSheets
{
    protected string $nomSede = "";

    protected float $granTotal = 0.0;

    protected int $totalLineas = 0;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $cachedRawLineas = null;

    protected ?Collection $cachedColeccion = null;

    /**
     * Guarda los filtros del periodo a liquidar y resuelve la sede.
     *
     * @param  int  $sedeId  Sede liquidada.
     * @param  string  $fechaInicio  Inicio en Y-m-d.
     * @param  string  $fechaFin  Fin en Y-m-d.
     * @param  int|null  $contratoId  Contrato opcional para acotar la vista.
     * @param  int|null  $medicoId  Médico opcional para acotar la vista.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function __construct(
        protected int $sedeId,
        protected string $fechaInicio,
        protected string $fechaFin,
        protected ?int $contratoId = null,
        protected ?int $medicoId = null
    ) {
        $sede = ConfigSede::find($this->sedeId);
        $this->nomSede = $sede ? $sede->nom_sede : "Sede #{$this->sedeId}";
    }

    /**
     * Retorna las hojas del libro de cálculo.
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
            new LiquidacionEstadisticasSheet(
                $this->cachedRawLineas ?? [],
                $this->nomSede,
                $this->fechaInicio,
                $this->fechaFin
            ),
            new LiquidacionDetalleSheet(
                $this->cachedColeccion ?? collect(),
                $this->nomSede,
                $this->fechaInicio,
                $this->fechaFin,
                $this->granTotal,
                $this->totalLineas
            ),
        ];
    }

    /**
     * Carga y procesa la información de liquidación médica.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    protected function loadData(): void
    {
        if ($this->cachedRawLineas !== null) {
            return;
        }

        $result = app(LiquidacionService::class)->calcular(
            $this->sedeId,
            $this->fechaInicio,
            $this->fechaFin,
            $this->contratoId,
            $this->medicoId
        );

        $lineas = $result["data"]["lineas"] ?? [];
        $this->cachedRawLineas = $lineas;
        $this->totalLineas = count($lineas);
        $this->granTotal = (float) ($result["data"]["totales"]["gran_total"] ?? 0.0);

        $coleccion = collect($lineas);

        if ($this->totalLineas > 0) {
            $coleccion->push([
                "es_total" => true,
                "medico_name" => "GRAN TOTAL LIQUIDADO",
                "cod_cups" => "",
                "nom_produc" => "Total acumulado de honorarios médicos",
                "clase" => "{$this->totalLineas} lecturas",
                "valor" => $this->granTotal,
                "fecha_lectura" => "{$this->fechaInicio} a {$this->fechaFin}",
                "contrato_id" => "",
            ]);
        }

        $this->cachedColeccion = $coleccion;
    }

    /**
     * Entrega las líneas de la liquidación como colección, agregando la fila de total.
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
