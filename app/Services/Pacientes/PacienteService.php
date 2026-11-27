<?php

namespace App\Services\Pacientes;

use App\Helpers\SahiConnectionHelper;
use App\Models\Admisiones\AdminAdmision;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigTipoDocumento;
use App\Models\General\Paciente;
use App\Models\Worklist\WorklistOrden;
use App\Services\BaseService;
use App\Services\Interfaces\SAHIService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Servicio para gestión, búsqueda jerárquica (LIDIAG -> Interfaz por Sede) y admisión de Pacientes.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-27
 */
class PacienteService extends BaseService
{
    /**
     * Inicializa el servicio de pacientes inyectando la capa de interoperabilidad HIS/SAHI.
     *
     * Flujo del proceso:
     * 1. Recibe e inyecta SAHIService para búsquedas transparentes en sedes con interfaz activa.
     *
     * @param  SAHIService  $sahiService  Servicio de consulta en interfaz hospitalaria SAHI.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function __construct(
        private readonly SAHIService $sahiService,
    ) {}

    /**
     * Lista los pacientes en LIDIAG y, si no existen al buscar por documento, consulta de forma transparente la interfaz de la sede.
     *
     * Flujo del proceso:
     * 1. Aplica filtros opcionales de documento, nombre y fechas sobre la base de datos local de LIDIAG.
     * 2. Si existen resultados en LIDIAG, mapea órdenes de Worklist y estado de admisión del día para la sede activa.
     * 3. Si NO existen resultados en LIDIAG y se buscó por documento (sin filtro de fechas locales):
     *    - Verifica si la sede actual está activa y posee interfaz configurada (ej. SAHI).
     *    - Si la sede no tiene interfaz configurada, retorna respuesta limpia informándolo sin errores no controlados.
     *    - Si tiene interfaz activa, conmuta la conexión con SahiConnectionHelper y busca el paciente en SAHI.
     *    - Retorna los datos normalizados en memoria marcados como origen INTERFAZ (se persiste al admitir/enviar a Worklist).
     *
     * @param  array<string, mixed>  $filters  Criterios de búsqueda (bus_documento, bus_nombre, bus_fech_ini, bus_fecha_fin).
     * @param  int  $sedeId  ID de la sede asistencial activa.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function listar(array $filters, int $sedeId): array
    {
        $query = Paciente::with("tipoDocumento:id,codigo,tipo_documento");

        $docBuscado = ! empty($filters["bus_documento"]) ? trim((string) $filters["bus_documento"]) : "";
        $nomBuscado = ! empty($filters["bus_nombre"]) ? trim((string) $filters["bus_nombre"]) : "";
        $tieneFechas = ! empty($filters["bus_fech_ini"]) && ! empty($filters["bus_fecha_fin"]);

        if ($docBuscado !== "") {
            $query->where("num_docu", "like", "%{$docBuscado}%");
        }

        if ($nomBuscado !== "") {
            $query->where(function ($q) use ($nomBuscado) {
                $q->where("nombres", "like", "%{$nomBuscado}%")
                    ->orWhere("seg_nom", "like", "%{$nomBuscado}%")
                    ->orWhere("pri_ape", "like", "%{$nomBuscado}%")
                    ->orWhere("seg_ape", "like", "%{$nomBuscado}%");
            });
        }

        if ($tieneFechas) {
            $inicio = $filters["bus_fech_ini"]." 00:00:00";
            $fin = $filters["bus_fecha_fin"]." 23:59:59";
            $query->whereBetween("created_at", [$inicio, $fin]);
        }

        $pacientes = $query->orderBy("id", "desc")->limit(300)->get();

        // Fallback transparente a la interfaz hospitalaria de la sede solo cuando no existe en LIDIAG
        if ($pacientes->isEmpty() && $docBuscado !== "" && ! $tieneFechas) {
            return $this->buscarPacienteEnInterfazSede($docBuscado, $sedeId);
        }

        $pacienteIds = $pacientes->pluck("id")->all();
        $ordenesActivas = [];
        $admisionesActivas = [];

        if (! empty($pacienteIds) && $sedeId > 0) {
            $ordenes = WorklistOrden::with("producto:id,cod_cups,nom_produc")
                ->where("sede_id", $sedeId)
                ->whereIn("paciente_id", $pacienteIds)
                ->whereIn("estado", ["PENDIENTE", "ADQUIRIDO", "COMPLETADO"])
                ->whereDate("created_at", Carbon::today())
                ->orderBy("id", "desc")
                ->get();

            foreach ($ordenes as $ord) {
                if (! isset($ordenesActivas[$ord->paciente_id])) {
                    $ordenesActivas[$ord->paciente_id] = [
                        "id" => $ord->id,
                        "accession_no" => $ord->accession_no,
                        "modality" => $ord->modality,
                        "station_aet" => $ord->station_aet,
                        "estado" => $ord->estado,
                        "producto" => $ord->producto ? $ord->producto->nom_produc : null,
                        "study_pk" => $ord->study_pk,
                        "study_iuid" => $ord->study_iuid,
                        "estudio_id" => $ord->estudio_id,
                        "adquirido_at" => $ord->adquirido_at ? $ord->adquirido_at->format("H:i") : null,
                        "created_at" => $ord->created_at ? $ord->created_at->format("H:i") : null,
                    ];
                }
            }

            if (Schema::hasTable("admin_admisiones")) {
                $admisiones = AdminAdmision::query()
                    ->where("sede_id", $sedeId)
                    ->whereIn("paciente_id", $pacienteIds)
                    ->where("estado", "!=", AdminAdmision::ESTADO_CANCELADO)
                    ->whereDate("fecha_admision", Carbon::today())
                    ->orderBy("id", "desc")
                    ->get();

                foreach ($admisiones as $adm) {
                    if (! isset($admisionesActivas[$adm->paciente_id])) {
                        $admisionesActivas[$adm->paciente_id] = [
                            "id" => $adm->id,
                            "estado" => $adm->estado,
                            "accession_no" => $adm->accession_no,
                            "study_iuid" => $adm->study_iuid,
                            "study_id" => $adm->study_id,
                            "fecha_admision" => $adm->fecha_admision?->format("H:i"),
                            "fecha_toma" => $adm->fecha_toma?->format("H:i"),
                        ];
                    }
                }
            }
        }

        foreach ($pacientes as $p) {
            $p->orden_worklist_activa = $ordenesActivas[$p->id] ?? null;
            $p->admision_activa = $admisionesActivas[$p->id] ?? null;
            $p->nombre_completo = trim("{$p->pri_ape} {$p->seg_ape} {$p->nombres} {$p->seg_nom}");
            $p->es_paciente_interfaz = false;
            $p->origen_datos = "LIDIAG";
        }

        return $this->success($pacientes);
    }

    /**
     * Consulta un paciente por documento en la interfaz externa configurada en la sede activa sin duplicar ni persistir prematuramente.
     *
     * Flujo del proceso:
     * 1. Verifica que la sede exista, se encuentre activa y posea interface_software configurado.
     * 2. Si la sede no tiene interfaz configurada, devuelve colección vacía con mensaje claro.
     * 3. Si la interfaz es SAHI, aplica SahiConnectionHelper::configureForSede() y consulta admCliente.
     * 4. Normaliza nombres, apellidos, sexo, fecha de nacimiento y tipo de documento homologado con LIDIAG.
     * 5. Retorna el registro virtual identificado como es_paciente_interfaz = true listo para admisión.
     *
     * @param  string  $numDocu  Documento del paciente a buscar.
     * @param  int  $sedeId  ID de la sede activa.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    private function buscarPacienteEnInterfazSede(string $numDocu, int $sedeId): array
    {
        if ($sedeId <= 0) {
            return $this->success([], "No se encontró el paciente en LIDIAG y no hay una sede activa seleccionada.");
        }

        $sede = ConfigSede::find($sedeId);
        if (! $sede || ! $sede->estado) {
            return $this->success([], "No se encontró el paciente en LIDIAG.");
        }

        $software = strtoupper(trim((string) ($sede->interface_software ?? "")));
        if ($software === "") {
            return $this->success(
                [],
                "El paciente con documento '{$numDocu}' no existe en LIDIAG y la sede '{$sede->nom_sede}' no tiene interfaz externa configurada."
            );
        }

        if ($software !== "SAHI") {
            return $this->success(
                [],
                "El paciente no existe en LIDIAG y el conector de interfaz '{$software}' no está habilitado para búsqueda demográfica."
            );
        }

        try {
            SahiConnectionHelper::configureForSede($sede);
            $resSahi = $this->sahiService->buscarPacientePorDocumento($numDocu);

            if (! ($resSahi["success"] ?? false) || empty($resSahi["data"])) {
                $statusSahi = (int) ($resSahi["status"] ?? 404);
                if ($statusSahi === 404) {
                    return $this->success(
                        [],
                        "El paciente con documento '{$numDocu}' no fue encontrado en LIDIAG ni en la interfaz {$software} de la sede '{$sede->nom_sede}'."
                    );
                }

                return $this->success(
                    [],
                    "El paciente no existe en LIDIAG y no fue posible consultar la interfaz {$software} en este momento."
                );
            }

            $cliente = (object) $resSahi["data"];
            $codTipoDoc = strtoupper(trim((string) ($cliente->CodTipoDoc ?? "")));

            $tipoDoc = null;
            if ($codTipoDoc !== "") {
                $tipoDoc = ConfigTipoDocumento::where("estado", true)
                    ->whereRaw("UPPER(codigo) = ?", [$codTipoDoc])
                    ->select("id", "codigo", "tipo_documento")
                    ->first();
            }
            if (! $tipoDoc) {
                $tipoDoc = ConfigTipoDocumento::where("estado", true)
                    ->orderBy("id")
                    ->select("id", "codigo", "tipo_documento")
                    ->first();
            }

            $priNom = strtoupper(trim((string) ($cliente->PrimerNombre ?? "")));
            $segNom = strtoupper(trim((string) ($cliente->SegundoNombre ?? "")));
            $priApe = strtoupper(trim((string) ($cliente->PrimerApellido ?? "")));
            $segApe = strtoupper(trim((string) ($cliente->SegundoApellido ?? "")));

            $rawSexo = strtoupper(trim((string) ($cliente->IdSexo ?? "M")));
            $sexoNorm = match ($rawSexo) {
                "1", "M", "MASCULINO" => "M",
                "2", "F", "FEMENINO" => "F",
                default => "O",
            };

            $fecNaci = ! empty($cliente->FecNacimiento) ? substr(trim((string) $cliente->FecNacimiento), 0, 10) : null;
            $telCasa = ! empty($cliente->TelCasa) ? trim((string) $cliente->TelCasa) : null;
            $dirCasa = ! empty($cliente->DirCasa) ? trim((string) $cliente->DirCasa) : null;
            $docReal = trim((string) ($cliente->NumDocumento ?? $numDocu));

            $nombreCompleto = trim(preg_replace("/\s+/", " ", "{$priApe} {$segApe} {$priNom} {$segNom}") ?? "");
            if ($nombreCompleto === "") {
                $nombreCompleto = $priNom;
            }

            $pacienteInterfaz = [
                "id" => null,
                "row_key" => "interfaz_{$docReal}",
                "tipo_documento_id" => $tipoDoc?->id,
                "tipo_documento" => $tipoDoc ? [
                    "id" => $tipoDoc->id,
                    "codigo" => $tipoDoc->codigo,
                    "tipo_documento" => $tipoDoc->tipo_documento,
                ] : [
                    "id" => null,
                    "codigo" => $codTipoDoc ?: "DOC",
                    "tipo_documento" => (string) ($cliente->NomTipoDoc ?? "Documento"),
                ],
                "num_docu" => $docReal,
                "nombres" => $priNom ?: $nombreCompleto,
                "seg_nom" => $segNom ?: null,
                "pri_ape" => $priApe ?: null,
                "seg_ape" => $segApe ?: null,
                "nombre_completo" => $nombreCompleto,
                "sexo" => $sexoNorm,
                "fec_naci" => $fecNaci,
                "direccion" => $dirCasa,
                "tel" => $telCasa,
                "cel" => $telCasa,
                "email" => null,
                "orden_worklist_activa" => null,
                "admision_activa" => null,
                "es_paciente_interfaz" => true,
                "origen_datos" => "INTERFAZ",
                "interface_software" => $software,
            ];

            return $this->success(
                [$pacienteInterfaz],
                "Paciente localizado en la interfaz {$software}. Se almacenará en LIDIAG automáticamente al enviarlo a la Worklist."
            );
        } catch (\Throwable $e) {
            Log::warning("Fallo controlado consultando paciente '{$numDocu}' en interfaz {$software} (sede {$sedeId}): ".$e->getMessage());

            return $this->success(
                [],
                "El paciente no existe en LIDIAG y la interfaz {$software} de la sede no respondió ({$sede->nom_sede})."
            );
        }
    }

    /**
     * Registra un nuevo paciente en LIDiag.
     *
     * Flujo del proceso:
     * 1. Verifica que no exista otro paciente con el mismo número de documento.
     * 2. Crea el registro dentro de una transacción de base de datos normalizando a mayúsculas.
     * 3. Carga el tipo de documento y retorna el recurso creado con código 201.
     *
     * @param  array<string, mixed>  $data  Datos del paciente validados.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function store(array $data): array
    {
        $numDocu = trim((string) $data["num_docu"]);
        $existente = Paciente::where("num_docu", $numDocu)->first();

        if ($existente) {
            return $this->error("Ya existe un paciente registrado con el documento '{$numDocu}'.", 422, [
                "num_docu" => ["El número de documento ya está en uso."],
            ]);
        }

        try {
            DB::beginTransaction();

            $paciente = Paciente::create([
                "tipo_documento_id" => $data["tipo_documento_id"] ?? null,
                "num_docu" => $numDocu,
                "nombres" => strtoupper(trim((string) $data["nombres"])),
                "seg_nom" => ! empty($data["seg_nom"]) ? strtoupper(trim((string) $data["seg_nom"])) : null,
                "pri_ape" => ! empty($data["pri_ape"]) ? strtoupper(trim((string) $data["pri_ape"])) : null,
                "seg_ape" => ! empty($data["seg_ape"]) ? strtoupper(trim((string) $data["seg_ape"])) : null,
                "direccion" => $data["direccion"] ?? null,
                "email" => $data["email"] ?? null,
                "sexo" => strtoupper(trim((string) ($data["sexo"] ?? "M"))),
                "fec_naci" => $data["fec_naci"] ?? null,
                "tel" => $data["tel"] ?? null,
                "cel" => $data["cel"] ?? null,
            ]);

            DB::commit();

            $paciente->load("tipoDocumento:id,codigo,tipo_documento");
            $paciente->nombre_completo = trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}");

            return $this->success($paciente, "Paciente registrado exitosamente.", 201);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error registrando paciente: ".$e->getMessage());

            return $this->error("No fue posible registrar el paciente. ".$e->getMessage(), 500);
        }
    }

    /**
     * Obtiene el detalle de un paciente específico.
     *
     * Flujo del proceso:
     * 1. Busca el paciente por ID cargando su tipo de documento.
     * 2. Retorna error 404 si no existe o los datos del paciente con nombre completo.
     *
     * @param  int  $id  ID del paciente.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function show(int $id): array
    {
        $paciente = Paciente::with("tipoDocumento:id,codigo,tipo_documento")->find($id);

        if (! $paciente) {
            return $this->error("Paciente no encontrado.", 404);
        }

        $paciente->nombre_completo = trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}");

        return $this->success($paciente);
    }

    /**
     * Actualiza la información demográfica de un paciente.
     *
     * Flujo del proceso:
     * 1. Verifica existencia del paciente y que el nuevo documento no pertenezca a otro registro.
     * 2. Actualiza los campos demográficos en transacción y retorna el modelo actualizado.
     *
     * @param  array<string, mixed>  $data  Datos validados.
     * @param  int  $id  ID del paciente a actualizar.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function update(array $data, int $id): array
    {
        $paciente = Paciente::find($id);
        if (! $paciente) {
            return $this->error("Paciente no encontrado.", 404);
        }

        $numDocu = trim((string) $data["num_docu"]);
        $duplicado = Paciente::where("num_docu", $numDocu)
            ->where("id", "!=", $id)
            ->first();

        if ($duplicado) {
            return $this->error("El número de documento '{$numDocu}' ya pertenece a otro paciente.", 422, [
                "num_docu" => ["El número de documento ya está en uso por otro paciente."],
            ]);
        }

        try {
            DB::beginTransaction();

            $paciente->update([
                "tipo_documento_id" => $data["tipo_documento_id"] ?? null,
                "num_docu" => $numDocu,
                "nombres" => strtoupper(trim((string) $data["nombres"])),
                "seg_nom" => ! empty($data["seg_nom"]) ? strtoupper(trim((string) $data["seg_nom"])) : null,
                "pri_ape" => ! empty($data["pri_ape"]) ? strtoupper(trim((string) $data["pri_ape"])) : null,
                "seg_ape" => ! empty($data["seg_ape"]) ? strtoupper(trim((string) $data["seg_ape"])) : null,
                "direccion" => $data["direccion"] ?? null,
                "email" => $data["email"] ?? null,
                "sexo" => strtoupper(trim((string) ($data["sexo"] ?? "M"))),
                "fec_naci" => $data["fec_naci"] ?? null,
                "tel" => $data["tel"] ?? null,
                "cel" => $data["cel"] ?? null,
            ]);

            DB::commit();

            $paciente->load("tipoDocumento:id,codigo,tipo_documento");
            $paciente->nombre_completo = trim("{$paciente->pri_ape} {$paciente->seg_ape} {$paciente->nombres} {$paciente->seg_nom}");

            return $this->success($paciente, "Paciente actualizado exitosamente.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error actualizando paciente: ".$e->getMessage());

            return $this->error("No fue posible actualizar el paciente. ".$e->getMessage(), 500);
        }
    }

    /**
     * Elimina un paciente si no tiene estudios, ecografías, órdenes ni admisiones asociadas.
     *
     * Flujo del proceso:
     * 1. Cuenta relaciones clínicas asociadas al paciente (incluyendo admin_admisiones si la tabla existe).
     * 2. Impide el borrado con error 422 si cuenta con trazabilidad asistencial.
     * 3. Ejecuta el borrado dentro de una transacción en caso contrario.
     *
     * @param  int  $id  ID del paciente.
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function destroy(int $id): array
    {
        $relaciones = ["estudios", "ecografias", "worklistOrdenes"];
        if (Schema::hasTable("admin_admisiones")) {
            $relaciones[] = "admisiones";
        }

        $paciente = Paciente::withCount($relaciones)->find($id);

        if (! $paciente) {
            return $this->error("Paciente no encontrado.", 404);
        }

        $admisionesCount = (int) ($paciente->admisiones_count ?? 0);
        if (
            $paciente->estudios_count > 0 ||
            $paciente->ecografias_count > 0 ||
            $paciente->worklist_ordenes_count > 0 ||
            $admisionesCount > 0
        ) {
            return $this->error("No es posible eliminar el paciente porque cuenta con estudios, admisiones o registros clínicos asociados.", 422);
        }

        try {
            DB::beginTransaction();
            $paciente->delete();
            DB::commit();

            return $this->success(null, "Paciente eliminado correctamente.");
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error("Error eliminando paciente: ".$e->getMessage(), 500);
        }
    }

    /**
     * Retorna el catálogo de tipos de documento y productos activos para procedimientos de Rayos X.
     *
     * Flujo del proceso:
     * 1. Consulta los tipos de documento activos ordenados por código.
     * 2. Consulta los productos activos de Rayos X (con fallback al catálogo general activo).
     *
     * @return array<string, mixed>
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function obtenerCatalogosAuxiliares(): array
    {
        $tiposDocumento = ConfigTipoDocumento::where("estado", true)
            ->select("id", "codigo", "tipo_documento")
            ->orderBy("codigo")
            ->get();

        $productosRayosX = ConfigProducto::where("estado", true)
            ->where(function ($q) {
                $q->where("nom_produc", "like", "%RAYOS%")
                    ->orWhere("nom_produc", "like", "%RX%")
                    ->orWhere("nom_produc", "like", "%RADIOGRAFIA%");
            })
            ->select("id", "cod_cups", "nom_produc")
            ->orderBy("nom_produc")
            ->get();

        if ($productosRayosX->isEmpty()) {
            $productosRayosX = ConfigProducto::where("estado", true)
                ->select("id", "cod_cups", "nom_produc")
                ->orderBy("nom_produc")
                ->limit(100)
                ->get();
        }

        return $this->success([
            "tipos_documento" => $tiposDocumento,
            "productos_rayos_x" => $productosRayosX,
        ]);
    }
}
