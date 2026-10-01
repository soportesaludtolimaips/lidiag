<?php

namespace App\Exports;

use App\Services\Liquidacion\LiquidacionService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export de la liquidación de un periodo a Excel.
 *
 * Reutiliza LiquidacionService::calcular() para no duplicar la lógica.
 *
 * Flujo del proceso:
 * 1. Recibe sede y rango de fechas por constructor.
 * 2. Delega el cálculo a LiquidacionService::calcular().
 * 3. Expone las líneas como colección con encabezados y anchos en español.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
class LiquidacionExport implements FromCollection, WithColumnWidths, WithHeadings, WithMapping
{
    /**
     * Guarda los filtros del periodo a liquidar.
     *
     * Flujo del proceso:
     * 1. Recibe la sede a liquidar.
     * 2. Recibe el inicio y fin del periodo en Y-m-d.
     *
     * @param  int  $sedeId  Sede liquidada.
     * @param  string  $fechaInicio  Inicio en Y-m-d.
     * @param  string  $fechaFin  Fin en Y-m-d.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function __construct(protected int $sedeId, protected string $fechaInicio, protected string $fechaFin) {}

    /**
     * Entrega las líneas de la liquidación como colección.
     *
     * Flujo del proceso:
     * 1. Llama a LiquidacionService::calcular() con los filtros guardados.
     * 2. Extrae las líneas del payload data (patrón success de BaseService).
     * 3. Devuelve la colección (vacía si no hay líneas).
     *
     * @returns \Illuminate\Support\Collection<int, array> Líneas de la liquidación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function collection()
    {
        $result = app(LiquidacionService::class)->calcular($this->sedeId, $this->fechaInicio, $this->fechaFin);

        return collect($result["data"]["lineas"] ?? []);
    }

    /**
     * Entrega los encabezados de la hoja en español.
     *
     * Flujo del proceso:
     * 1. Devuelve el arreglo fijo de 7 encabezados en el orden del map.
     *
     * @returns array<int, string> Encabezados en español.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function headings(): array
    {
        return ["Médico", "CUPS", "Producto", "Clase", "Valor", "Fecha lectura", "Contrato"];
    }

    /**
     * Mapea una línea de la liquidación a una fila del Excel.
     *
     * Flujo del proceso:
     * 1. Recibe una línea (arreglo) de LiquidacionService::calcular().
     * 2. Devuelve las 7 celdas en el orden de los encabezados.
     *
     * @param  array  $linea  Una línea de la liquidación.
     *
     * @returns array<int, mixed> Fila del Excel.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function map($linea): array
    {
        return [$linea["medico_name"], $linea["cod_cups"], $linea["nom_produc"], $linea["clase"], $linea["valor"], $linea["fecha_lectura"], $linea["contrato_id"]];
    }

    /**
     * Entrega los anchos de columna de la hoja.
     *
     * Flujo del proceso:
     * 1. Devuelve el ancho fijo por columna (A a G).
     *
     * @returns array<string, float> Anchos de columna.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function columnWidths(): array
    {
        return ["A" => 30, "B" => 15, "C" => 45, "D" => 12, "E" => 15, "F" => 15, "G" => 12];
    }
}
