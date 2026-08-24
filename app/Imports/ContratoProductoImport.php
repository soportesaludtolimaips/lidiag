<?php

namespace App\Imports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Contratacion\ContratoProducto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import de productos a un contrato desde Excel.
 * Realiza upsert en contratos_productos por (contrato_id, producto_id):
 * actualiza el valor si ya existe, lo crea si es nuevo. Los productos del
 * contrato ausentes en el Excel NO se eliminan.
 *
 * Estructura esperada del Excel:
 * - Fila 1: Título
 * - Fila 2: Encabezados (CODIGO | ACTIVIDAD | Tarifa Actividad)
 * - Filas 3+: Datos de productos
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class ContratoProductoImport implements ToCollection, WithHeadingRow
{
    /**
     * Contador de filas importadas correctamente (creadas o actualizadas).
     */
    public int $importados = 0;

    /**
     * Constructor del import.
     *
     * @param  int  $contratoId  Identificador del contrato destino de los productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function __construct(protected int $contratoId) {}

    /**
     * Número de fila donde comienzan los encabezados.
     * Fila 1 = título, Fila 2 = headers.
     *
     * @return int Fila de encabezados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function headingRow(): int
    {
        return 2;
    }

    /**
     * Procesa las filas del Excel dentro de una transacción atómica.
     * Valida que cada código CUPS exista en config_productos activo.
     * Si alguna fila falla, hace rollback completo y lanza excepción
     * con el número de fila y código del producto fallido.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows  Filas del Excel mapeadas por encabezados.
     *
     * @throws \Exception Si un código CUPS no existe en config_productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new \Exception('El archivo Excel no contiene datos para importar.');
        }

        // Cache de productos CUPS para evitar consultas repetidas
        $productosCache = ConfigProducto::where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        DB::transaction(function () use ($rows, $productosCache) {
            $filaNum = 3;

            foreach ($rows as $row) {
                $codCups = trim((string) ($row['codigo'] ?? ''));
                $tarifa = (float) ($row['tarifa_actividad'] ?? 0);

                if ($codCups !== '') {
                    if (! isset($productosCache[$codCups])) {
                        throw new \Exception(
                            "Error en fila {$filaNum}: el código CUPS '{$codCups}' no existe en la base de datos."
                        );
                    }

                    ContratoProducto::updateOrCreate(
                        [
                            'contrato_id' => $this->contratoId,
                            'producto_id' => $productosCache[$codCups],
                        ],
                        [
                            'valor_contrato' => $tarifa,
                            'estado' => 1,
                        ]
                    );

                    $this->importados++;
                }
                $filaNum++;
            }
        });
    }
}
