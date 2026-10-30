<?php

namespace App\Services\Estudios;

use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Services\BaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EstudioQueryService extends BaseService
{
    /**
     * Obtiene todos los estudios registrados en el sistema.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function all(): array
    {
        return $this->success(Estudio::all());
    }

    /**
     * Lista estudios pendientes de lectura para un médico en una sede específica.
     *
     * @param  int  $medicoId  ID del médico
     * @param  int  $sedeId  ID de la sede
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarPendientesMedico(int $medicoId, int $sedeId): array
    {
        $results = Estudio::select(
            'estudios.id',
            'estudios.study_pk',
            'estudios.study_iuid',
            'estudios.study_id',
            'estudios.fec_estudio',
            'estudios.accession_no',
            'estudios.study_desc',
            'estudios.mods_in_study',
            'estudios.email_reportar',
            'estudios.paciente_id',
            'estudios.medico_id',
            'estudios.quien_registro_id',
            'estudios.sede_id',
            'estudios.prioridad_id',
            'estudios.observaciones',
            'estudios.motivo_examen',
            'estudios.atencion',
            'estudios.servicio',
            'estudios.eps',
            'estudios.contrato',
            'estudios.plan',
            'estudios.estado',
            DB::raw("DATE_FORMAT(estudios.created_at, '%Y-%m-%d %H:%i:%s') AS created_at"),
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.sexo',
            'estu_produc.id as estudio_producto_id',
            'estu_produc.cod_cups',
            'estu_produc.nom_produc',
            'estu_produc.fechor_lectura',
            'estu_produc.visto',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'sesde.nom_sede as nom_sede',
            'sesde.url_oviyam',
            'sesde.tap_oviyam',
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sesde', 'estudios.sede_id', '=', 'sesde.id')
            ->join('estudios_productos AS estu_produc', 'estu_produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos AS config_produc', 'estu_produc.producto_id', '=', 'config_produc.id')
            ->where('estudios.medico_id', $medicoId)
            ->where('estu_produc.activo', 1)
            ->whereNull('estu_produc.transcriptor_id')
            ->where('config_produc.requi_lectura', 1)
            ->whereNull('estu_produc.fechor_trascrito')
            ->whereNull('estu_produc.fechor_lectura')
            ->where('sesde.id', $sedeId)
            ->orderBy('priori.nivel')
            ->get();

        return $this->success($results);
    }

    /**
     * Lista los diagnósticos asociados a un estudio específico.
     *
     * @param  int  $estudioId  ID del estudio
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarDiagnosticosPorEstudio(int $estudioId): array
    {
        return $this->success(
            EstudioDiagnostico::where('estudio_id', $estudioId)->get()
        );
    }

    /**
     * Lista estudios pendientes de transcripción con filtros aplicados.
     *
     * @param  array  $filters  Filtros: sede_id, destino_estudio, fechas, nombre/doc, médico
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarPendientesTranscribir(array $filters): array
    {
        $query = Estudio::select(
            'estudios.id',
            'estudios.study_pk',
            'estudios.study_iuid',
            'estudios.study_id',
            'estudios.fec_estudio',
            'estudios.accession_no',
            'estudios.study_desc',
            'estudios.mods_in_study',
            'estudios.email_reportar',
            'estudios.paciente_id',
            'estudios.medico_id',
            'estudios.quien_registro_id',
            'estudios.sede_id',
            'estudios.prioridad_id',
            'estudios.observaciones',
            'estudios.atencion',
            'estudios.servicio',
            'estudios.eps',
            'estudios.contrato',
            'estudios.plan',
            'estudios.estado',
            DB::raw("DATE_FORMAT(estudios.created_at, '%Y-%m-%d %H:%i:%s') AS created_at"),
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.direccion',
            'pacien.email',
            'pacien.tel',
            'estudios_productos.id as estudio_producto_id',
            'estudios_productos.cod_cups',
            'estudios_productos.nom_produc',
            'estudios_productos.fechor_lectura',
            'estudios_productos.lectura',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'medi.name as medico',
            'medi.name as nombres_medico',
            'medi.firma as firma_medico',
            'medi.reg_med as reg_medico',
            'medi.num_docu AS num_docu_medico',
            'sede.nom_sede',
            'sede.url_oviyam',
            'sede.tap_oviyam',
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sede', 'estudios.sede_id', '=', 'sede.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->leftJoin('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos', 'estudios_productos.producto_id', '=', 'config_productos.id');

        $query = $this->applyTranscripcionFilters($query, $filters);
        $results = $query->orderBy('priori.nivel')->get();

        return $this->success($results);
    }

    /**
     * Lista estudios asignados a médicos con filtros de búsqueda.
     *
     * @param  array  $filters  Filtros: sede_id, fechas, nombre/doc, quién asignó, médico
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function listarEstudiosAsignados(array $filters): array
    {
        $query = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.sexo',
            'pacien.direccion',
            'pacien.email',
            'pacien.tel',
            'estudios_productos.id as estudio_producto_id',
            'estudios_productos.cod_cups',
            'estudios_productos.nom_produc',
            'estudios_productos.fechor_lectura',
            'estudios_productos.lectura',
            'estudios_productos.transcriptor_id',
            'estudios_productos.activo',
            'estudios_productos.estado_envio_sahi',
            'estudios_productos.fechor_envio_sahi',
            'estudios_productos.sahi_esquema_149',
            'estudios_productos.sahi_esquema_150',
            'estudios_productos.error_envio_sahi',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'medi.name as medico',
            'medi.firma',
            'medi.reg_med',
            'medi.num_docu AS num_docu_medi',
            'sede.nom_sede',
            'sede.url_oviyam',
            'sede.tap_oviyam',
            'sede.interface_software',
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sede', 'estudios.sede_id', '=', 'sede.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos', 'estudios_productos.producto_id', '=', 'config_productos.id');

        $query = $this->applyAsignadosFilters($query, $filters);
        $results = $query->orderByDesc('estudios.fec_estudio')
            ->orderByDesc('estudios.created_at')
            ->get();

        return $this->success($results);
    }

    /**
     * Lista estudios pendientes por leer y transcribir sin filtros.
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function pendientesPorLeerTranscribir(): array
    {
        $results = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.sexo',
            'produc.id as estudio_producto_id',
            'produc.cod_cups',
            'produc.nom_produc',
            'produc.fechor_lectura',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'sesde.nom_sede as nom_sede',
            'sesde.url_oviyam',
            'sesde.tap_oviyam',
            'medico.id as medico_id',
            'medico.name as medico_name',
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sesde', 'estudios.sede_id', '=', 'sesde.id')
            ->join('estudios_productos AS produc', 'produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('users as medico', 'estudios.medico_id', '=', 'medico.id')
            ->whereNull('produc.fechor_lectura')
            ->where('medico.permitir_transcribir', 1)
            ->orderBy('priori.nom_priori')
            ->get();

        return $this->success($results);
    }

    /**
     * Verifica si un paciente tiene estudios registrados por número de documento.
     *
     * @param  string  $numDocu  Número de documento del paciente
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function buscarPorNumDocu(string $numDocu): array
    {
        $count = Estudio::select('pacientes.num_docu')
            ->join('pacientes', 'estudios.paciente_id', 'pacientes.id')
            ->where('pacientes.num_docu', $numDocu)
            ->count();

        return $this->success(['tiene_estudios' => $count > 0]);
    }

    /**
     * Aplica los filtros del listado de pendientes por transcribir.
     *
     * @param  Builder  $query  Consulta base con los joins ya aplicados
     * @param  array  $filters  Filtros: sede_id, destino, fechas, nombre/doc, quién asignó, médico
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function applyTranscripcionFilters(Builder $query, array $filters): Builder
    {
        $sedeId = $filters['sede_id'] ?? null;
        $destinoEstudio = $filters['destino_estudio'] ?? null;
        $estudioParaTranscribir = $filters['estudioParaTranscribir'] ?? null;

        $query->where('estudios_productos.activo', 1)
            ->where('estudios.estado', 1)
            ->whereNull('estudios_productos.transcriptor_id');

        if ($sedeId) {
            $query->where('estudios.sede_id', $sedeId);
        }

        if ($destinoEstudio) {
            $query->where(function ($q) use ($destinoEstudio) {
                $q->where('estudios.destino_estudio', $destinoEstudio)
                    ->orWhereNull('estudios.destino_estudio');
            });
        }

        if ($estudioParaTranscribir) {
            $query->where(function ($q) {
                $q->whereNotNull('estudios_productos.fechor_lectura');
            });
        }

        if (! empty($filters['bus_fech_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $inicio = Carbon::parse($filters['bus_fech_ini'])->startOfDay();
            $fin = Carbon::parse($filters['bus_fecha_fin'])->endOfDay();
            $query->whereBetween('estudios.fec_estudio', [$inicio, $fin]);
        }

        if (! empty($filters['bus_nom_num_docu'])) {
            $search = $filters['bus_nom_num_docu'];
            $query->where(function ($q) use ($search) {
                $q->where('pacien.num_docu', $search)
                    ->orWhere('pacien.nombres', 'LIKE', "%{$search}%");
            });
        }

        if (! empty($filters['quien_asigno_id'])) {
            $query->where('estudios.quien_registro_id', $filters['quien_asigno_id']);
        }

        if (! empty($filters['medico_id'])) {
            $query->where('estudios.medico_id', $filters['medico_id']);
        }

        return $query;
    }

    /**
     * Aplica los filtros del listado de estudios asignados.
     *
     * @param  Builder  $query  Consulta base con los joins ya aplicados
     * @param  array  $filters  Filtros: sede_id, fechas, nombre/doc, quién asignó, médico
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function applyAsignadosFilters(Builder $query, array $filters): Builder
    {
        $sedeId = $filters['sede_id'] ?? null;

        $query->where('estudios_productos.activo', 1);

        if ($sedeId) {
            $query->where('estudios.sede_id', $sedeId);
        }

        if (! empty($filters['bus_fech_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $query->whereBetween('estudios.fec_estudio', [
                $filters['bus_fech_ini'].' 00:00:00',
                $filters['bus_fecha_fin'].' 23:59:59',
            ]);
        }

        if (! empty($filters['bus_nom_num_docu'])) {
            $search = $filters['bus_nom_num_docu'];
            $query->where(function ($q) use ($search) {
                $q->where('pacien.num_docu', $search)
                    ->orWhere('pacien.nombres', 'LIKE', "%{$search}%");
            });
        }

        if (! empty($filters['quien_asigno_id'])) {
            $query->where('estudios.quien_registro_id', $filters['quien_asigno_id']);
        }

        if (! empty($filters['medico_id'])) {
            $query->where('estudios.medico_id', $filters['medico_id']);
        }

        return $query;
    }
}
