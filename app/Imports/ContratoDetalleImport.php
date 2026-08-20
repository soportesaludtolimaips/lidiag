<?php

namespace App\Imports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import de detalles de contratos desde Excel.
 * Lee el contrato_id de la fila 2, valida CUPS por fila,
 * y crea registros en contrato_detalle dentro de una transacción atómica.
 *
 * Estructura esperada del Excel:
 * - Fila 2, columna D: Contrato ID
 * - Fila 3: Encabezados (Código CUPS, Sede, Valor Contrato, Valor Consulta, Valor Urgencias)
 * - Fila 4+: Datos de productos
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoDetalleImport implements ToCollection, WithHeadingRow
{
    /**
     * @var int ID del médico seleccionado en el modal
     */
    protected int $medicoId;

    /**
     * Constructor del import.
     *
     * @param  int  $medicoId  Identificador del médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function __construct(int $medicoId)
    {
        $this->medicoId = $medicoId;
    }

    /**
     * Número de fila donde comienzan los encabezados.
     * Fila 1 = título, Fila 2 = info contrato, Fila 3 = headers.
     *
     * @returns int
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function headingRow(): int
    {
        return 3;
    }

    /**
     * Procesa las filas del Excel dentro de una transacción atómica.
     * Valida que cada código CUPS exista en config_productos.
     * Si alguna fila falla, hace rollback completo y lanza excepción
     * con el número de fila y código del producto fallido.
     *
     * @param  Collection  $rows  Filas del Excel (mapeadas por encabezados de fila 3).
     *
     * @throws \Exception Si un código CUPS no existe en config_productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw new \Exception('El archivo Excel no contiene datos para importar.');
        }

        // Obtener el contrato_id del primer registro (viene de la fila 2 del Excel)
        $firstRow = $rows->first();
        $contratoId = $firstRow['contrato_id'] ?? null;

        if (! $contratoId || ! is_numeric($contratoId)) {
            throw new \Exception('No se encontró el ID del contrato en el archivo Excel (fila 2, columna D).');
        }

        $contrato = Contrato::find((int) $contratoId);
        if (! $contrato) {
            throw new \Exception("El contrato con ID {$contratoId} no existe.");
        }

        // Cache de productos CUPS para evitar consultas repetidas
        $productosCache = ConfigProducto::where('requi_lectura', 1)
            ->where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        DB::transaction(function () use ($rows, $contratoId, $productosCache) {
            $filaNum = 4; // Los datos empiezan en fila 4 del Excel

            foreach ($rows as $row) {
                $codCups = trim($row['codigo_cups'] ?? '');
                $sede = $row['sede'] ?? ''; // Solo informativo, no se guarda
                $valorContrato = (float) ($row['valor_contrato'] ?? 0);
                $valorConsulta = (float) ($row['valor_consulta'] ?? 0);
                $valorUrgencias = (float) ($row['valor_urgencias'] ?? 0);

                // Validar que el código CUPS no esté vacío
                if (empty($codCups)) {
                    throw new \Exception(
                        "Error en fila {$filaNum}: el código CUPS está vacío."
                    );
                }

                // Validar que el código CUPS exista en config_productos
                if (! isset($productosCache[$codCups])) {
                    throw new \Exception(
                        "Error en fila {$filaNum}: el código CUPS '{$codCups}' no existe en la base de datos."
                    );
                }

                // Crear el detalle del contrato
                ContratoDetalle::create([
                    'contrato_id' => (int) $contratoId,
                    'medico_id' => $this->medicoId,
                    'producto_id' => $productosCache[$codCups],
                    'valor_contrato' => $valorContrato,
                    'valor_consulta' => $valorConsulta,
                    'valor_urgencias' => $valorUrgencias,
                    'estado' => 1,
                ]);

                $filaNum++;
            }
        });
    }
}
