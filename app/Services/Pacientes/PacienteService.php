<?php

namespace App\Services\Pacientes;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigTipoDocumento;
use App\Models\General\Paciente;
use App\Models\Worklist\WorklistOrden;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión y administración del catálogo de Pacientes en LIDiag.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class PacienteService extends BaseService
{
    /**
     * Lista los pacientes con filtros de búsqueda y adjunta el estado de Worklist de la sede activa.
     *
     * Flujo del proceso:
     * 1. Aplica filtros opcionales de documento, nombre completo y rango de fechas de registro.
     * 2. Ordena por fecha de creación descendente con límite de resultados.
     * 3. Consulta y mapea las órdenes de Worklist activas (PENDIENTE) del día de hoy para la sede.
     *
     * @param  array  $filters  Criterios de búsqueda (bus_documento, bus_nombre, bus_fech_ini, bus_fecha_fin)
     * @param  int  $sedeId  ID de la sede asistencial activa
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function listar(array $filters, int $sedeId): array
    {
        $query = Paciente::with('tipoDocumento:id,codigo,tipo_documento');

        if (! empty($filters['bus_documento'])) {
            $doc = trim($filters['bus_documento']);
            $query->where('num_docu', 'like', "%{$doc}%");
        }

        if (! empty($filters['bus_nombre'])) {
            $nom = trim($filters['bus_nombre']);
            $query->where(function ($q) use ($nom) {
                $q->where('nombres', 'like', "%{$nom}%")
                    ->orWhere('seg_nom', 'like', "%{$nom}%")
                    ->orWhere('pri_ape', 'like', "%{$nom}%")
                    ->orWhere('seg_ape', 'like', "%{$nom}%");
            });
        }

        if (! empty($filters['bus_fech_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $inicio = $filters['bus_fech_ini'].' 00:00:00';
            $fin = $filters['bus_fecha_fin'].' 23:59:59';
            $query->whereBetween('created_at', [$inicio, $fin]);
        }

        $pacientes = $query->orderBy('id', 'desc')->limit(300)->get();

        // Mapear órdenes de Worklist activas para la sede en la fecha de hoy
        $pacienteIds = $pacientes->pluck('id')->all();
        $ordenesActivas = [];

        if (! empty($pacienteIds) && $sedeId > 0) {
            $ordenes = WorklistOrden::with('producto:id,cod_cups,nom_produc')
                ->where('sede_id', $sedeId)
                ->whereIn('paciente_id', $pacienteIds)
                ->where('estado', 'PENDIENTE')
                ->whereDate('created_at', Carbon::today())
                ->get();

            foreach ($ordenes as $ord) {
                $ordenesActivas[$ord->paciente_id] = [
                    'id' => $ord->id,
                    'accession_no' => $ord->accession_no,
                    'modality' => $ord->modality,
                    'station_aet' => $ord->station_aet,
                    'producto' => $ord->producto ? $ord->producto->nom_produc : null,
                    'created_at' => $ord->created_at ? $ord->created_at->format('H:i') : null,
                ];
            }
        }

        foreach ($pacientes as $p) {
            $p->orden_worklist_activa = $ordenesActivas[$p->id] ?? null;
            $p->nombre_completo = trim("{$p->pri_ape} {$p->seg_ape} {$p->nombres} {$p->seg_nom}");
        }

        return $this->success($pacientes);
    }

    /**
     * Registra un nuevo paciente en LIDiag.
     *
     * @param  array  $data  Datos del paciente validados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function store(array $data): array
    {
        $numDocu = trim($data['num_docu']);
        $existente = Paciente::where('num_docu', $numDocu)->first();

        if ($existente) {
            return $this->error("Ya existe un paciente registrado con el documento '{$numDocu}'.", 422, [
                'num_docu' => ['El número de documento ya está en uso.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $paciente = Paciente::create([
                'tipo_documento_id' => $data['tipo_documento_id'] ?? null,
                'num_docu' => $numDocu,
                'nombres' => strtoupper(trim($data['nombres'])),
                'seg_nom' => ! empty($data['seg_nom']) ? strtoupper(trim($data['seg_nom'])) : null,
                'pri_ape' => ! empty($data['pri_ape']) ? strtoupper(trim($data['pri_ape'])) : null,
                'seg_ape' => ! empty($data['seg_ape']) ? strtoupper(trim($data['seg_ape'])) : null,
                'direccion' => $data['direccion'] ?? null,
                'email' => $data['email'] ?? null,
                'sexo' => strtoupper(trim($data['sexo'] ?? 'M')),
                'fec_naci' => $data['fec_naci'] ?? null,
                'tel' => $data['tel'] ?? null,
                'cel' => $data['cel'] ?? null,
            ]);

            DB::commit();

            $paciente->load('tipoDocumento:id,codigo,tipo_documento');
            $paciente->nombre_completo = trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}");

            return $this->success($paciente, 'Paciente registrado exitosamente.', 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error registrando paciente: '.$e->getMessage());

            return $this->error('No fue posible registrar el paciente. '.$e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el detalle de un paciente específico.
     *
     * @param  int  $id  ID del paciente
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function show(int $id): array
    {
        $paciente = Paciente::with('tipoDocumento:id,codigo,tipo_documento')->find($id);

        if (! $paciente) {
            return $this->error('Paciente no encontrado.', 404);
        }

        $paciente->nombre_completo = trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}");

        return $this->success($paciente);
    }

    /**
     * Actualiza la información de un paciente.
     *
     * @param  array  $data  Datos validados
     * @param  int  $id  ID del paciente a actualizar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function update(array $data, int $id): array
    {
        $paciente = Paciente::find($id);
        if (! $paciente) {
            return $this->error('Paciente no encontrado.', 404);
        }

        $numDocu = trim($data['num_docu']);
        $duplicado = Paciente::where('num_docu', $numDocu)
            ->where('id', '!=', $id)
            ->first();

        if ($duplicado) {
            return $this->error("El número de documento '{$numDocu}' ya pertenece a otro paciente.", 422, [
                'num_docu' => ['El número de documento ya está en uso por otro paciente.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $paciente->update([
                'tipo_documento_id' => $data['tipo_documento_id'] ?? null,
                'num_docu' => $numDocu,
                'nombres' => strtoupper(trim($data['nombres'])),
                'seg_nom' => ! empty($data['seg_nom']) ? strtoupper(trim($data['seg_nom'])) : null,
                'pri_ape' => ! empty($data['pri_ape']) ? strtoupper(trim($data['pri_ape'])) : null,
                'seg_ape' => ! empty($data['seg_ape']) ? strtoupper(trim($data['seg_ape'])) : null,
                'direccion' => $data['direccion'] ?? null,
                'email' => $data['email'] ?? null,
                'sexo' => strtoupper(trim($data['sexo'] ?? 'M')),
                'fec_naci' => $data['fec_naci'] ?? null,
                'tel' => $data['tel'] ?? null,
                'cel' => $data['cel'] ?? null,
            ]);

            DB::commit();

            $paciente->load('tipoDocumento:id,codigo,tipo_documento');
            $paciente->nombre_completo = trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}");

            return $this->success($paciente, 'Paciente actualizado exitosamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando paciente: '.$e->getMessage());

            return $this->error('No fue posible actualizar el paciente. '.$e->getMessage(), 500);
        }
    }

    /**
     * Elimina un paciente si no tiene estudios o historial clínico asociado.
     *
     * @param  int  $id  ID del paciente
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function destroy(int $id): array
    {
        $paciente = Paciente::withCount(['estudios', 'ecografias', 'worklistOrdenes'])->find($id);

        if (! $paciente) {
            return $this->error('Paciente no encontrado.', 404);
        }

        if ($paciente->estudios_count > 0 || $paciente->ecografias_count > 0 || $paciente->worklist_ordenes_count > 0) {
            return $this->error('No es posible eliminar el paciente porque cuenta con estudios o registros clínicos asociados.', 422);
        }

        try {
            DB::beginTransaction();
            $paciente->delete();
            DB::commit();

            return $this->success(null, 'Paciente eliminado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error('Error eliminando paciente: '.$e->getMessage(), 500);
        }
    }

    /**
     * Retorna el catálogo de tipos de documento y productos activos para procedimientos de Rayos X.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function obtenerCatalogosAuxiliares(): array
    {
        $tiposDocumento = ConfigTipoDocumento::where('estado', true)
            ->select('id', 'codigo', 'tipo_documento')
            ->orderBy('codigo')
            ->get();

        // Productos habilitados para Rayos X (por palabras clave o activos)
        $productosRayosX = ConfigProducto::where('estado', true)
            ->where(function ($q) {
                $q->where('nom_produc', 'like', '%RAYOS%')
                    ->orWhere('nom_produc', 'like', '%RX%')
                    ->orWhere('nom_produc', 'like', '%RADIOGRAFIA%');
            })
            ->select('id', 'cod_cups', 'nom_produc')
            ->orderBy('nom_produc')
            ->get();

        // Si no hay productos con esas palabras clave, traer catálogo general activo
        if ($productosRayosX->isEmpty()) {
            $productosRayosX = ConfigProducto::where('estado', true)
                ->select('id', 'cod_cups', 'nom_produc')
                ->orderBy('nom_produc')
                ->limit(100)
                ->get();
        }

        return $this->success([
            'tipos_documento' => $tiposDocumento,
            'productos_rayos_x' => $productosRayosX,
        ]);
    }
}
