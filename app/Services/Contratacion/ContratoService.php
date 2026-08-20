<?php

namespace App\Services\Contratacion;

use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoDetalle;
use App\Services\BaseService;

class ContratoService extends BaseService
{
    /**
     * Lista contratos con filtros opcionales y paginación.
     * Incluye relaciones: sede, detalles.medico, detalles.producto.
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
                'detalles.medico:id,name,reg_med',
                'detalles.producto:id,cod_cups,nom_produc',
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
            'detalles.medico:id,name,reg_med',
            'detalles.producto:id,cod_cups,nom_produc',
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
     * Elimina un contrato y todos sus detalles dentro de una transacción.
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
            $contrato->detalles()->delete();
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
     * Agrega un detalle individual (médico + producto) a un contrato.
     *
     * @param  int  $contratoId  Identificador del contrato padre.
     * @param  array  $data  Datos del detalle: medico_id, producto_id, valores.
     *
     * @returns array Respuesta con el detalle creado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function addDetalle(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $detalle = ContratoDetalle::create([
                'contrato_id' => $contrato->id,
                'medico_id' => $data['medico_id'],
                'producto_id' => $data['producto_id'],
                'valor_contrato' => $data['valor_contrato'] ?? 0,
                'valor_consulta' => $data['valor_consulta'] ?? 0,
                'valor_urgencias' => $data['valor_urgencias'] ?? 0,
                'estado' => $data['estado'] ?? 1,
            ]);

            return $this->created($detalle->load([
                'medico:id,name,reg_med',
                'producto:id,cod_cups,nom_produc',
            ]));
        });
    }

    /**
     * Actualiza un detalle existente de un contrato.
     *
     * @param  int  $detalleId  Identificador del detalle a actualizar.
     * @param  array  $data  Datos a actualizar (campos opcionales).
     *
     * @returns array Respuesta con el detalle actualizado o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function updateDetalle(int $detalleId, array $data): array
    {
        $detalle = ContratoDetalle::find($detalleId);

        if (! $detalle) {
            return $this->notFound('Detalle no encontrado');
        }

        return $this->transaction(function () use ($detalle, $data) {
            $detalle->update([
                'medico_id' => $data['medico_id'] ?? $detalle->medico_id,
                'producto_id' => $data['producto_id'] ?? $detalle->producto_id,
                'valor_contrato' => $data['valor_contrato'] ?? $detalle->valor_contrato,
                'valor_consulta' => $data['valor_consulta'] ?? $detalle->valor_consulta,
                'valor_urgencias' => $data['valor_urgencias'] ?? $detalle->valor_urgencias,
                'estado' => $data['estado'] ?? $detalle->estado,
            ]);

            return $this->updated($detalle->load([
                'medico:id,name,reg_med',
                'producto:id,cod_cups,nom_produc',
            ]));
        });
    }

    /**
     * Elimina un detalle individual de un contrato.
     *
     * @param  int  $detalleId  Identificador del detalle a eliminar.
     *
     * @returns array Respuesta de confirmación o error 404.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function deleteDetalle(int $detalleId): array
    {
        $detalle = ContratoDetalle::find($detalleId);

        if (! $detalle) {
            return $this->notFound('Detalle no encontrado');
        }

        return $this->transaction(function () use ($detalle) {
            $detalle->delete();

            return $this->deleted('Detalle eliminado correctamente');
        });
    }

    /**
     * Sincroniza los detalles de un contrato: elimina los existentes e inserta los nuevos.
     * Operación atómica dentro de una transacción.
     *
     * @param  int  $contratoId  Identificador del contrato cuyos detalles se van a sincronizar.
     * @param  array  $data  Datos enviados desde el frontend.
     *                       - detalles (array): Lista de productos con sus valores.
     *                       - detalles.*.medico_id (int): Identificación del médico.
     *                       - detalles.*.producto_id (int): Identificación del producto (CUPS).
     *                       - detalles.*.valor_contrato (float): Valor contratado por lectura.
     *                       - detalles.*.valor_consulta (float): Valor por lectura de consulta externa.
     *                       - detalles.*.valor_urgencias (float): Valor por lectura de urgencias.
     *
     * @returns array Respuesta con el contrato actualizado o mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-20
     */
    public function syncDetalles(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $detalles = $data['detalles'];

            ContratoDetalle::where('contrato_id', $contrato->id)->delete();

            foreach ($detalles as $item) {
                ContratoDetalle::create([
                    'contrato_id' => $contrato->id,
                    'medico_id' => $item['medico_id'],
                    'producto_id' => $item['producto_id'],
                    'valor_contrato' => $item['valor_contrato'] ?? 0,
                    'valor_consulta' => $item['valor_consulta'] ?? 0,
                    'valor_urgencias' => $item['valor_urgencias'] ?? 0,
                    'estado' => 1,
                ]);
            }

            return $this->updated(
                $contrato->load([
                    'sede:id,cod_sede,nom_sede',
                    'detalles.medico:id,name,reg_med',
                    'detalles.producto:id,cod_cups,nom_produc',
                ]),
                'Detalles sincronizados correctamente'
            );
        });
    }
}
