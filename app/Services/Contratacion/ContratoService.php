<?php

namespace App\Services\Contratacion;

use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoMedico;
use App\Models\Contratacion\ContratoProducto;
use App\Services\BaseService;

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
     * Crea un nuevo contrato dentro de una transacción.
     *
     * @param  array  $data  Datos del contrato: sede_id, fecha_inicio, fecha_fin, observaciones, estado.
     *
     * @returns array Respuesta con el contrato creado (con sede cargada).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function create(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $contrato = Contrato::create([
                'sede_id' => $data['sede_id'],
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
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
            $contrato->update([
                'sede_id' => $data['sede_id'] ?? $contrato->sede_id,
                'fecha_inicio' => $data['fecha_inicio'] ?? $contrato->fecha_inicio,
                'fecha_fin' => $data['fecha_fin'] ?? $contrato->fecha_fin,
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
     * Sincroniza los productos de un contrato: elimina los existentes e inserta los nuevos.
     * Cada producto se resuelve por su código CUPS contra `config_productos`.
     * Operación atómica dentro de una transacción.
     *
     * @param  int  $contratoId  Identificador del contrato cuyos productos se sincronizan.
     * @param  array  $data  Datos enviados desde el frontend o la importación.
     *                       - productos (array): Lista de productos a vincular.
     *                       - productos.*.producto_id (int): Identificación del producto (config_productos).
     *                       - productos.*.valor_contrato (float): Valor contratado para el producto.
     *
     * @returns array Respuesta con el contrato y productos actualizados o mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncProductos(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $productos = $data['productos'];

            // Eliminar primero los médicos vinculados porque referencian contratos_productos
            ContratoMedico::where('contrato_id', $contrato->id)->delete();
            ContratoProducto::where('contrato_id', $contrato->id)->delete();

            foreach ($productos as $item) {
                ContratoProducto::create([
                    'contrato_id' => $contrato->id,
                    'producto_id' => $item['producto_id'],
                    'valor_contrato' => $item['valor_contrato'] ?? 0,
                    'estado' => 1,
                ]);
            }

            return $this->updated(
                $contrato->load(['sede:id,cod_sede,nom_sede', 'productos.producto:id,cod_cups,nom_produc']),
                'Productos del contrato sincronizados correctamente'
            );
        });
    }

    /**
     * Sincroniza los médicos-producto de un contrato: elimina los existentes e inserta los nuevos.
     * Cada item se recibe con `producto_id`; internamente se resuelve al registro
     * correspondiente en `contratos_productos` del mismo contrato.
     * El campo valor_contrato queda en 0 porque la tarifa pactada vive en
     * contratos_productos; a nivel de médico solo se guardan consulta y urgencias.
     * Operación atómica dentro de una transacción.
     *
     * @param  int  $contratoId  Identificador del contrato cuyos médicos se sincronizan.
     * @param  array  $data  Datos enviados desde el frontend o la importación.
     *                       - medicos (array): Lista de vinculaciones médico-producto.
     *                       - medicos.*.medico_id (int): Identificación del médico (users).
     *                       - medicos.*.producto_id (int): Identificación del producto (config_productos).
     *                       - medicos.*.valor_consulta (float): Valor por lectura de consulta externa.
     *                       - medicos.*.valor_urgencias (float): Valor por lectura de urgencias.
     *
     * @returns array Respuesta con el contrato y médicos actualizados o mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncMedicos(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $items = $data['medicos'];

            // Mapa producto_id => id de contratos_productos para este contrato
            $mapaProductos = ContratoProducto::where('contrato_id', $contrato->id)
                ->pluck('id', 'producto_id');

            ContratoMedico::where('contrato_id', $contrato->id)->delete();

            foreach ($items as $item) {
                $contratoProductoId = $mapaProductos[(int) $item['producto_id']] ?? null;

                if (! $contratoProductoId) {
                    abort(422, "El producto {$item['producto_id']} no está configurado en este contrato");
                }

                ContratoMedico::create([
                    'contrato_id' => $contrato->id,
                    'medico_id' => $item['medico_id'],
                    'contratos_productos_id' => $contratoProductoId,
                    'valor_contrato' => 0,
                    'valor_consulta' => $item['valor_consulta'] ?? 0,
                    'valor_urgencias' => $item['valor_urgencias'] ?? 0,
                    'estado' => 1,
                ]);
            }

            return $this->updated(
                $contrato->load([
                    'sede:id,cod_sede,nom_sede',
                    'medicos.medico:id,name,reg_med',
                    'medicos.contratoProducto.producto:id,cod_cups,nom_produc',
                ]),
                'Médicos del contrato sincronizados correctamente'
            );
        });
    }
}
