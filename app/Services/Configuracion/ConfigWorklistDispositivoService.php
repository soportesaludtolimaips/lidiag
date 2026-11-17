<?php

namespace App\Services\Configuracion;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigWorklistDispositivo;
use App\Services\BaseService;
use Exception;
use Illuminate\Support\Facades\DB;

class ConfigWorklistDispositivoService extends BaseService
{
    /**
     * Obtiene el listado de dispositivos filtrados opcionalmente por sede y módulo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function listarPorSede(?int $sedeId = null, ?int $moduloId = null): array
    {
        $query = ConfigWorklistDispositivo::with(['sede:id,nom_sede', 'modulo:id,modulo'])
            ->porSede($sedeId);

        if (! empty($moduloId)) {
            $query->where('modulo_id', $moduloId);
        }

        $dispositivos = $query->orderBy('nombre')->get();

        return $this->success($dispositivos);
    }

    /**
     * Busca un dispositivo por su ID.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function find(int $id): array
    {
        $dispositivo = ConfigWorklistDispositivo::with(['sede', 'modulo'])->find($id);

        if (! $dispositivo) {
            return $this->notFound('Dispositivo no encontrado.');
        }

        return $this->success($dispositivo);
    }

    /**
     * Registra un nuevo dispositivo de flujo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function create(array $data): array
    {
        try {
            DB::beginTransaction();

            $dispositivo = ConfigWorklistDispositivo::create($data);
            AuditLogger::crear($dispositivo, 'Dispositivo configurado: '.$dispositivo->nombre);

            DB::commit();

            return $this->created($dispositivo->load(['sede', 'modulo']));
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error('Error al registrar el dispositivo: '.$e->getMessage(), 500);
        }
    }

    /**
     * Actualiza un dispositivo existente.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function update(int $id, array $data): array
    {
        $dispositivo = ConfigWorklistDispositivo::find($id);

        if (! $dispositivo) {
            return $this->notFound('Dispositivo no encontrado.');
        }

        try {
            DB::beginTransaction();

            $dispositivo->update($data);
            AuditLogger::modificar($dispositivo, 'Dispositivo actualizado: '.$dispositivo->nombre);

            DB::commit();

            return $this->success($dispositivo->load(['sede', 'modulo']));
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error('Error al actualizar el dispositivo: '.$e->getMessage(), 500);
        }
    }

    /**
     * Alterna el estado activo/inactivo de un dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function toggleEstado(int $id): array
    {
        $dispositivo = ConfigWorklistDispositivo::find($id);

        if (! $dispositivo) {
            return $this->notFound('Dispositivo no encontrado.');
        }

        $dispositivo->estado = ! $dispositivo->estado;
        $dispositivo->save();

        AuditLogger::modificar($dispositivo, 'Cambio de estado dispositivo: '.($dispositivo->estado ? 'Activo' : 'Inactivo'));

        return $this->success($dispositivo);
    }

    /**
     * Elimina un dispositivo.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function destroy(int $id): array
    {
        $dispositivo = ConfigWorklistDispositivo::find($id);

        if (! $dispositivo) {
            return $this->notFound('Dispositivo no encontrado.');
        }

        $dispositivo->delete();
        AuditLogger::eliminar($dispositivo, 'Dispositivo eliminado: '.$dispositivo->nombre);

        return $this->success(null, 'Dispositivo eliminado con éxito.');
    }
}
