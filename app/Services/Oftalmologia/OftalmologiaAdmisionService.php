<?php

namespace App\Services\Oftalmologia;

use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigTipoRecaudo;
use App\Models\General\Paciente;
use App\Models\Oftalmologia\OftalmologiaAtencion;
use App\Services\BaseService;
use App\Services\Interfaces\SAHIOftalmologiaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la gestión de admisiones en el módulo de Oftalmología.
 *
 * Flujo del proceso:
 * 1. Resuelve o crea el paciente en LIDiag mediante búsqueda por documento.
 * 2. Determina el tipo de recaudo (Particular, Cortesía o Institucional SAHI).
 * 3. Valida y asigna el identificador de atención (atencion_id) según la sede y recaudo.
 * 4. Gestiona la admisión directa, anulación y listado de pacientes en sala/espera.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-19
 */
class OftalmologiaAdmisionService extends BaseService
{
    public function __construct(
        protected SAHIOftalmologiaService $sahiOftalmologiaService
    ) {}

    /**
     * Lista las atenciones admisionadas con filtros y paginación.
     *
     * @param  array<string, mixed>  $filtros
     * @return array<string, mixed>
     */
    public function listarAtenciones(array $filtros): array
    {
        try {
            $query = OftalmologiaAtencion::query()
                ->with([
                    'sede:id,nom_sede',
                    'paciente:id,num_docu,nombres,pri_ape,seg_ape,fec_naci,sexo,cel,tel',
                    'medico:id,name',
                    'usuarioRegistrador:id,name',
                    'adminSalud:id,nom_admin',
                    'tipoRecaudo:id,nombre,codigo,envia_sahi',
                    'consulta:id,atencion_id,estado_consulta,fechor_finalizacion',
                ]);

            if (! empty($filtros['sede_id'])) {
                $query->where('sede_id', (int) $filtros['sede_id']);
            }

            if (! empty($filtros['estado'])) {
                $query->where('estado', trim((string) $filtros['estado']));
            }

            if (! empty($filtros['fecha_ini']) && ! empty($filtros['fecha_fin'])) {
                $query->whereBetween('created_at', [
                    Carbon::parse($filtros['fecha_ini'])->startOfDay(),
                    Carbon::parse($filtros['fecha_fin'])->endOfDay(),
                ]);
            }

            if (! empty($filtros['busqueda'])) {
                $termino = trim((string) $filtros['busqueda']);
                $query->where(function (Builder $q) use ($termino) {
                    $q->where('atencion_id', 'LIKE', "%{$termino}%")
                        ->orWhereHas('paciente', function (Builder $qp) use ($termino) {
                            $qp->where('num_docu', 'LIKE', "%{$termino}%")
                                ->orWhere('nombres', 'LIKE', "%{$termino}%")
                                ->orWhere('pri_ape', 'LIKE', "%{$termino}%")
                                ->orWhere('seg_ape', 'LIKE', "%{$termino}%");
                        });
                });
            }

            $perPage = ! empty($filtros['per_page']) ? max(1, min((int) $filtros['per_page'], 100)) : 15;
            $paginator = $query->orderByDesc('id')->paginate($perPage);

            return $this->success($paginator, 'Atenciones de oftalmología obtenidas correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error listando atenciones de oftalmología', [
                'filtros' => $filtros,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible consultar las atenciones de oftalmología.', 500);
        }
    }

    /**
     * Registra una nueva admisión de Oftalmología en LIDiag.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function admisionar(array $data, int $usuarioId): array
    {
        try {
            return DB::transaction(function () use ($data, $usuarioId) {
                $sedeId = (int) $data['sede_id'];
                $sede = ConfigSede::findOrFail($sedeId);

                // 1. Resolver Tipo de Recaudo
                $tipoRecaudoId = ! empty($data['tipo_recaudo_id']) ? (int) $data['tipo_recaudo_id'] : null;
                $tipoRecaudo = $tipoRecaudoId ? ConfigTipoRecaudo::find($tipoRecaudoId) : null;
                $esRecaudoSahi = $tipoRecaudo ? (bool) $tipoRecaudo->envia_sahi : true;

                // 2. Resolver atencion_id
                $atencionId = trim((string) ($data['atencion_id'] ?? ''));
                if (! $this->sahiOftalmologiaService->tieneInterfazActiva($sede) || ! $esRecaudoSahi) {
                    if (empty($atencionId) || in_array(strtoupper($atencionId), ['PARTICULAR', 'CORTESIA'], true)) {
                        $codigo = $tipoRecaudo?->codigo ? strtoupper($tipoRecaudo->codigo) : 'PARTICULAR';
                        $atencionId = "{$codigo}-".date('YmdHis').'-'.random_int(100, 999);
                    }
                } else {
                    if (empty($atencionId)) {
                        return $this->error('Para atenciones institucionales por SAHI el número de atención es obligatorio.', 422);
                    }
                    // Validar si ya fue admisionada en esta sede
                    $yaExiste = OftalmologiaAtencion::query()
                        ->where('sede_id', $sedeId)
                        ->where('atencion_id', $atencionId)
                        ->where('estado', '!=', 'ANULADO')
                        ->exists();

                    if ($yaExiste) {
                        return $this->error("La atención número {$atencionId} ya se encuentra registrada en esta sede.", 422);
                    }
                }

                // 3. Resolver o crear Paciente
                $pacienteId = $this->resolverPaciente($data, $usuarioId);
                if (! $pacienteId) {
                    return $this->error('No fue posible identificar o registrar la información del paciente.', 422);
                }

                // 4. Determinar estado inicial de sincronización SAHI
                $estadoSahi = 'NO_APLICA';
                if ($this->sahiOftalmologiaService->tieneInterfazActiva($sede) && $esRecaudoSahi) {
                    $estadoSahi = 'PENDIENTE';
                } elseif (! $esRecaudoSahi) {
                    $estadoSahi = 'OMITIDO';
                }

                // 5. Crear registro de atención
                $atencion = OftalmologiaAtencion::create([
                    'atencion_id' => $atencionId,
                    'sede_id' => $sedeId,
                    'paciente_id' => $pacienteId,
                    'medico_id' => ! empty($data['medico_id']) ? (int) $data['medico_id'] : null,
                    'usua_regis_id' => $usuarioId,
                    'admin_salud_id' => ! empty($data['admin_salud_id']) ? (int) $data['admin_salud_id'] : null,
                    'tipo_recaudo_id' => $tipoRecaudoId,
                    'contrato' => ! empty($data['contrato']) ? substr(trim((string) $data['contrato']), 0, 150) : null,
                    'plan' => ! empty($data['plan']) ? substr(trim((string) $data['plan']), 0, 150) : null,
                    'ubicacion' => ! empty($data['ubicacion']) ? substr(trim((string) $data['ubicacion']), 0, 150) : null,
                    'cama' => ! empty($data['cama']) ? substr(trim((string) $data['cama']), 0, 80) : null,
                    'quien_ordena' => ! empty($data['quien_ordena']) ? substr(trim((string) $data['quien_ordena']), 0, 150) : null,
                    'fec_cita_sahi' => ! empty($data['fec_cita_sahi']) ? Carbon::parse($data['fec_cita_sahi'])->toDateString() : null,
                    'fec_ingreso' => ! empty($data['fec_ingreso']) ? Carbon::parse($data['fec_ingreso'])->toDateTimeString() : Carbon::now(),
                    'estado' => 'PENDIENTE',
                    'estado_sahi' => $estadoSahi,
                ]);

                return $this->success($atencion->load(['sede', 'paciente', 'tipoRecaudo']), 'Paciente admisionado en Oftalmología correctamente.', 201);
            });
        } catch (\Throwable $e) {
            Log::error('Error registrando admisión de oftalmología', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return $this->error('Error interno al intentar admisionar el paciente: '.$e->getMessage(), 500);
        }
    }

    /**
     * Anula una atención de oftalmología.
     *
     * @return array<string, mixed>
     */
    public function anular(int $atencionId, string $motivo, int $usuarioId): array
    {
        try {
            $atencion = OftalmologiaAtencion::with('consulta')->find($atencionId);
            if (! $atencion) {
                return $this->notFound('No se encontró la atención de oftalmología.');
            }

            if ($atencion->estado === 'ANULADO') {
                return $this->error('La atención ya se encuentra anulada.', 422);
            }

            if ($atencion->consulta && $atencion->consulta->estado_consulta === 'FINALIZADA') {
                return $this->error('No es posible anular una atención con consulta médica finalizada.', 422);
            }

            $atencion->update([
                'estado' => 'ANULADO',
                'usua_anula_id' => $usuarioId,
                'fechor_anula' => Carbon::now(),
                'motivo_anula' => trim($motivo),
            ]);

            return $this->success($atencion, 'Atención anulada exitosamente.');
        } catch (\Throwable $e) {
            Log::error('Error anulando atención de oftalmología', [
                'atencion_id' => $atencionId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('No fue posible anular la atención: '.$e->getMessage(), 500);
        }
    }

    /**
     * Resuelve o crea el paciente en base a la información remitida.
     *
     * @param  array<string, mixed>  $data
     */
    protected function resolverPaciente(array $data, int $usuarioId): ?int
    {
        if (! empty($data['paciente_id'])) {
            $paciente = Paciente::find((int) $data['paciente_id']);
            if ($paciente) {
                return $paciente->id;
            }
        }

        $documento = trim((string) ($data['documento'] ?? $data['num_docu'] ?? ''));
        if ($documento === '') {
            return null;
        }

        $paciente = Paciente::where('num_docu', $documento)->first();
        if ($paciente) {
            return $paciente->id;
        }

        $nombresPartes = array_filter([
            trim((string) ($data['nombres'] ?? '')),
            trim((string) ($data['seg_nom'] ?? '')),
        ]);
        $nomUnido = implode(' ', $nombresPartes);
        $nomCompleto = trim((string) ($data['paciente_nombre'] ?? $nomUnido));
        $nombres = $nomCompleto !== '' ? $nomCompleto : 'PACIENTE SIN NOMBRE';

        $nuevo = Paciente::create([
            'num_docu' => $documento,
            'id_tipo_documento' => ! empty($data['tipo_documento_id']) ? (int) $data['tipo_documento_id'] : null,
            'nombres' => $nombres,
            'pri_ape' => ! empty($data['pri_ape']) ? trim((string) $data['pri_ape']) : null,
            'seg_ape' => ! empty($data['seg_ape']) ? trim((string) $data['seg_ape']) : null,
            'sexo' => in_array($data['sexo'] ?? '', ['M', 'F', 'O'], true) ? $data['sexo'] : 'O',
            'fec_naci' => ! empty($data['fecha_nacimiento'] ?? $data['fec_naci'] ?? null) ? Carbon::parse($data['fecha_nacimiento'] ?? $data['fec_naci'])->toDateString() : null,
            'cel' => ! empty($data['telefono'] ?? $data['cel'] ?? $data['tel'] ?? null) ? substr(trim((string) ($data['telefono'] ?? $data['cel'] ?? $data['tel'])), 0, 50) : null,
            'direccion' => ! empty($data['direccion']) ? substr(trim((string) $data['direccion']), 0, 200) : null,
        ]);

        return $nuevo->id;
    }
}
