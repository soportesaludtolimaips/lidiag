<?php

namespace App\Imports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Contratacion\ContratoDetalle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import de detalles de contratos desde Excel.
 * Valida CUPS por fila y crea registros en contrato_detalle dentro de una transacción atómica.
 * El contrato_id se recibe del controller (validado contra D2 del Excel).
 *
 * Estructura esperada del Excel:
 * - Fila 3: Encabezados (Código CUPS, Valor Contrato, Valor Consulta, Valor Urgencias)
 * - Filas 4+: Datos de productos
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoDetalleImport implements ToCollection, WithHeadingRow
{
    /**
     * @var int ID del contrato (validado previamente por el controller)
     */
    protected int $contratoId;

    /**
     * @var int ID del médico seleccionado en el modal
     */
    protected int $medicoId;

    /**
     * Constructor del import.
     *
     * @param  int  $contratoId  Identificador del contrato (de la ruta, validado contra D2 del Excel).
     * @param  int  $medicoId  Identificador del médico.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function __construct(int $contratoId, int $medicoId)
    {
        $this->contratoId = $contratoId;
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

        // Cache de productos CUPS para evitar consultas repetidas
        $productosCache = ConfigProducto::where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        // Cache de duplicados existentes en la BD para este contrato + médico
        $existentes = ContratoDetalle::where('contrato_id', $this->contratoId)
            ->where('medico_id', $this->medicoId)
            ->pluck('producto_id')
            ->toArray();
        $existentesSet = array_flip($existentes);

        DB::transaction(function () use ($rows, $productosCache, $existentesSet) {
            $filaNum = 4;

            foreach ($rows as $row) {
                $codCups = trim($row['codigo_cups'] ?? '');
                $valorContrato = (float) ($row['valor_contrato'] ?? 0);
                $valorConsulta = (float) ($row['valor_consulta'] ?? 0);
                $valorUrgencias = (float) ($row['valor_urgencias'] ?? 0);

                if (! empty($codCups)) {

                    if (! isset($productosCache[$codCups])) {
                        throw new \Exception(
                            "Error en fila {$filaNum}: el código CUPS '{$codCups}' no existe en la base de datos."
                        );
                    }

                    $productoId = $productosCache[$codCups];

                    if (isset($existentesSet[$productoId])) {
                        throw new \Exception(
                            "Error en fila {$filaNum}: el producto CUPS '{$codCups}' ya está registrado para este médico en este contrato."
                        );
                    }

                    ContratoDetalle::create([
                        'contrato_id' => $this->contratoId,
                        'medico_id' => $this->medicoId,
                        'producto_id' => $productoId,
                        'valor_contrato' => $valorContrato,
                        'valor_consulta' => $valorConsulta,
                        'valor_urgencias' => $valorUrgencias,
                        'estado' => 1,
                    ]);

                    $existentesSet[$productoId] = true;
                }
                $filaNum++;
            }
        });
    }
}
