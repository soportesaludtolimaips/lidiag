<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigPlantilla;
use App\Models\Configuracion\ConfigPlantillaTipo;
use App\Services\BaseService;

class ConfigPlantillaService extends BaseService
{
    /**
     * Obtiene todas las plantillas con sus relaciones de tipo y médico.
     *
     * @returns array Arreglo con la lista de plantillas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])->get()
        );
    }

    /**
     * Busca una plantilla por su id incluyendo sus relaciones.
     *
     * @param  int  $id  Identificador de la plantilla a buscar.
     *
     * @returns array Arreglo con los datos de la plantilla o un error de no encontrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConfigPlantilla::with(['plantillaTipo', 'medico'])->find($id);

        if (! $item) {
            return $this->notFound('Plantilla no encontrada');
        }

        return $this->success($item);
    }

    /**
     * Obtiene las plantillas activas con sus relaciones de tipo y médico.
     *
     * @returns array Arreglo con la lista de plantillas activas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivos(): array
    {
        return $this->success(
            ConfigPlantilla::with(['plantillaTipo', 'medico'])
                ->where('estado', true)
                ->get()
        );
    }

    /**
     * Obtiene las plantillas activas para un tipo y médico dados.
     * Si no se encuentran plantillas asignadas directamente a ese tipo para el médico,
     * busca por coincidencia en tipos de nombres equivalentes o plantillas generales.
     *
     * @param  int  $tipoId  Identificador del tipo de plantilla.
     * @param  int  $medicoId  Identificador del médico.
     *
     * @returns array Arreglo con la lista de plantillas activas del médico para ese tipo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivosPorTipo(int $tipoId, int $medicoId): array
    {
        $queryBase = ConfigPlantilla::with(['plantillaTipo', 'medico'])
            ->where('estado', true);

        // 1. Búsqueda directa por el tipo y médico solicitados
        if ($medicoId > 0) {
            $plantillas = (clone $queryBase)
                ->where('plantilla_tipo_id', $tipoId)
                ->where('medico_id', $medicoId)
                ->get();

            if ($plantillas->isNotEmpty()) {
                return $this->success($plantillas);
            }

            // 2. Fallback inteligente: buscar si el médico tiene plantillas en un tipo con nombre equivalente
            $tipo = ConfigPlantillaTipo::find($tipoId);
            if ($tipo && ! empty($tipo->nom_tipo)) {
                $nombreNormalizado = trim(preg_replace('/\b(ECOGRAFIA|ECOGRAFÍA|DE|DEL|LA|EL|LOS|LAS)\b/ui', '', $tipo->nom_tipo));
                $palabrasClave = array_values(array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $nombreNormalizado)), function ($w) {
                    return mb_strlen(trim($w)) >= 4;
                }));

                if (! empty($palabrasClave)) {
                    $tiposIdsEquivalentes = ConfigPlantillaTipo::where('estado', true)
                        ->where(function ($q) use ($palabrasClave) {
                            foreach ($palabrasClave as $palabra) {
                                $q->orWhere('nom_tipo', 'LIKE', '%'.$palabra.'%');
                            }
                        })
                        ->pluck('id');

                    $plantillasEquivalentes = (clone $queryBase)
                        ->whereIn('plantilla_tipo_id', $tiposIdsEquivalentes)
                        ->where('medico_id', $medicoId)
                        ->get();

                    if ($plantillasEquivalentes->isNotEmpty()) {
                        return $this->success($plantillasEquivalentes);
                    }
                }
            }

            // 3. Fallback: buscar plantillas del tipo sin médico asignado (plantillas generales/globales)
            $plantillasGenerales = (clone $queryBase)
                ->where('plantilla_tipo_id', $tipoId)
                ->where(function ($q) {
                    $q->whereNull('medico_id')->orWhere('medico_id', 0);
                })
                ->get();

            if ($plantillasGenerales->isNotEmpty()) {
                return $this->success($plantillasGenerales);
            }
        }

        // Si no hay médico o no se encontraron bajo el médico, retornar las del tipo
        $plantillasTipo = (clone $queryBase)
            ->where('plantilla_tipo_id', $tipoId)
            ->get();

        return $this->success($plantillasTipo);
    }

    /**
     * Crea una nueva plantilla y registra la auditoría correspondiente.
     *
     * @param  array  $data  Datos de la plantilla a crear.
     *
     * @returns array Arreglo con la plantilla creada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data): array
    {
        $item = ConfigPlantilla::create($data);
        AuditLogger::crear($item, 'Plantilla creada: '.($data['nom_plantilla'] ?? ''));

        return $this->created($item->load(['plantillaTipo', 'medico']));
    }

    /**
     * Actualiza una plantilla existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador de la plantilla a actualizar.
     * @param  array  $data  Datos de la plantilla a actualizar.
     *
     * @returns array Arreglo con la plantilla actualizada.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(int $id, array $data): array
    {
        $item = ConfigPlantilla::findOrFail($id);
        $item->update($data);
        AuditLogger::actualizar($item, $item->getOriginal(), 'Plantilla actualizada: '.$item->nom_plantilla);

        return $this->updated($item->load(['plantillaTipo', 'medico']));
    }

    /**
     * Elimina una plantilla existente y registra la auditoría correspondiente.
     *
     * @param  int  $id  Identificador de la plantilla a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigPlantilla::findOrFail($id);
        $item->delete();
        AuditLogger::eliminar($item, 'Plantilla eliminada: '.$item->nom_plantilla);

        return $this->deleted();
    }
}
