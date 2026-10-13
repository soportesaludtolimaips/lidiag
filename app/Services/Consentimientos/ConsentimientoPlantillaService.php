<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Services\BaseService;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;

class ConsentimientoPlantillaService extends BaseService
{
    /**
     * Lista las plantillas de consentimiento con filtros por módulo, vigencia y búsqueda.
     *
     * @param  array  $filters  Filtros: config_modulo_id, q, vigentes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Obtiene una plantilla de consentimiento por su identificador con sus relaciones cargadas.
     *
     * @param  int  $id  Identificador de la plantilla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function find(int $id): array
    {
        $plantilla = ConsentimientoPlantilla::query()
            ->with(['producto:id,cod_cups,nom_produc', 'usuarioCrea:id,name'])
            ->findOrFail($id);

        return $this->success($plantilla);
    }

    /**
     * Crea una nueva versión vigente de la plantilla desactivando la versión anterior.
     *
     * @param  array  $data  Datos de la plantilla: config_modulo_id, producto_id, nombre, html
     * @param  int  $userId  Identificador del usuario que crea la plantilla
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Genera una nueva versión de la plantilla a partir de una existente, marcándola como vigente.
     *
     * @param  int  $id  Identificador de la plantilla base
     * @param  array  $data  Nuevos datos de la plantilla: nombre, html
     * @param  int  $userId  Identificador del usuario que crea la nueva versión
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Lista todas las versiones de la plantilla para un producto y módulo específicos.
     *
     * @param  int  $productoId  Identificador del producto
     * @param  int  $moduloId  Identificador del módulo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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
     * Marca una plantilla como vigente, desactivando las demás versiones del mismo producto.
     *
     * @param  int  $id  Identificador de la plantilla a marcar como vigente
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
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

    /**
     * Desactiva una plantilla de consentimiento marcándola como no vigente.
     *
     * @param  int  $id  Identificador de la plantilla a desactivar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function delete(int $id): array
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $p->vigente = false;
        $p->save();

        return $this->deleted();
    }

    /**
     * Genera y envía al navegador una vista previa en PDF de la plantilla de consentimiento.
     *
     * @param  int  $id  Identificador de la plantilla a previsualizar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function previewPdf(int $id): mixed
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $html = '<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px;">'.$p->html.'</div>';

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        $dompdf->stream('plantilla_consentimiento.pdf', ['Attachment' => false]);
    }
}
