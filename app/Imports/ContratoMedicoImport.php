<?php

namespace App\Imports;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import de médicos-producto a un contrato desde Excel.
 * El médico se selecciona previamente en el modal; cada fila del Excel vincula
 * un producto (por código CUPS) con sus valores de lectura. Crea registros en
 * contratos_medicos resolviendo el CUPS contra contratos_productos del contrato.
 *
 * Estructura esperada del Excel:
 * - Fila 1: Título
 * - Fila 2: Encabezados (CODIGO | ACTIVIDAD | Valor Contrato | Valor Consulta | Valor Urgencias)
 * - Filas 3+: Datos de productos con valores
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class ContratoMedicoImport implements ToCollection, WithHeadingRow
{
    /**
     * Contador de filas importadas correctamente.
     */
    public int $importados = 0;

    /**
     * Constructor del import.
     *
     * @param  int  $contratoId  Identificador del contrato destino.
     * @param  int  $medicoId  Identificador del médico seleccionado en el modal.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function __construct(
        protected int $contratoId,
        protected int $medicoId,
    ) {}

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
     * Valida que cada código CUPS esté configurado en contratos_productos del
     * contrato. Hace upsert por (contrato, médico, producto): actualiza valores
     * si ya existe la vinculación, la crea si es nueva. Si alguna fila falla,
     * hace rollback completo y lanza excepción con fila y código fallido.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows  Filas del Excel mapeadas por encabezados.
     *
     * @throws \Exception Si un código CUPS no está configurado en el contrato.
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

        // Cache de productos activos por código CUPS
        $productosCache = ConfigProducto::where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        // Mapa producto_id => contratos_productos.id configurados en este contrato
        $mapaProductos = ContratoProducto::where('contrato_id', $this->contratoId)
            ->pluck('id', 'producto_id')
            ->toArray();

        DB::transaction(function () use ($rows, $productosCache, $mapaProductos) {
            $filaNum = 3;

            foreach ($rows as $row) {
                $codCups = trim((string) ($row['codigo'] ?? ''));
                $valorContrato = (float) ($row['valor_contrato'] ?? 0);
                $valorConsulta = (float) ($row['valor_consulta'] ?? 0);
                $valorUrgencias = (float) ($row['valor_urgencias'] ?? 0);

                if ($codCups !== '') {
                    if (! isset($productosCache[$codCups])) {
                        throw new \Exception(
                            "Error en fila {$filaNum}: el código CUPS '{$codCups}' no existe en la base de datos."
                        );
                    }

                    $productoId = $productosCache[$codCups];

                    if (! isset($mapaProductos[$productoId])) {
                        throw new \Exception(
                            "Error en fila {$filaNum}: el producto CUPS '{$codCups}' no está configurado en este contrato. Importe primero la plantilla de productos."
                        );
                    }

                    ContratoMedico::updateOrCreate(
                        [
                            'contrato_id' => $this->contratoId,
                            'medico_id' => $this->medicoId,
                            'contratos_productos_id' => $mapaProductos[$productoId],
                        ],
                        [
                            'valor_contrato' => $valorContrato,
                            'valor_consulta' => $valorConsulta,
                            'valor_urgencias' => $valorUrgencias,
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
