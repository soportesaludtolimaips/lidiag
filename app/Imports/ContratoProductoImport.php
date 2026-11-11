<?php

namespace App\Imports;

use App\Exceptions\CupsFaltantesException;
use App\Imports\Concerns\ValidaEncabezados;
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
 * Si el Excel contiene códigos CUPS inexistentes en config_productos:
 * - Con crear_faltantes = false (pre-validación): lanza CupsFaltantesException
 *   con la lista completa de faltantes para que el usuario confirme.
 * - Con crear_faltantes = true: crea los productos en el catálogo
 *   (cod_cups + nom_produc desde la columna ACTIVIDAD, estado=1, requi_lectura=1)
 *   y continúa con la importación.
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
    use ValidaEncabezados;

    /**
     * Contador de filas importadas correctamente (creadas o actualizadas).
     */
    public int $importados = 0;

    /**
     * Constructor del import.
     *
     * @param  int  $contratoId  Identificador del contrato destino de los productos.
     * @param  bool  $crearFaltantes  Si true, crea automáticamente los productos inexistentes en config_productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-24
     */
    public function __construct(
        protected int $contratoId,
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
     * Primero parsea las filas y valida que existan códigos CUPS legibles;
     * si el archivo trae filas pero ninguna con código válido (formato de
     * encabezados incorrecto), lanza excepción explicativa. Luego detecta los
     * códigos inexistentes en config_productos; si hay faltantes y no se
     * autorizó su creación, lanza CupsFaltantesException con la lista completa.
     * Finalmente hace el upsert de contratos_productos.
     *
     * @param  Collection<int, Collection<string, mixed>>  $rows  Filas del Excel mapeadas por encabezados.
     *
     * @throws \Exception Si los encabezados no coinciden con el formato esperado, o ninguna fila contiene un código CUPS legible.
     * @throws CupsFaltantesException Si hay CUPS inexistentes y no se autorizó crearlos.
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

        // ── FASE 0: validar encabezados de la fila 2 ───────────────────
        $this->validarEncabezados(
            $rows->first(),
            ['codigo', 'actividad', 'tarifa_actividad'],
            'CODIGO | ACTIVIDAD | Tarifa Actividad (encabezados en fila 2, datos desde fila 3)'
        );

        // ── FASE 1: parsear filas ──────────────────────────────────────
        $productosCache = ConfigProducto::where('estado', 1)
            ->pluck('id', 'cod_cups')
            ->toArray();

        $items = [];

        foreach ($rows as $row) {
            $codCups = trim((string) ($row['codigo'] ?? ''));

            if ($codCups !== '') {
                $items[] = [
                    'codigo' => $codCups,
                    'actividad' => trim((string) ($row['actividad'] ?? '')),
                    'tarifa' => (float) ($row['tarifa_actividad'] ?? 0),
                ];
            }
        }

        if (empty($items)) {
            throw new \Exception(
                'No se encontraron códigos CUPS válidos en el archivo. Verifique que los datos inicien en la fila 3, debajo de los encabezados.'
            );
        }

        // ── FASE 1: detectar faltantes en el catálogo ──────────────────
        $faltantes = [];

        foreach ($items as $item) {
            if (! isset($productosCache[$item['codigo']])) {
                $faltantes[$item['codigo']] = [
                    'codigo' => $item['codigo'],
                    'actividad' => $item['actividad'],
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

        // ── FASE 2: upsert de contratos_productos ──────────────────────
        DB::transaction(function () use ($items, $productosCache) {
            foreach ($items as $item) {
                ContratoProducto::updateOrCreate(
                    [
                        'contrato_id' => $this->contratoId,
                        'producto_id' => $productosCache[$item['codigo']],
                    ],
                    [
                        'valor_contrato' => $item['tarifa'],
                        'estado' => 1,
                    ]
                );

                $this->importados++;
            }
        });
    }
}
