<?php

namespace Database\Seeders\Configuracion;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deduplica productos con el mismo cod_cups en config_productos.
 *
 * Reasigna las referencias (producto_id) del id retirado al conservado y
 * marca el retirado con estado = 0. No borra filas de productos. Antes de
 * cada update se fusionan filas que chocarían con un índice único que
 * incluya producto_id (se elimina la fila duplicada del id retirado).
 * Idempotente: se puede ejecutar varias veces sin efecto adicional.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-08
 */
class ConfigProductosDedupeSeeder extends Seeder
{
    /**
     * cod_cups => [id conservar, id retirar].
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private const PARES = [
        '876802' => [64, 1],
        '21601-CCG' => [3, 116],
        '873204' => [41, 117],
        '873312' => [47, 119],
        '873333' => [50, 120],
        '873420' => [55, 121],
        '879201' => [75, 110],
        '883111' => [112, 150],
        '873312-1' => [181, 193],
    ];

    /**
     * Ejecuta la deduplicación de los pares CUPS aprobados.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function run(): void
    {
        foreach (self::PARES as $cups => [$conservar, $retirar]) {
            if (! $this->parValido($cups, $conservar, $retirar)) {
                $this->command?->warn("Dedupe CUPS {$cups}: los ids no coinciden con el catálogo, par omitido.");

                continue;
            }

            DB::transaction(function () use ($conservar, $retirar): void {
                foreach ($this->tablasBaseConProductoId() as $tabla) {
                    $this->reasignarEnTabla($tabla, $conservar, $retirar);
                }

                DB::table('config_productos')->where('id', $retirar)->update(['estado' => 0]);
            });
        }
    }

    /**
     * Verifica que ambos ids existan y que el cod_cups coincida con la clave.
     *
     * @param  string  $cups  CUPS esperado para el par
     * @param  int  $conservar  Id que se conserva
     * @param  int  $retirar  Id que se retira
     *
     * @returns bool true si el par es válido en esta base
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function parValido(string $cups, int $conservar, int $retirar): bool
    {
        $ids = DB::table('config_productos')
            ->whereIn('id', [$conservar, $retirar])
            ->pluck('cod_cups', 'id');

        return $ids->count() === 2
            && $ids[$conservar] === $cups
            && $ids[$retirar] === $cups;
    }

    /**
     * Lista las tablas base (sin vistas) con columna producto_id.
     *
     * @returns array<int, string> nombres de tabla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function tablasBaseConProductoId(): array
    {
        $filas = DB::select(
            "SELECT col.TABLE_NAME AS nombre
             FROM information_schema.COLUMNS col
             JOIN information_schema.TABLES tbl
               ON tbl.TABLE_SCHEMA = col.TABLE_SCHEMA
              AND tbl.TABLE_NAME = col.TABLE_NAME
             WHERE col.TABLE_SCHEMA = DATABASE()
               AND col.COLUMN_NAME = 'producto_id'
               AND tbl.TABLE_TYPE = 'BASE TABLE'
             ORDER BY col.TABLE_NAME"
        );

        return array_map(static fn ($fila): string => $fila->nombre, $filas);
    }

    /**
     * Reasigna en una tabla las referencias del id retirado al conservado.
     *
     * @param  string  $tabla  Tabla con columna producto_id
     * @param  int  $conservar  Id conservado
     * @param  int  $retirar  Id retirado
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function reasignarEnTabla(string $tabla, int $conservar, int $retirar): void
    {
        if (DB::table($tabla)->where('producto_id', $retirar)->doesntExist()) {
            return;
        }

        $primaria = array_map(
            static fn ($indice): string => $indice->Column_name,
            array_filter(
                DB::select("SHOW INDEX FROM `{$tabla}` WHERE Key_name = 'PRIMARY'"),
                static fn ($indice): bool => (int) $indice->Non_unique === 0
            )
        );

        if (in_array('producto_id', $primaria, true)) {
            $this->command?->warn("Dedupe: {$tabla} incluye producto_id en la clave primaria, tabla omitida.");

            return;
        }

        foreach ($this->indicesUnicosConProducto($tabla) as $columnas) {
            $this->fusionarFilasEnConflicto($tabla, $columnas, $conservar, $retirar);
        }

        DB::table($tabla)->where('producto_id', $retirar)->update(['producto_id' => $conservar]);
    }

    /**
     * Devuelve los índices únicos de la tabla que incluyen producto_id.
     *
     * @param  string  $tabla  Tabla a inspeccionar
     *
     * @returns array<int, array<int, string>> cada entrada es el listado ordenado de columnas del índice
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function indicesUnicosConProducto(string $tabla): array
    {
        $indices = DB::select("SHOW INDEX FROM `{$tabla}` WHERE Non_unique = 0 AND Key_name <> 'PRIMARY'");

        $agrupados = [];
        foreach ($indices as $indice) {
            $agrupados[$indice->Key_name][] = $indice->Column_name;
        }

        return array_values(array_filter(
            $agrupados,
            static fn (array $columnas): bool => in_array('producto_id', $columnas, true)
        ));
    }

    /**
     * Elimina filas del id retirado cuyo grupo ya existe para el conservado.
     *
     * Solo se considera conflicto cuando todas las columnas del índice son
     * no nulas e iguales (los nulos no chocan en índices únicos de MySQL).
     *
     * @param  string  $tabla  Tabla con el índice único
     * @param  array<int, string>  $columnas  Columnas del índice
     * @param  int  $conservar  Id conservado
     * @param  int  $retirar  Id retirado
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function fusionarFilasEnConflicto(string $tabla, array $columnas, int $conservar, int $retirar): void
    {
        $otras = array_values(array_diff($columnas, ['producto_id']));

        if ($otras === []) {
            return;
        }

        $filasRetirar = DB::table($tabla)->where('producto_id', $retirar)->get(['id', ...$otras]);

        foreach ($filasRetirar as $fila) {
            $valores = [];
            foreach ($otras as $columna) {
                $valor = $fila->{$columna};
                if ($valor === null) {
                    continue 2;
                }
                $valores[$columna] = $valor;
            }

            $existeConservado = DB::table($tabla)
                ->where('producto_id', $conservar)
                ->when(
                    $valores !== [],
                    static fn ($consulta) => $consulta->where($valores)
                )
                ->exists();

            if ($existeConservado) {
                DB::table($tabla)->where('id', $fila->id)->delete();
            }
        }
    }
}
