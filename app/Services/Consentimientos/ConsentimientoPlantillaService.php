<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;

class ConsentimientoPlantillaService extends BaseService
{
    public function list(array $filters): array
    {
        $moduloId = (int) ($filters['config_modulo_id'] ?? 0);
        $q = trim((string) ($filters['q'] ?? ''));
        $onlyVigentes = ($filters['vigentes'] ?? '1') !== '0';

        $query = ConsentimientoPlantilla::query()
            ->with(['producto:id,cod_cups,nom_produc', 'usuarioCrea:id,name'])
            ->where('config_modulo_id', $moduloId);

        if ($onlyVigentes) {
            $query->where('vigente', true);
        }

        if ($q !== '') {
            $query->whereHas('producto', function ($p) use ($q) {
                $p->where('nom_produc', 'like', "%{$q}%")
                    ->orWhere('cod_cups', 'like', "%{$q}%");
            })->orWhere('nombre', 'like', "%{$q}%");
        }

        return $this->success($query->orderByDesc('id')->get());
    }

    public function find(int $id): array
    {
        $plantilla = ConsentimientoPlantilla::query()
            ->with(['producto:id,cod_cups,nom_produc', 'usuarioCrea:id,name'])
            ->findOrFail($id);

        return $this->success($plantilla);
    }

    public function create(array $data, int $userId): array
    {
        $moduloId = (int) $data['config_modulo_id'];
        $productoId = (int) $data['producto_id'];

        $plantilla = DB::transaction(function () use ($data, $moduloId, $productoId, $userId) {
            $vigente = ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', $productoId)
                ->where('vigente', true)
                ->lockForUpdate()
                ->first();

            $nextVersion = 1;
            if ($vigente) {
                $nextVersion = (int) $vigente->version + 1;
                $vigente->vigente = false;
                $vigente->vigente_key = null;
                $vigente->save();
            }

            return ConsentimientoPlantilla::create([
                'config_modulo_id' => $moduloId,
                'producto_id' => $productoId,
                'usuario_crea_id' => $userId,
                'nombre' => (string) $data['nombre'],
                'html' => (string) $data['html'],
                'version' => $nextVersion,
                'vigente' => true,
                'vigente_key' => 1,
            ]);
        });

        return $this->created($plantilla->load(['producto', 'usuarioCrea']));
    }

    public function update(int $id, array $data, int $userId): array
    {
        $current = ConsentimientoPlantilla::query()->findOrFail($id);
        $moduloId = (int) $current->config_modulo_id;
        $productoId = (int) $current->producto_id;

        $new = DB::transaction(function () use ($data, $moduloId, $productoId, $userId) {
            ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', $productoId)
                ->where('vigente', true)
                ->lockForUpdate()
                ->update(['vigente' => false, 'vigente_key' => null]);

            $maxVersion = (int) ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', $productoId)
                ->max('version');

            return ConsentimientoPlantilla::create([
                'config_modulo_id' => $moduloId,
                'producto_id' => $productoId,
                'usuario_crea_id' => $userId,
                'nombre' => (string) $data['nombre'],
                'html' => (string) $data['html'],
                'version' => $maxVersion + 1,
                'vigente' => true,
                'vigente_key' => 1,
            ]);
        });

        return $this->updated($new->load(['producto', 'usuarioCrea']));
    }

    public function versions(int $productoId, int $moduloId): array
    {
        $items = ConsentimientoPlantilla::query()
            ->with(['usuarioCrea:id,name'])
            ->where('config_modulo_id', $moduloId)
            ->where('producto_id', $productoId)
            ->orderByDesc('version')
            ->get();

        return $this->success($items);
    }

    /**
     * Compara dos versiones (filas) de plantilla y devuelve un diff por líneas.
     * Ambas versiones deben pertenecer al mismo (config_modulo_id, producto_id).
     */
    public function comparar(int $v1Id, int $v2Id): array
    {
        $v1 = ConsentimientoPlantilla::query()->with('usuarioCrea:id,name')->findOrFail($v1Id);
        $v2 = ConsentimientoPlantilla::query()->with('usuarioCrea:id,name')->findOrFail($v2Id);

        if ((int) $v1->config_modulo_id !== (int) $v2->config_modulo_id
            || (int) $v1->producto_id !== (int) $v2->producto_id) {
            return $this->validationError(null, 'Las versiones no corresponden al mismo producto/módulo');
        }

        return $this->success([
            'v1' => [
                'id' => $v1->id,
                'version' => $v1->version,
                'nombre' => $v1->nombre,
                'usuario' => $v1->usuarioCrea?->name,
                'fecha' => $v1->created_at,
            ],
            'v2' => [
                'id' => $v2->id,
                'version' => $v2->version,
                'nombre' => $v2->nombre,
                'usuario' => $v2->usuarioCrea?->name,
                'fecha' => $v2->created_at,
            ],
            'diff' => $this->diffLineas((string) $v1->html, (string) $v2->html),
        ]);
    }

    /**
     * Diff por líneas basado en LCS. Devuelve una lista de segmentos con tipo:
     * 'eq' (igual), 'del' (solo en v1), 'add' (solo en v2).
     *
     * @return array<int, array{tipo: string, texto: string}>
     */
    private function diffLineas(string $a, string $b): array
    {
        $x = preg_split("/\r\n|\n|\r/", $a) ?: [];
        $y = preg_split("/\r\n|\n|\r/", $b) ?: [];
        $n = count($x);
        $m = count($y);

        // Tabla LCS
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $x[$i] === $y[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $out = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($x[$i] === $y[$j]) {
                $out[] = ['tipo' => 'eq', 'texto' => $x[$i]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $out[] = ['tipo' => 'del', 'texto' => $x[$i]];
                $i++;
            } else {
                $out[] = ['tipo' => 'add', 'texto' => $y[$j]];
                $j++;
            }
        }
        while ($i < $n) {
            $out[] = ['tipo' => 'del', 'texto' => $x[$i]];
            $i++;
        }
        while ($j < $m) {
            $out[] = ['tipo' => 'add', 'texto' => $y[$j]];
            $j++;
        }

        return $out;
    }

    public function setVigente(int $id): array
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $moduloId = (int) $p->config_modulo_id;

        DB::transaction(function () use ($moduloId, $p) {
            ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', (int) $p->producto_id)
                ->where('vigente', true)
                ->lockForUpdate()
                ->update(['vigente' => false, 'vigente_key' => null]);

            $p->vigente = true;
            $p->vigente_key = 1;
            $p->save();
        });

        return $this->updated(['ok' => true]);
    }

    public function delete(int $id): array
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $p->vigente = false;
        $p->save();

        return $this->deleted();
    }

    public function previewPdf(int $id): mixed
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $html = '<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px;">'.$p->html.'</div>';

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream('plantilla_consentimiento.pdf');
    }
}
