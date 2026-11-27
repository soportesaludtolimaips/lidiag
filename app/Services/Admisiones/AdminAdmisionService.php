<?php

namespace App\Services\Admisiones;

use App\Helpers\AuditLogger;
use App\Http\Resources\Admisiones\AdminAdmisionResource;
use App\Models\Admisiones\AdminAdmision;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use App\Models\Worklist\WorklistOrden;
use App\Services\BaseService;
use App\Services\Dcm4chee\WorklistService;
use App\Services\Estudios\EstudioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio de dominio para la gestión integral de admin_admisiones y el módulo Pendientes de asignar.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class AdminAdmisionService extends BaseService
{
    /**
     * Verifica de forma defensiva si la tabla admin_admisiones ya fue creada en el esquema actual.
     *
     * Flujo del proceso:
     * 1. Consulta Schema::hasTable sobre la conexión por defecto.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function tablaDisponible(): bool
    {
        try {
            return Schema::hasTable("admin_admisiones");
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Lista registros de admin_admisiones aplicando filtros de sede, estado, paciente/accession y fechas.
     *
     * Flujo del proceso:
     * 1. Verifica disponibilidad de la tabla admin_admisiones para evitar errores no controlados.
     * 2. Carga relaciones ansiosas de paciente, tipo de documento, sede, producto, worklist y usuarios.
     * 3. Aplica filtros por sede_id, estado normalizado, texto libre y rango de fechas.
     * 4. Serializa los resultados mediante AdminAdmisionResource.
     *
     * @param  array<string, mixed>  $filters  Criterios de filtrado validados.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function listar(array $filters): array
    {
        if (! $this->tablaDisponible()) {
            return $this->error(
                "La tabla admin_admisiones aún no se encuentra migrada en la base de datos.",
                503
            );
        }

        $query = AdminAdmision::query()->with([
            "paciente.tipoDocumento:id,codigo,tipo_documento",
            "sede:id,nom_sede",
            "producto:id,cod_cups,nom_produc",
            "worklistOrden",
            "usuarioAdmision:id,name",
            "usuarioAsignacion:id,name",
        ]);

        if (! empty($filters["sede_id"])) {
            $query->porSede((int) $filters["sede_id"]);
        }

        if (! empty($filters["estado"])) {
            $estadoNorm = AdminAdmision::normalizarEstado((string) $filters["estado"]);
            if ($estadoNorm === AdminAdmision::ESTADO_PENDIENTE_LECTURA) {
                $query->pendientesLectura();
            } else {
                $varianteGuion = str_replace(" ", "_", $estadoNorm);
                $query->whereIn("estado", array_unique([$estadoNorm, $varianteGuion]));
            }
        }

        $this->aplicarFiltrosComunes($query, $filters);

        $registros = $query
            ->orderByRaw("COALESCE(fecha_toma, fecha_admision) DESC")
            ->orderByDesc("id")
            ->limit(500)
            ->get();

        return $this->success(
            AdminAdmisionResource::collection($registros)->resolve(),
            "Listado de admisiones obtenido correctamente."
        );
    }

    /**
     * Consulta las admisiones en estado PENDIENTE LECTURA para el módulo Pendientes de asignar.
     *
     * Flujo del proceso:
     * 1. Si se solicita reconciliación previa, sincroniza las órdenes activas de la sede con el PACS Dcm4chee.
     * 2. Filtra admin_admisiones estrictamente por estado PENDIENTE LECTURA (estudio_id nulo) y sede activa.
     * 3. Aplica criterios opcionales de documento/nombre/accession/UID, modalidad y rango de fechas.
     * 4. Retorna la colección formateada con AdminAdmisionResource lista para asignación de lectura.
     *
     * @param  array<string, mixed>  $filters  Filtros validados (sede_id, bus_nom_num_docu, bus_fech_ini, bus_fecha_fin, modality, reconciliar).
     * @param  WorklistService|null  $worklistService  Servicio de Worklist opcional para reconciliación previa.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function listarPendientesAsignar(array $filters, ?WorklistService $worklistService = null): array
    {
        if (! $this->tablaDisponible()) {
            return $this->error(
                "La tabla admin_admisiones aún no se encuentra migrada en la base de datos.",
                503
            );
        }

        $sedeId = (int) $filters["sede_id"];

        if (! empty($filters["reconciliar"]) && $worklistService !== null && $sedeId > 0) {
            try {
                $worklistService->reconciliarOrdenes($sedeId);
            } catch (\Throwable $e) {
                Log::warning("Reconciliación silenciosa en listarPendientesAsignar omitida: ".$e->getMessage());
            }
        }

        $query = AdminAdmision::query()
            ->with([
                "paciente.tipoDocumento:id,codigo,tipo_documento",
                "sede:id,nom_sede",
                "producto:id,cod_cups,nom_produc",
                "worklistOrden",
                "usuarioAdmision:id,name",
            ])
            ->porSede($sedeId)
            ->pendientesLectura();

        $this->aplicarFiltrosComunes($query, $filters);

        $registros = $query
            ->orderByRaw("COALESCE(fecha_toma, fecha_admision) DESC")
            ->orderByDesc("id")
            ->limit(500)
            ->get();

        return $this->success(
            AdminAdmisionResource::collection($registros)->resolve(),
            "Estudios pendientes de asignar obtenidos correctamente."
        );
    }

    /**
     * Obtiene el detalle completo de una admisión específica por su identificador.
     *
     * Flujo del proceso:
     * 1. Busca el registro en admin_admisiones con todas sus relaciones clínicas y DICOM.
     * 2. Retorna error 404 si no existe o el recurso serializado en caso de éxito.
     *
     * @param  int  $id  Identificador de la admisión.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function show(int $id): array
    {
        if (! $this->tablaDisponible()) {
            return $this->error("La tabla admin_admisiones no está disponible.", 503);
        }

        $admision = AdminAdmision::with([
            "paciente.tipoDocumento:id,codigo,tipo_documento",
            "sede:id,nom_sede",
            "producto:id,cod_cups,nom_produc",
            "worklistOrden",
            "estudio",
            "usuarioAdmision:id,name",
            "usuarioAsignacion:id,name",
        ])->find($id);

        if (! $admision) {
            return $this->notFound("Registro de admisión no encontrado.");
        }

        return $this->success(
            (new AdminAdmisionResource($admision))->resolve(),
            "Detalle de admisión obtenido correctamente."
        );
    }

    /**
     * Registra una admisión enviando el paciente a la Modality Worklist y persistiendo su trazabilidad.
     *
     * Flujo del proceso:
     * 1. Delega en WorklistService::enviarAWorklist el envío DICOM y la creación atómica de paciente, orden y admisión.
     * 2. Retorna el resultado estructurado del proceso.
     *
     * @param  array<string, mixed>  $data  Datos validados de la admisión y estación Worklist.
     * @param  int  $usuarioId  ID del usuario autenticado que admite al paciente.
     * @param  WorklistService  $worklistService  Servicio de orquestación MWL Dcm4chee.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function store(array $data, int $usuarioId, WorklistService $worklistService): array
    {
        return $worklistService->enviarAWorklist($data, $usuarioId);
    }

    /**
     * Registra o sincroniza la fila de admin_admisiones al crear una orden en la Modality Worklist.
     *
     * Flujo del proceso:
     * 1. Verifica disponibilidad de la tabla admin_admisiones.
     * 2. Crea o actualiza el registro asociado a worklist_orden_id en estado ADMISIONADO.
     * 3. Persiste accession_no, study_iuid inicial, modalidad, descripción del producto y contexto asistencial.
     *
     * @param  Paciente  $paciente  Paciente ya persistido en LIDiag.
     * @param  WorklistOrden  $orden  Orden creada en worklist_ordenes.
     * @param  array<string, mixed>  $contexto  Datos opcionales de atención, EPS, contrato y observaciones.
     * @param  int  $usuarioId  ID del usuario que realiza la admisión.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function registrarDesdeWorklist(
        Paciente $paciente,
        WorklistOrden $orden,
        array $contexto,
        int $usuarioId
    ): ?AdminAdmision {
        if (! $this->tablaDisponible()) {
            return null;
        }

        $studyDesc = $orden->relationLoaded("producto") && $orden->producto
            ? $orden->producto->nom_produc
            : ($contexto["study_desc"] ?? null);

        return AdminAdmision::updateOrCreate(
            ["worklist_orden_id" => $orden->id],
            [
                "paciente_id" => $paciente->id,
                "sede_id" => $orden->sede_id,
                "producto_id" => $orden->producto_id,
                "usuario_admision_id" => $usuarioId,
                "accession_no" => $orden->accession_no,
                "study_iuid" => $orden->study_iuid,
                "modality" => $orden->modality,
                "study_desc" => $studyDesc,
                "fecha_admision" => $orden->created_at ?? Carbon::now(),
                "estado" => AdminAdmision::ESTADO_ADMISIONADO,
                "atencion" => ! empty($contexto["atencion"]) ? (string) $contexto["atencion"] : null,
                "eps" => ! empty($contexto["eps"]) ? (string) $contexto["eps"] : null,
                "contrato" => ! empty($contexto["contrato"]) ? (string) $contexto["contrato"] : null,
                "plan" => ! empty($contexto["plan"]) ? (string) $contexto["plan"] : null,
                "servicio" => ! empty($contexto["servicio"]) ? (string) $contexto["servicio"] : null,
                "ubicacion" => ! empty($contexto["ubicacion"]) ? (string) $contexto["ubicacion"] : null,
                "cama" => ! empty($contexto["cama"]) ? (string) $contexto["cama"] : null,
                "medico_ordena" => ! empty($contexto["medico_ordena"]) ? (string) $contexto["medico_ordena"] : null,
                "observaciones" => $contexto["observaciones"] ?? $orden->observaciones,
            ]
        );
    }

    /**
     * Sincroniza el estado y los identificadores DICOM de admin_admisiones durante la reconciliación PACS.
     *
     * Flujo del proceso:
     * 1. Localiza la admisión vinculada a la orden de Worklist (o la inicializa si proviene de una orden previa).
     * 2. Actualiza study_iuid, study_id, study_pk, modality, study_desc y fecha_toma cuando el PACS los reporta.
     * 3. Actualiza el estado (TOMANDO ESTUDIO, PENDIENTE LECTURA o ASIGNADO) preservando trazabilidad completa.
     *
     * @param  WorklistOrden  $orden  Orden de Worklist evaluada.
     * @param  string  $nuevoEstado  Estado destino en admin_admisiones.
     * @param  object|null  $estudioPacs  Registro crudo de la tabla study de Dcm4chee (si existe).
     * @param  Estudio|null  $estudioLidiag  Estudio ya asignado en LIDiag (si existe).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function sincronizarDesdeReconciliacion(
        WorklistOrden $orden,
        string $nuevoEstado,
        ?object $estudioPacs = null,
        ?Estudio $estudioLidiag = null
    ): ?AdminAdmision {
        if (! $this->tablaDisponible()) {
            return null;
        }

        $admision = AdminAdmision::withTrashed()
            ->where("worklist_orden_id", $orden->id)
            ->first();

        if (! $admision) {
            $admision = new AdminAdmision([
                "worklist_orden_id" => $orden->id,
                "paciente_id" => $orden->paciente_id,
                "sede_id" => $orden->sede_id,
                "producto_id" => $orden->producto_id,
                "usuario_admision_id" => $orden->usuario_id,
                "accession_no" => $orden->accession_no,
                "study_iuid" => $orden->study_iuid,
                "modality" => $orden->modality,
                "fecha_admision" => $orden->created_at ?? Carbon::now(),
                "observaciones" => $orden->observaciones,
            ]);
        } elseif ($admision->trashed()) {
            return null;
        }

        if ($estudioPacs !== null) {
            if (! empty($estudioPacs->study_iuid)) {
                $admision->study_iuid = trim((string) $estudioPacs->study_iuid);
            }
            if (! empty($estudioPacs->study_id)) {
                $admision->study_id = trim((string) $estudioPacs->study_id);
            }
            if (! empty($estudioPacs->pk)) {
                $admision->study_pk = (int) $estudioPacs->pk;
            }
            if (! empty($estudioPacs->study_datetime)) {
                $admision->fecha_toma = Carbon::parse($estudioPacs->study_datetime);
            } elseif (! $admision->fecha_toma && $nuevoEstado === AdminAdmision::ESTADO_PENDIENTE_LECTURA) {
                $admision->fecha_toma = $orden->adquirido_at ?? Carbon::now();
            }
            if (! empty($estudioPacs->mods_in_study)) {
                $admision->modality = trim((string) $estudioPacs->mods_in_study);
            }
            if (! empty($estudioPacs->study_desc)) {
                $desc = (string) $estudioPacs->study_desc;
                $admision->study_desc = mb_check_encoding($desc, "UTF-8")
                    ? $desc
                    : mb_convert_encoding($desc, "UTF-8", "ISO-8859-1");
            }
        }

        if ($estudioLidiag !== null) {
            $admision->estudio_id = $estudioLidiag->id;
            if (! empty($estudioLidiag->study_iuid)) {
                $admision->study_iuid = $estudioLidiag->study_iuid;
            }
            if (! empty($estudioLidiag->study_id)) {
                $admision->study_id = $estudioLidiag->study_id;
            }
            if (! empty($estudioLidiag->study_pk)) {
                $admision->study_pk = (int) $estudioLidiag->study_pk;
            }
            if (! $admision->fecha_asignacion) {
                $admision->fecha_asignacion = $estudioLidiag->created_at ?? Carbon::now();
            }
            if (! $admision->usuario_asignacion_id && ! empty($estudioLidiag->quien_registro_id)) {
                $admision->usuario_asignacion_id = (int) $estudioLidiag->quien_registro_id;
            }
        }

        $admision->estado = AdminAdmision::normalizarEstado($nuevoEstado);
        $admision->save();

        return $admision;
    }

    /**
     * Actualiza manualmente el estado y metadatos DICOM de una admisión existente.
     *
     * Flujo del proceso:
     * 1. Valida que el estado solicitado pertenezca al conjunto de estados permitidos del workflow.
     * 2. Actualiza dentro de una transacción el estado, study_iuid, study_id, study_pk y fecha_toma si aplican.
     * 3. Registra trazabilidad en audit_logs y retorna el recurso actualizado.
     *
     * @param  int  $id  ID del registro en admin_admisiones.
     * @param  array<string, mixed>  $data  Campos validados a actualizar.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function actualizarEstado(int $id, array $data): array
    {
        if (! $this->tablaDisponible()) {
            return $this->error("La tabla admin_admisiones no está disponible.", 503);
        }

        $estadoNuevo = AdminAdmision::normalizarEstado((string) $data["estado"]);
        if (! in_array($estadoNuevo, AdminAdmision::ESTADOS_VALIDOS, true)) {
            return $this->error("El estado '{$data["estado"]}' no es válido para el flujo de admisión.", 422);
        }

        return DB::transaction(function () use ($id, $data, $estadoNuevo) {
            $admision = AdminAdmision::lockForUpdate()->find($id);
            if (! $admision) {
                return $this->notFound("Registro de admisión no encontrado.");
            }

            $estadoAnterior = $admision->estado;
            $admision->estado = $estadoNuevo;

            if (! empty($data["study_iuid"])) {
                $admision->study_iuid = trim((string) $data["study_iuid"]);
            }
            if (! empty($data["study_id"])) {
                $admision->study_id = trim((string) $data["study_id"]);
            }
            if (! empty($data["study_pk"])) {
                $admision->study_pk = (int) $data["study_pk"];
            }
            if (! empty($data["fecha_toma"])) {
                $admision->fecha_toma = Carbon::parse($data["fecha_toma"]);
            } elseif ($estadoNuevo === AdminAdmision::ESTADO_PENDIENTE_LECTURA && ! $admision->fecha_toma) {
                $admision->fecha_toma = Carbon::now();
            }
            if (array_key_exists("observaciones", $data) && $data["observaciones"] !== null) {
                $admision->observaciones = (string) $data["observaciones"];
            }

            $admision->save();

            AuditLogger::log(
                "cambio_estado_admision",
                $admision,
                "Cambio de estado en admisión #{$admision->id} (Acc: {$admision->accession_no}): {$estadoAnterior} -> {$estadoNuevo}"
            );

            $admision->load([
                "paciente.tipoDocumento:id,codigo,tipo_documento",
                "sede:id,nom_sede",
                "producto:id,cod_cups,nom_produc",
                "worklistOrden",
            ]);

            return $this->updated(
                (new AdminAdmisionResource($admision))->resolve(),
                "Estado de admisión actualizado a '{$estadoNuevo}'."
            );
        });
    }

    /**
     * Envía a lectura médica una admisión en estado PENDIENTE LECTURA replicando el flujo de EstudioService::create.
     *
     * Flujo del proceso:
     * 1. Carga la admisión y verifica que no haya sido asignada ni cancelada previamente.
     * 2. Enriquece el payload de registro preservando study_iuid, study_id, study_pk, accession_no y contexto clínico.
     * 3. Delega en EstudioService::create la creación transaccional del estudio, productos, diagnósticos, soportes y liberación de lock.
     * 4. Vincula estudio_id, usuario_asignacion_id, fecha_asignacion y estado ASIGNADO en admin_admisiones.
     *
     * @param  int  $admisionId  Identificador del registro en admin_admisiones.
     * @param  array<string, mixed>  $registro  Payload decodificado del formulario de asignación.
     * @param  UploadedFile|null  $archivo1  Soporte HC 1 opcional.
     * @param  UploadedFile|null  $archivo2  Soporte HC 2 opcional.
     * @param  UploadedFile|null  $archivo3  Soporte HC 3 opcional.
     * @param  EstudioService  $estudioService  Servicio central de asignación de estudios.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function asignarALectura(
        int $admisionId,
        array $registro,
        ?UploadedFile $archivo1,
        ?UploadedFile $archivo2,
        ?UploadedFile $archivo3,
        EstudioService $estudioService
    ): array {
        if (! $this->tablaDisponible()) {
            return $this->error("La tabla admin_admisiones no está disponible.", 503);
        }

        $admision = AdminAdmision::with(["paciente", "producto", "worklistOrden"])->find($admisionId);
        if (! $admision) {
            return $this->notFound("La admisión seleccionada no existe.");
        }

        if ($admision->estudio_id !== null || $admision->estado === AdminAdmision::ESTADO_ASIGNADO) {
            return $this->error("Esta admisión ya fue asignada a lectura previamente.", 409);
        }

        if ($admision->estado === AdminAdmision::ESTADO_CANCELADO) {
            return $this->error("No es posible asignar a lectura una admisión cancelada.", 422);
        }

        // Preservar estrictamente la relación con el workflow, study_iuid, study_id y study_pk
        $registro["admision_id"] = $admision->id;
        $registro["worklist_orden_id"] = $admision->worklist_orden_id;
        $registro["sede_id"] = ! empty($registro["sede_id"]) ? (int) $registro["sede_id"] : $admision->sede_id;
        $registro["study_iuid"] = ! empty($registro["study_iuid"]) ? $registro["study_iuid"] : $admision->study_iuid;
        $registro["study_id"] = ! empty($registro["study_id"]) ? $registro["study_id"] : $admision->study_id;
        $registro["study_pk"] = ! empty($registro["study_pk"]) ? $registro["study_pk"] : $admision->study_pk;
        $registro["accession_no"] = ! empty($registro["accession_no"]) ? $registro["accession_no"] : $admision->accession_no;
        $registro["mods_in_study"] = ! empty($registro["mods_in_study"]) ? $registro["mods_in_study"] : ($admision->modality ?? "DX");

        if (empty($registro["study_datetime"])) {
            $registro["study_datetime"] = $admision->fecha_toma
                ? $admision->fecha_toma->format("Y-m-d H:i:s")
                : ($admision->fecha_admision?->format("Y-m-d H:i:s") ?? now()->format("Y-m-d H:i:s"));
        }

        if (empty($registro["study_desc"])) {
            $registro["study_desc"] = $admision->study_desc ?: ($admision->producto?->nom_produc ?? "ESTUDIO RADIOLÓGICO");
        }

        // Conservar contexto asistencial capturado en admisión si el usuario no lo sobreescribió
        foreach (["eps", "contrato", "plan", "servicio", "ubicacion", "cama", "medico_ordena"] as $campo) {
            if (empty($registro[$campo]) && ! empty($admision->{$campo})) {
                $registro[$campo] = $admision->{$campo};
            }
        }
        if (empty($registro["atencion_id"]) && ! empty($admision->atencion)) {
            $registro["atencion_id"] = $admision->atencion;
        }

        $resultadoEstudio = $estudioService->create($registro, $archivo1, $archivo2, $archivo3);

        if (! ($resultadoEstudio["success"] ?? false)) {
            return $resultadoEstudio;
        }

        /** @var Estudio $estudioCreado */
        $estudioCreado = $resultadoEstudio["data"];

        $admision->refresh();
        $admision->estudio_id = $estudioCreado->id;
        $admision->estado = AdminAdmision::ESTADO_ASIGNADO;
        $admision->fecha_asignacion = $admision->fecha_asignacion ?? Carbon::now();
        $admision->usuario_asignacion_id = (int) (Auth::id() ?: $estudioCreado->quien_registro_id);
        if (! empty($estudioCreado->study_iuid)) {
            $admision->study_iuid = $estudioCreado->study_iuid;
        }
        if (! empty($estudioCreado->study_id)) {
            $admision->study_id = $estudioCreado->study_id;
        }
        if (! empty($estudioCreado->study_pk)) {
            $admision->study_pk = (int) $estudioCreado->study_pk;
        }
        $admision->save();

        if ($admision->worklist_orden_id) {
            WorklistOrden::where("id", $admision->worklist_orden_id)->update([
                "estado" => "COMPLETADO",
                "estudio_id" => $estudioCreado->id,
                "study_pk" => $admision->study_pk,
                "updated_at" => Carbon::now(),
            ]);
        }

        return $this->created(
            [
                "estudio" => $estudioCreado,
                "admision_id" => $admision->id,
                "estado_admision" => $admision->estado,
            ],
            $resultadoEstudio["message"] ?? "La asignación de la lectura se realizó correctamente."
        );
    }

    /**
     * Cancela una admisión activa y retira su orden de Worklist en Dcm4chee si aún estaba pendiente.
     *
     * Flujo del proceso:
     * 1. Busca la admisión y verifica que no esté ya asignada a lectura médica.
     * 2. Si posee una orden Worklist en estado PENDIENTE, solicita su retiro del PACS Dcm4chee.
     * 3. Marca la admisión como CANCELADO registrando auditoría.
     *
     * @param  int  $id  ID de la admisión a cancelar.
     * @param  int  $usuarioId  ID del usuario que ejecuta la cancelación.
     * @param  WorklistService  $worklistService  Servicio de Worklist.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function cancelar(int $id, int $usuarioId, WorklistService $worklistService): array
    {
        if (! $this->tablaDisponible()) {
            return $this->error("La tabla admin_admisiones no está disponible.", 503);
        }

        $admision = AdminAdmision::with("worklistOrden")->find($id);
        if (! $admision) {
            return $this->notFound("Registro de admisión no encontrado.");
        }

        if ($admision->estado === AdminAdmision::ESTADO_ASIGNADO || $admision->estudio_id !== null) {
            return $this->error("No se puede cancelar una admisión que ya fue enviada a lectura.", 422);
        }

        if ($admision->worklistOrden && $admision->worklistOrden->estado === "PENDIENTE") {
            $worklistService->cancelarOrdenWorklist($admision->worklistOrden->id, $usuarioId);
        }

        $admision->estado = AdminAdmision::ESTADO_CANCELADO;
        $admision->observaciones = trim(($admision->observaciones ? $admision->observaciones." | " : "")."Cancelada por usuario ID {$usuarioId} en ".now());
        $admision->save();

        AuditLogger::log(
            "cancelar_admision",
            $admision,
            "Admisión #{$admision->id} (Acc: {$admision->accession_no}) cancelada por usuario ID {$usuarioId}"
        );

        return $this->updated(null, "La admisión fue cancelada correctamente.");
    }

    /**
     * Aplica filtros compartidos de texto libre, modalidad y rango de fechas sobre una consulta de AdminAdmision.
     *
     * Flujo del proceso:
     * 1. Si existe bus_nom_num_docu, filtra sobre documento/nombres del paciente o accession_no/study_iuid/study_id.
     * 2. Si existe modality, filtra por coincidencia parcial de modalidad DICOM.
     * 3. Si existen fechas inicio y fin, acota sobre COALESCE(fecha_toma, fecha_admision).
     *
     * @param  Builder  $query  Consulta activa de AdminAdmision.
     * @param  array<string, mixed>  $filters  Criterios de filtrado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    private function aplicarFiltrosComunes($query, array $filters): void
    {
        if (! empty($filters["bus_nom_num_docu"])) {
            $term = trim((string) $filters["bus_nom_num_docu"]);
            $safeTerm = str_replace(["%", "_"], ["\\%", "\\_"], $term);

            $query->where(function ($q) use ($safeTerm) {
                $q->where("accession_no", "like", "%{$safeTerm}%")
                    ->orWhere("study_iuid", "like", "%{$safeTerm}%")
                    ->orWhere("study_id", "like", "%{$safeTerm}%")
                    ->orWhereHas("paciente", function ($pq) use ($safeTerm) {
                        $pq->where("num_docu", "like", "%{$safeTerm}%")
                            ->orWhere("nombres", "like", "%{$safeTerm}%")
                            ->orWhere("seg_nom", "like", "%{$safeTerm}%")
                            ->orWhere("pri_ape", "like", "%{$safeTerm}%")
                            ->orWhere("seg_ape", "like", "%{$safeTerm}%");
                    });
            });
        }

        if (! empty($filters["modality"])) {
            $mod = strtoupper(trim((string) $filters["modality"]));
            $query->where("modality", "like", "%{$mod}%");
        }

        if (! empty($filters["bus_fech_ini"]) && ! empty($filters["bus_fecha_fin"])) {
            $inicio = $filters["bus_fech_ini"]." 00:00:00";
            $fin = $filters["bus_fecha_fin"]." 23:59:59";
            $query->whereRaw("COALESCE(fecha_toma, fecha_admision) BETWEEN ? AND ?", [$inicio, $fin]);
        } elseif (! empty($filters["bus_fech_ini"])) {
            $inicio = $filters["bus_fech_ini"]." 00:00:00";
            $query->whereRaw("COALESCE(fecha_toma, fecha_admision) >= ?", [$inicio]);
        } elseif (! empty($filters["bus_fecha_fin"])) {
            $fin = $filters["bus_fecha_fin"]." 23:59:59";
            $query->whereRaw("COALESCE(fecha_toma, fecha_admision) <= ?", [$fin]);
        }
    }
}
