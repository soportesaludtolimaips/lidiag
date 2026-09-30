<?php

namespace App\Services\Contratacion;

use App\Models\Configuracion\ConfigSede;
use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use App\Services\BaseService;
use Illuminate\Support\Carbon;

/**
 * Service ContratoService: lógica de negocio del módulo de contratación.
 *
 * Gestiona el CRUD de contratos y la sincronización de sus relaciones:
 * productos contratados (`contratos_productos`) y médicos vinculados con
 * sus valores por lectura (`contratos_medicos`).
 *
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-08-20
 */
class ContratoService extends BaseService
{
    /**
     * Lista contratos con filtros opcionales y paginación.
     * Incluye relaciones: sede, productos.producto, medicos.medico y medicos.producto.
     *
     * @param  array  $filters  Filtros: sede_id, estado, q (búsqueda), per_page, page.
     *
     * @returns array Respuesta con items paginados y metadata.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function list(array $filters): array
    {
        $q = Contrato::query()
            ->with([
                'sede:id,cod_sede,nom_sede',
                'productos.producto:id,cod_cups,nom_produc',
                'medicos.medico:id,name,reg_med',
                'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
            ]);

        if (! empty($filters['sede_id'])) {
            $q->where('sede_id', (int) $filters['sede_id']);
        }

        if (! empty($filters['estado'])) {
            $q->where('estado', $filters['estado'] === '1');
        }

        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
            $q->where(function ($qq) use ($escaped) {
                $qq->where('observaciones', 'like', "%{$escaped}%")
                    ->orWhereHas('sede', function ($sq) use ($escaped) {
                        $sq->where('nom_sede', 'like', "%{$escaped}%");
                    });
            });
        }

        $q->orderByDesc('created_at');

        $perPage = max(1, min(200, (int) ($filters['per_page'] ?? 50)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $paginator = $q->paginate(perPage: $perPage, page: $page);

        return $this->success([
            'data' => $paginator->items(),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Busca un contrato por ID con todas sus relaciones.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns array Respuesta con el contrato encontrado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function find(int $id): array
    {
        $contrato = Contrato::with([
            'sede:id,cod_sede,nom_sede',
            'productos.producto:id,cod_cups,nom_produc',
            'medicos.medico:id,name,reg_med',
            'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
        ])->find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->success($contrato);
    }

    /**
     * Busca el contrato de la sede cuyo rango de fechas se cruza con el indicado.
     *
     * Se usa dentro de la misma transacción que inserta o actualiza, con el
     * bloqueo de fila ya tomado, para que dos cargas simultáneas no puedan
     * insertar contratos solapados. La regla es solo por fechas: un contrato
     * cerrado también ocupa su rango.
     *
     * Flujo del proceso:
     * 1. Filtra los contratos de la sede cuyas fechas se cruzan con el rango indicado.
     * 2. Excluye el contrato que se está editando, si se recibe su identificador.
     * 3. Bloquea las filas y devuelve el primer contrato encontrado, o null si no hay cruce.
     *
     * @param  int  $sedeId  Sede del contrato a validar.
     * @param  string  $fechaInicio  Fecha de inicio en formato Y-m-d.
     * @param  string  $fechaFin  Fecha de fin en formato Y-m-d.
     * @param  int|null  $ignorarContratoId  Contrato a excluir (el que se está editando).
     * @return Contrato|null Contrato en conflicto, o null si no existe solapamiento.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    private function buscarSolapamiento(
        int $sedeId,
        string $fechaInicio,
        string $fechaFin,
        ?int $ignorarContratoId = null
    ): ?Contrato {
        $query = Contrato::where('sede_id', $sedeId)
            ->whereDate('fecha_inicio', '<=', $fechaFin)
            ->whereDate('fecha_fin', '>=', $fechaInicio);

        if ($ignorarContratoId !== null) {
            $query->where('id', '!=', $ignorarContratoId);
        }

        return $query->lockForUpdate()->first();
    }

    /**
     * Crea un nuevo contrato dentro de una transacción.
     *
     * Flujo del proceso:
     * 1. Bloquea la fila de la sede para serializar cargas concurrentes.
     * 2. Verifica que no exista otro contrato de esa sede con fechas cruzadas.
     * 3. Inserta el contrato y devuelve la sede ya cargada.
     *
     * @param  array  $data  Datos del contrato: sede_id, fecha_inicio, fecha_fin, observaciones, estado.
     *
     * @returns array Contrato creado, o error 422 si las fechas se solapan.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function create(array $data): array
    {
        return $this->transaction(function () use ($data) {
            ConfigSede::where('id', $data['sede_id'])->lockForUpdate()->first();

            $inicio = Carbon::parse($data['fecha_inicio'])->format('Y-m-d');
            $fin = Carbon::parse($data['fecha_fin'])->format('Y-m-d');

            $conflicto = $this->buscarSolapamiento($data['sede_id'], $inicio, $fin);

            if ($conflicto !== null) {
                $rango = $conflicto->fecha_inicio->format('Y-m-d').' a '.$conflicto->fecha_fin->format('Y-m-d');

                return $this->error(
                    'La sede ya tiene el contrato #'.$conflicto->id
                    .' (vigencia '.$rango.') ocupando ese rango de fechas. '
                    .'Elija un rango de fechas diferente.',
                    422
                );
            }

            $contrato = Contrato::create([
                'sede_id' => $data['sede_id'],
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => $data['estado'] ?? 1,
            ]);

            return $this->created($contrato->load('sede:id,cod_sede,nom_sede'));
        });
    }

    /**
     * Actualiza un contrato existente dentro de una transacción.
     *
     * @param  int  $id  Identificador del contrato a actualizar.
     * @param  array  $data  Datos a actualizar (campos opcionales).
     *
     * @returns array Respuesta con el contrato actualizado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function update(int $id, array $data): array
    {
        $contrato = Contrato::find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $sedeId = (int) ($data['sede_id'] ?? $contrato->sede_id);

            ConfigSede::where('id', $sedeId)->lockForUpdate()->first();

            $inicio = Carbon::parse($data['fecha_inicio'] ?? $contrato->fecha_inicio)->format('Y-m-d');
            $fin = Carbon::parse($data['fecha_fin'] ?? $contrato->fecha_fin)->format('Y-m-d');

            $conflicto = $this->buscarSolapamiento($sedeId, $inicio, $fin, $contrato->id);

            if ($conflicto !== null) {
                $rango = $conflicto->fecha_inicio->format('Y-m-d').' a '.$conflicto->fecha_fin->format('Y-m-d');

                return $this->error(
                    'La sede ya tiene el contrato #'.$conflicto->id
                    .' (vigencia '.$rango.') ocupando ese rango de fechas. '
                    .'Elija un rango de fechas diferente.',
                    422
                );
            }

            $contrato->update([
                'sede_id' => $sedeId,
                'fecha_inicio' => $inicio,
                'fecha_fin' => $fin,
                'observaciones' => $data['observaciones'] ?? $contrato->observaciones,
                'estado' => $data['estado'] ?? $contrato->estado,
            ]);

            return $this->updated($contrato->load('sede:id,cod_sede,nom_sede'));
        });
    }

    /**
     * Elimina un contrato junto con sus médicos vinculados y productos contratados.
     * Operación atómica dentro de una transacción.
     *
     * @param  int  $id  Identificador del contrato a eliminar.
     *
     * @returns array Respuesta de confirmación o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function delete(int $id): array
    {
        $contrato = Contrato::find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato) {
            $contrato->medicos()->delete();
            $contrato->productos()->delete();
            $contrato->delete();

            return $this->deleted('Contrato eliminado correctamente');
        });
    }

    /**
     * Cambia el estado (activo/inactivo) de un contrato.
     *
     * @param  int  $id  Identificador del contrato.
     *
     * @returns array Respuesta con el contrato y estado actualizado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function toggleEstado(int $id): array
    {
        $contrato = Contrato::find($id);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato) {
            $contrato->update(['estado' => ! $contrato->estado]);

            return $this->updated($contrato);
        });
    }

    /**
     * Calcula la diferencia entre el detalle recibido y el detalle guardado.
     *
     * Flujo del proceso:
     * 1. Traduce cada producto_id a su contratos_productos_id dentro del contrato.
     * 2. Clasifica cada item recibido como alta, cambio o error.
     * 3. Detecta los registros existentes que no vienen en el payload: quedan
     *    en "ausentes" y solo se retiran si se piden explícitamente.
     * 4. No escribe nada en la base de datos.
     *
     * @param  int  $contratoId  Contrato al que pertenece el detalle.
     * @param  array  $items  Items con medico_id, producto_id, valor_consulta,
     *                        valor_urgencias y fecha_ingreso opcional.
     * @param  bool  $retirarFaltantes  Si true, los ausentes se marcan como retiros.
     *
     * @returns array Estructura con las claves altas, cambios, retiros, ausentes y errores.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function calcularDiffMedicos(int $contratoId, array $items, bool $retirarFaltantes = false): array
    {
        $mapaProductos = ContratoProducto::where('contrato_id', $contratoId)
            ->pluck('id', 'producto_id');

        $existentes = ContratoMedico::where('contrato_id', $contratoId)
            ->with(['medico:id,name', 'contratoProducto.producto:id,cod_cups,nom_produc'])
            ->get()
            ->keyBy(fn ($m) => $m->medico_id.'-'.($m->contratoProducto->producto_id ?? 0));

        $altas = [];
        $cambios = [];
        $errores = [];
        $vistos = [];

        foreach ($items as $item) {
            $medicoId = (int) $item['medico_id'];
            $productoId = (int) $item['producto_id'];
            $clave = $medicoId.'-'.$productoId;

            if (isset($vistos[$clave])) {
                $errores[] = "El médico {$medicoId} tiene el producto {$productoId} duplicado en el archivo.";

                continue;
            }

            $vistos[$clave] = true;

            if (! isset($mapaProductos[$productoId])) {
                $errores[] = "El producto {$productoId} no está configurado en este contrato.";

                continue;
            }

            $valores = [
                'medico_id' => $medicoId,
                'producto_id' => $productoId,
                'valor_consulta' => $item['valor_consulta'] ?? 0,
                'valor_urgencias' => $item['valor_urgencias'] ?? 0,
                'fecha_ingreso' => $item['fecha_ingreso'] ?? null,
            ];

            $registro = $existentes->get($clave);

            if (! $registro) {
                $altas[] = $valores;

                continue;
            }

            $fechaExistente = $registro->fecha_ingreso?->format('Y-m-d');
            $fechaNueva = $valores['fecha_ingreso'] ? Carbon::parse($valores['fecha_ingreso'])->format('Y-m-d') : null;

            $cambio = $registro->valor_consulta != $valores['valor_consulta']
                || $registro->valor_urgencias != $valores['valor_urgencias']
                || $fechaExistente != $fechaNueva;

            if ($cambio) {
                $valores['contratos_medicos_id'] = $registro->id;
                $cambios[] = $valores;
            }
        }

        $ausentes = [];
        $retiros = [];

        foreach ($existentes as $clave => $registro) {
            if (isset($vistos[$clave])) {
                continue;
            }

            $resumen = [
                'contratos_medicos_id' => $registro->id,
                'medico_id' => $registro->medico_id,
                'producto_id' => $registro->contratoProducto->producto_id ?? null,
                'medico_name' => $registro->medico->name ?? '',
                'nom_produc' => $registro->contratoProducto->producto->nom_produc ?? '',
            ];

            $ausentes[] = $resumen;

            if ($retirarFaltantes) {
                $retiros[] = $resumen;
            }
        }

        return compact('altas', 'cambios', 'retiros', 'ausentes', 'errores');
    }

    /**
     * Devuelve el diff del detalle de médicos sin aplicarlo, para que el
     * frontend muestre al usuario exactamente qué va a cambiar antes de confirmar.
     *
     * @param  int  $contratoId  Contrato a previsualizar.
     * @param  array  $items  Items del payload de sincronización.
     *
     * @returns array Contrato con relaciones más las claves del diff.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function previewMedicos(int $contratoId, array $items): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $diff = $this->calcularDiffMedicos($contratoId, $items);

        return $this->success(array_merge(
            ['contrato' => $contrato],
            $diff
        ));
    }

    /**
     * Aplica el diff del detalle de médicos. Nunca borra registros: los items
     * nuevos se insertan, los existentes con valores distintos se actualizan y
     * los que no vienen en el payload solo se retiran si el cliente lo pide
     * explícitamente con retirar_faltantes.
     *
     * Flujo del proceso:
     * 1. Calcula el diff contra el estado guardado.
     * 2. Si hay errores de validación, devuelve 422 y no escribe nada.
     * 3. Inserta las altas con fecha_ingreso en null (aplica desde el inicio
     *    del contrato) salvo que el item traiga una fecha.
     * 4. Actualiza solo los registros que cambiaron de valor.
     * 5. Marca como retirados los ausentes cuando retirer_faltantes es true.
     *
     * @param  int  $contratoId  Contrato a sincronizar.
     * @param  array  $data  Payload con medicos y retirar_faltantes opcional.
     *
     * @returns array Contrato actualizado con relaciones, o error 422.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-09-30
     */
    public function syncMedicos(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        $items = $data['medicos'];
        $retirarFaltantes = (bool) ($data['retirar_faltantes'] ?? false);
        $diff = $this->calcularDiffMedicos($contratoId, $items, $retirarFaltantes);

        if (! empty($diff['errores'])) {
            return $this->error(implode(' ', $diff['errores']), 422);
        }

        return $this->transaction(function () use ($contrato, $diff) {
            $mapaProductos = ContratoProducto::where('contrato_id', $contrato->id)
                ->pluck('id', 'producto_id');

            foreach ($diff['altas'] as $alta) {
                ContratoMedico::create([
                    'contrato_id' => $contrato->id,
                    'medico_id' => $alta['medico_id'],
                    'contratos_productos_id' => $mapaProductos[$alta['producto_id']],
                    'valor_contrato' => 0,
                    'valor_consulta' => $alta['valor_consulta'],
                    'valor_urgencias' => $alta['valor_urgencias'],
                    'fecha_ingreso' => $alta['fecha_ingreso'],
                    'estado' => 1,
                ]);
            }

            foreach ($diff['cambios'] as $cambio) {
                ContratoMedico::where('id', $cambio['contratos_medicos_id'])->update([
                    'valor_consulta' => $cambio['valor_consulta'],
                    'valor_urgencias' => $cambio['valor_urgencias'],
                    'fecha_ingreso' => $cambio['fecha_ingreso'],
                ]);
            }

            foreach ($diff['retiros'] as $retiro) {
                ContratoMedico::where('id', $retiro['contratos_medicos_id'])->update([
                    'estado' => 0,
                    'fecha_retiro' => now()->toDateString(),
                ]);
            }

            return $this->updated(
                $contrato->load([
                    'sede:id,cod_sede,nom_sede',
                    'medicos.medico:id,name,reg_med',
                    'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
                ]),
                'Médicos del contrato actualizados correctamente'
            );
        });
    }
}
