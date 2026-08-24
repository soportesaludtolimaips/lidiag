<?php

namespace App\Imports;

use App\Exceptions\CupsFaltantesException;
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
 * Si el Excel contiene códigos CUPS inexistentes en config_productos:
 * - Con crear_faltantes = false (pre-validación): lanza CupsFaltantesException
 *   con la lista completa de faltantes para que el usuario confirme.
 * - Con crear_faltantes = true: crea los productos en el catálogo y continúa.
 * Los códigos que existen en el catálogo pero NO están configurados en el
 * contrato siguen siendo error duro (requieren tarifa definida por el usuario).
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
     * @param  bool  $crearFaltantes  Si true, crea automáticamente los productos inexistentes en config_productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function __construct(
        protected int $contratoId,
        protected int $medicoId,
        protected bool $crearFaltantes = false,
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
     * Primero detecta todos los códigos CUPS inexistentes en config_productos;
     * si hay faltantes y no se autorizó su creación, lanza CupsFaltantesException
     * con la lista completa (sin importar nada). Luego valida que cada producto
     * esté configurado en contratos_productos del contrato y hace upsert por
     * (contrato, médico, contratos_productos).
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows  Filas del Excel mapeadas por encabezados.
     *
     * @throws CupsFaltantesException Si hay CUPS inexistentes en el catálogo y no se autorizó crearlos.
     * @throws \Exception Si un CUPS existe en el catálogo pero no está configurado en el contrato.
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

        // ── FASE 1: parsear filas y detectar faltantes en el catálogo ──
        $productosCache = ConfigProducto::where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        $faltantes = [];

        foreach ($rows as $row) {
            $codCups = trim((string) ($row['codigo'] ?? ''));

            if ($codCups !== '' && ! isset($productosCache[$codCups])) {
                $faltantes[] = [
                    'codigo' => $codCups,
                    'actividad' => trim((string) ($row['actividad'] ?? '')),
                ];
            }
        }

        if (! empty($faltantes)) {
            if (! $this->crearFaltantes) {
                // Pre-validación: no importa nada, devuelve la lista al frontend
                throw new CupsFaltantesException($faltantes);
            }

            // Crear los productos faltantes en el catálogo maestro
            foreach ($faltantes as $f) {
                $producto = ConfigProducto::create([
                    'cod_cups' => $f['codigo'],
                    'nom_produc' => $f['actividad'] !== '' ? $f['actividad'] : $f['codigo'],
                    'requi_lectura' => 1,
                    'estado' => 1,
                ]);

                $productosCache[$f['codigo']] = $producto->id;
            }
        }

        // ── FASE 2: upsert de contratos_medicos ────────────────────────
        // Mapa producto_id => contratos_productos.id configurados en este contrato
        $mapaProductos = ContratoProducto::where('contrato_id', $this->contratoId)
            ->pluck('id', 'producto_id')
            ->toArray();

        DB::transaction(function () use ($rows, $productosCache, $mapaProductos) {
            foreach ($rows as $row) {
                $codCups = trim((string) ($row['codigo'] ?? ''));
                $valorContrato = (float) ($row['valor_contrato'] ?? 0);
                $valorConsulta = (float) ($row['valor_consulta'] ?? 0);
                $valorUrgencias = (float) ($row['valor_urgencias'] ?? 0);

                if ($codCups !== '') {
                    $productoId = $productosCache[$codCups];

                    if (! isset($mapaProductos[$productoId])) {
                        throw new \Exception(
                            "El producto CUPS '{$codCups}' no está configurado en este contrato. Importe primero la plantilla de productos."
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
            }
        });
    }
}
