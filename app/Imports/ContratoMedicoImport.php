<?php

namespace App\Imports;

use App\Exceptions\CupsFaltantesException;
use App\Imports\Concerns\ValidaEncabezados;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import de médicos-producto a un contrato desde Excel.
 * El médico se selecciona previamente en el modal y el Contrato ID se valida
 * contra la celda C2 del archivo (hecho por el controller). Cada fila vincula
 * un producto con sus valores de lectura. Crea registros en contratos_medicos
 * resolviendo el CUPS contra contratos_productos del contrato.
 *
 * El campo valor_contrato NO viene en la plantilla porque queda establecido
 * en contratos_productos; se guarda en 0 a nivel de médico.
 *
 * Si el Excel contiene códigos CUPS inexistentes en config_productos:
 * - Con crear_faltantes = false (pre-validación): lanza CupsFaltantesException
 *   con la lista completa de faltantes para que el usuario confirme.
 * - Con crear_faltantes = true: crea los productos en el catálogo (usando la
 *   columna PRODUCTO como nombre) y continúa.
 * Los códigos que existen en el catálogo pero NO están configurados en el
 * contrato siguen siendo error duro (requieren tarifa definida por el usuario).
 *
 * Estructura esperada del Excel:
 * - Fila 1: Título
 * - Fila 2: Sede: ... | Inicio: ... | Contrato ID: N  (celda C2 validada)
 * - Fila 3: Encabezados (Código CUPS | Producto | Valor Consulta | Valor Urgencias)
 * - Filas 4+: Datos de productos con valores
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-24
 */
class ContratoMedicoImport implements ToCollection, WithHeadingRow
{
    use ValidaEncabezados;

    /**
     * Contador de filas importadas correctamente.
     */
    public int $importados = 0;

    /**
     * Constructor del import.
     *
     * @param  int  $contratoId  Identificador del contrato destino (validado contra C2).
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
     * Fila 1 = título, Fila 2 = info del contrato, Fila 3 = headers.
     *
     * @return int Fila de encabezados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function headingRow(): int
    {
        return 3;
    }

    /**
     * Procesa las filas del Excel dentro de una transacción atómica.
     * Primero valida los encabezados de la fila 3 y que existan códigos CUPS
     * legibles. Luego detecta los códigos inexistentes en config_productos;
     * si hay faltantes y no se autorizó su creación, lanza CupsFaltantesException
     * con la lista completa. Finalmente valida que cada producto esté configurado
     * en contratos_productos del contrato y hace upsert por (contrato, médico,
     * contratos_productos) guardando valor_contrato en 0.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows  Filas del Excel mapeadas por encabezados.
     *
     * @throws \Exception Si los encabezados no coinciden con el formato esperado, no hay códigos legibles, o un CUPS no está configurado en el contrato.
     * @throws CupsFaltantesException Si hay CUPS inexistentes en el catálogo y no se autorizó crearlos.
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

        // ── FASE 0: validar encabezados de la fila 3 ───────────────────
        $this->validarEncabezados(
            $rows->first(),
            ['codigo_cups', 'producto', 'valor_consulta', 'valor_urgencias'],
            'Código CUPS | Producto | Valor Consulta | Valor Urgencias (encabezados en fila 3, datos desde fila 4)'
        );

        // ── FASE 0b: validar que existan códigos CUPS legibles ─────────
        // Si ninguna fila trae código CUPS, los datos no inician en la fila 4.
        $hayCodigos = false;

        foreach ($rows as $row) {
            if (trim((string) ($row['codigo_cups'] ?? '')) !== '') {
                $hayCodigos = true;
                break;
            }
        }

        if (! $hayCodigos) {
            throw new \Exception(
                'No se encontraron códigos CUPS válidos en el archivo. Verifique que los datos inicien en la fila 4, debajo de los encabezados.'
            );
        }

        // ── FASE 1: parsear filas y detectar faltantes en el catálogo ──
        $productosCache = ConfigProducto::where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        $items = [];
        $faltantes = [];

        foreach ($rows as $row) {
            $codCups = trim((string) ($row['codigo_cups'] ?? ''));

            if ($codCups === '') {
                continue;
            }

            $actividad = trim((string) ($row['producto'] ?? ''));

            $items[] = [
                'codigo' => $codCups,
                'valor_consulta' => (float) ($row['valor_consulta'] ?? 0),
                'valor_urgencias' => (float) ($row['valor_urgencias'] ?? 0),
            ];

            if (! isset($productosCache[$codCups]) && ! isset($faltantes[$codCups])) {
                $faltantes[$codCups] = [
                    'codigo' => $codCups,
                    'actividad' => $actividad,
                ];
            }
        }

        if (! empty($faltantes)) {
            if (! $this->crearFaltantes) {
                // Pre-validación: no importa nada, devuelve la lista al frontend
                throw new CupsFaltantesException(array_values($faltantes));
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

        DB::transaction(function () use ($items, $productosCache, $mapaProductos) {
            foreach ($items as $item) {
                $productoId = $productosCache[$item['codigo']];

                if (! isset($mapaProductos[$productoId])) {
                    throw new \Exception(
                        "El producto CUPS '{$item['codigo']}' no está configurado en este contrato. Importe primero la plantilla de productos."
                    );
                }

                ContratoMedico::updateOrCreate(
                    [
                        'contrato_id' => $this->contratoId,
                        'medico_id' => $this->medicoId,
                        'contratos_productos_id' => $mapaProductos[$productoId],
                    ],
                    [
                        'valor_contrato' => 0,
                        'valor_consulta' => $item['valor_consulta'],
                        'valor_urgencias' => $item['valor_urgencias'],
                        'estado' => 1,
                    ]
                );

                $this->importados++;
            }
        });
    }
}
