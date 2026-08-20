<?php

namespace App\Services\Contratacion;

use App\Models\Contratacion\Contrato;
use App\Models\Contratacion\ContratoDetalle;
use App\Services\BaseService;

class ContratoService extends BaseService
{
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
     *                       - medico_id (int): Identificación del médico asociado a todos los detalles.
     *                       - detalles (array): Lista de productos con sus valores.
     *                       - producto_id (int): Identificación del producto (CUPS).
     *                       - valor_contrato (float): Valor contratado por lectura con la sede.
     *                       - valor_consulta (float): Valor a pagar por lectura de consulta externa.
     *                       - valor_urgencias (float): Valor a pagar por lectura de urgencias.
     *
     * @returns array Respuesta con el contrato actualizado o mensaje de error.
     *
     * @author  Jhon Javer Lozano Arce
     *
     * @date    2026-08-20
     */
    public function syncDetalles(int $contratoId, array $data): array
    {
        $contrato = Contrato::find($contratoId);

        if (! $contrato) {
            return $this->notFound('Contrato no encontrado');
        }

        return $this->transaction(function () use ($contrato, $data) {
            $medicoId = $data['medico_id'];
            $detalles = $data['detalles'];

            // Eliminar todos los detalles existentes del contrato
            ContratoDetalle::where('contrato_id', $contrato->id)->delete();

            // Insertar los nuevos detalles
            foreach ($detalles as $item) {
                ContratoDetalle::create([
                    'contrato_id' => $contrato->id,
                    'medico_id' => $medicoId,
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
