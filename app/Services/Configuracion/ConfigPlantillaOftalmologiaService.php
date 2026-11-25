<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigPlantillaOftalmologia;
use App\Services\BaseService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la gestión de plantillas clínicas de Oftalmología en el módulo de Configuración.
 *
 * Flujo del proceso:
 * 1. Lista las plantillas activas segmentadas por sección (EXAMEN_FISICO, CONCEPTO_PLAN, PROCEDIMIENTOS, MEDICAMENTOS, ANAMNESIS).
 * 2. Soporta plantillas personales del médico o institucionales (medico_id null).
 * 3. Provee CRUD transaccional con auditoría de excepciones.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class ConfigPlantillaOftalmologiaService extends BaseService
{
    /**
     * Lista todas las plantillas registradas (con filtros opcionales de búsqueda).
     */
    public function all(array $filtros = []): array
    {
        try {
            $query = ConfigPlantillaOftalmologia::with(['medico:id,name', 'sede:id,nom_sede'])
                ->orderBy('seccion')
                ->orderBy('titulo');

            if (! empty($filtros['seccion'])) {
                $query->where('seccion', $filtros['seccion']);
            }

            if (! empty($filtros['medico_id'])) {
                $query->where(function ($q) use ($filtros) {
                    $q->where('medico_id', $filtros['medico_id'])
                        ->orWhereNull('medico_id');
                });
            }

            if (isset($filtros['activo'])) {
                $query->where('activo', (bool) $filtros['activo']);
            }

            $plantillas = $query->get();

            return $this->success($plantillas, 'Plantillas de oftalmología obtenidas correctamente.');
        } catch (Exception $e) {
            Log::error('Error al listar plantillas de oftalmología: '.$e->getMessage());

            return $this->error('Error al obtener plantillas de oftalmología.', 500);
        }
    }

    /**
     * Lista plantillas activas disponibles para una sección específica y médico.
     */
    public function listarPorSeccion(string $seccion, ?int $medicoId = null): array
    {
        try {
            $plantillas = ConfigPlantillaOftalmologia::query()
                ->where('activo', true)
                ->where('seccion', $seccion)
                ->where(function ($q) use ($medicoId) {
                    if ($medicoId) {
                        $q->where('medico_id', $medicoId)->orWhereNull('medico_id');
                    } else {
                        $q->whereNull('medico_id');
                    }
                })
                ->orderBy('titulo')
                ->get();

            return $this->success($plantillas, 'Plantillas filtradas correctamente.');
        } catch (Exception $e) {
            Log::error('Error al listar plantillas de oftalmología por sección: '.$e->getMessage());

            return $this->error('Error al obtener plantillas por sección.', 500);
        }
    }

    /**
     * Crea una nueva plantilla de oftalmología.
     */
    public function crear(array $data, ?int $usuarioId = null): array
    {
        DB::beginTransaction();
        try {
            if ($usuarioId && empty($data['es_institucional'])) {
                $data['medico_id'] = $usuarioId;
            } else {
                $data['medico_id'] = null;
            }

            $plantilla = ConfigPlantillaOftalmologia::create([
                'medico_id' => $data['medico_id'] ?? null,
                'sede_id' => $data['sede_id'] ?? null,
                'seccion' => $data['seccion'],
                'titulo' => $data['titulo'],
                'contenido' => $data['contenido'],
                'activo' => $data['activo'] ?? true,
            ]);

            DB::commit();

            return $this->success($plantilla, 'Plantilla de oftalmología creada exitosamente.', 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear plantilla de oftalmología: '.$e->getMessage());

            return $this->error('Error al registrar la plantilla de oftalmología.', 500);
        }
    }

    /**
     * Actualiza una plantilla existente.
     */
    public function actualizar(int $id, array $data): array
    {
        DB::beginTransaction();
        try {
            $plantilla = ConfigPlantillaOftalmologia::find($id);
            if (! $plantilla) {
                DB::rollBack();

                return $this->error('Plantilla de oftalmología no encontrada.', 404);
            }

            $plantilla->update([
                'seccion' => $data['seccion'] ?? $plantilla->seccion,
                'titulo' => $data['titulo'] ?? $plantilla->titulo,
                'contenido' => $data['contenido'] ?? $plantilla->contenido,
                'sede_id' => array_key_exists('sede_id', $data) ? $data['sede_id'] : $plantilla->sede_id,
                'activo' => array_key_exists('activo', $data) ? (bool) $data['activo'] : $plantilla->activo,
            ]);

            DB::commit();

            return $this->success($plantilla, 'Plantilla de oftalmología actualizada exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar plantilla de oftalmología: '.$e->getMessage());

            return $this->error('Error al actualizar la plantilla de oftalmología.', 500);
        }
    }

    /**
     * Elimina una plantilla.
     */
    public function eliminar(int $id): array
    {
        DB::beginTransaction();
        try {
            $plantilla = ConfigPlantillaOftalmologia::find($id);
            if (! $plantilla) {
                DB::rollBack();

                return $this->error('Plantilla de oftalmología no encontrada.', 404);
            }

            $plantilla->delete();
            DB::commit();

            return $this->success(null, 'Plantilla eliminada exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar plantilla de oftalmología: '.$e->getMessage());

            return $this->error('Error al eliminar la plantilla de oftalmología.', 500);
        }
    }
}
