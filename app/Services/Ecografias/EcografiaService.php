<?php

namespace App\Services\Ecografias;

use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\General\Paciente;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class EcografiaService extends BaseService
{
    public function all(array $filters = []): array
    {
        $query = Ecografia::with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico']);

        if (! empty($filters['bus_nom_num_docu'])) {
            $query->whereHas('paciente', function ($q) use ($filters) {
                $q->where('num_docu', 'like', '%'.$filters['bus_nom_num_docu'].'%')
                    ->orWhere('nombres', 'like', '%'.$filters['bus_nom_num_docu'].'%')
                    ->orWhere('pri_ape', 'like', '%'.$filters['bus_nom_num_docu'].'%');
            });
        }

        if (! empty($filters['bus_fehc_ini']) && ! empty($filters['bus_fehc_fin'])) {
            $query->whereBetween('created_at', [
                $filters['bus_fehc_ini'].' 00:00:00',
                $filters['bus_fehc_fin'].' 23:59:59',
            ]);
        }

        $ecografias = $query->get();

        $ecografias->transform(function ($eco) {
            if ($eco->relationLoaded('paciente') && $eco->paciente) {
                $eco->paciente->num_docu = maskData($eco->paciente->num_docu ?? '', 'document');
                $eco->paciente->direccion = maskData($eco->paciente->direccion ?? '', 'address');
                $eco->paciente->tel = maskData($eco->paciente->tel ?? '', 'phone');
                $eco->paciente->cel = maskData($eco->paciente->cel ?? '', 'phone');
            }

            return $eco;
        });

        return $this->success($ecografias);
    }

    public function filtarEcografias(array $filters = []): array
    {
        $query = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.fechor_lectura',
            'ep.estado_envio_sahi',
            'ep.fechor_envio_sahi',
            'ep.sahi_esquema_149',
            'ep.sahi_esquema_150',
            'ep.error_envio_sahi',
            'p.nom_produc',
            'p.val_produc as ValProduc'
        )->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id');

        if (! empty($filters['bus_nom_num_docu'])) {
            $valor = $filters['bus_nom_num_docu'];
            $query->where(function ($q) use ($valor) {
                $q->where('ecografias.atencion_id', 'like', "%{$valor}%")
                    ->orWhereHas('paciente', function ($p) use ($valor) {
                        $p->where('num_docu', 'like', "%{$valor}%")
                            ->orWhere('nombres', 'like', "%{$valor}%")
                            ->orWhere('pri_ape', 'like', "%{$valor}%")
                            ->orWhere('seg_ape', 'like', "%{$valor}%");
                    });
            });
        }

        if (! empty($filters['bus_fehc_ini']) && ! empty($filters['bus_fehc_fin'])) {
            $query->whereBetween('ecografias.created_at', [
                "{$filters['bus_fehc_ini']} 00:00:00",
                "{$filters['bus_fehc_fin']} 23:59:59",
            ]);
        }

        return $this->success($query->get());
    }

    public function find(int $id): array
    {
        $ecografia = Ecografia::with(['paciente', 'usuarioRegistrador', 'adminSalud'])->find($id);

        if (! $ecografia) {
            return $this->notFound('Ecografía no encontrada');
        }

        return $this->success($ecografia);
    }

    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $paciente = Paciente::updateOrCreate(
                ['num_docu' => $data['num_docu']],
                [
                    'tipo_documento_id' => $data['tipo_documento_id'] ?? null,
                    'num_docu' => $data['num_docu'],
                    'nombres' => $data['nombres'],
                    'seg_nom' => $data['seg_nom'] ?? null,
                    'pri_ape' => $data['pri_ape'] ?? null,
                    'seg_ape' => $data['seg_ape'] ?? null,
                    'sexo' => $data['sexo'] ?? null,
                    'fec_naci' => $data['fec_naci'] ?? null,
                    'direccion' => $data['direccion'] ?? null,
                    'tel' => $data['tel'] ?? null,
                    'cel' => $data['cel'] ?? null,
                    'email' => $data['email'] ?? null,
                ]
            );

            $adminSalud = ConfigAdminSalud::firstOrCreate(
                ['nom_admin_salud' => $data['admin_salud'] ?? ''],
                ['estado' => true]
            );

            $ecografia = Ecografia::create([
                'medico_id' => $data['medico_id'],
                'paciente_id' => $paciente->id,
                'usua_regis_id' => auth()->id(),
                'admin_salud_id' => $adminSalud->id ?? null,
                'atencion_id' => $data['atencion_id'],
                'fec_cita_sahi' => $data['fec_cita_sahi'] ?? null,
                'fec_regis_cita_sahi' => $data['fec_regis_cita_sahi'] ?? null,
                'contrato' => $data['contrato'] ?? null,
                'plan' => $data['plan'] ?? null,
                'quien_ordena' => $data['quien_ordena'] ?? null,
                'estado' => 'EN PROCESO',
            ]);

            foreach ($data['productosAgenda'] as $producto) {
                $productoExistente = ConfigProducto::firstOrCreate(
                    ['id' => $producto['IdProducto']],
                    [
                        'nom_produc' => $producto['NomProducto'],
                        'cod_cups' => $producto['CodProducto'],
                        'val_produc' => $producto['ValProduc'],
                    ]
                );

                EcografiaProducto::create([
                    'usua_regis_id' => auth()->id(),
                    'ecografia_id' => $ecografia->id,
                    'producto_id' => $productoExistente->id,
                    'lectura' => $producto['lectura'] ?? null,
                    'opinion' => $producto['opinion'] ?? null,
                    'estado' => 'EN PROCESO',
                ]);
            }

            return $this->created($ecografia, 'Ecografía y productos guardados correctamente.');
        });
    }

    public function update(int $id, array $data): array
    {
        $ecografia = Ecografia::find($id);

        if (! $ecografia) {
            return $this->notFound('Ecografía no encontrada');
        }

        $ecografia->update($data);

        return $this->updated($ecografia);
    }

    public function delete(int $id): array
    {
        $ecografia = Ecografia::find($id);

        if (! $ecografia) {
            return $this->notFound('Ecografía no encontrada');
        }

        $ecografia->delete();

        return $this->deleted('Ecografía eliminada correctamente');
    }

    public function anular(int $id, string $motivo): array
    {
        $ecografia = Ecografia::find($id);

        if (! $ecografia) {
            return $this->notFound('Ecografía no encontrada');
        }

        $ecografia->estado = 'ANULADO';
        $ecografia->motivo_anula = $motivo;
        $ecografia->usua_anula_id = auth()->id();
        $ecografia->fechor_anula = now();
        $ecografia->save();

        return $this->success(null, 'Ecografía anulada correctamente');
    }
}
