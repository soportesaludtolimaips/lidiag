<?php

namespace App\Services\Dcm4chee;

use App\Helpers\DicomDatasetBuilder;
use App\Models\Admisiones\AdminAdmision;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigWorklistDispositivo;
use App\Models\Ecografias\Ecografia;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use App\Models\Worklist\WorklistOrden;
use App\Services\Admisiones\AdminAdmisionService;
use App\Services\BaseService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestión y orquestación de la Modality Worklist (MWL) con Dcm4chee.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class WorklistService extends BaseService
{
    /**
     * Genera un Accession Number sugerido único con formato secuencial por sede, fecha y modalidad.
     *
     * @param  int  $sedeId  ID de la sede asistencial
     * @param  string  $modality  Modalidad DICOM (ej. DX, CR, US)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function generarAccessionSugerido(int $sedeId, string $modality = 'DX'): string
    {
        $modality = strtoupper(trim($modality));
        $prefijoModalidad = in_array($modality, ['US', 'ECO'], true) ? 'US-' : 'RX-';
        $prefijoFecha = $prefijoModalidad.date('Ymd').'-';

        $ultimo = WorklistOrden::where('sede_id', $sedeId)
            ->where('accession_no', 'like', $prefijoFecha.'%')
            ->orderBy('id', 'desc')
            ->value('accession_no');

        if ($ultimo) {
            $partes = explode('-', $ultimo);
            $consecutivo = (int) end($partes) + 1;
        } else {
            $consecutivo = 1;
        }

        return $prefijoFecha.str_pad((string) $consecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Genera un Study Instance UID único conforme al estándar DICOM.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function generarStudyIuid(): string
    {
        $timestamp = (int) (microtime(true) * 1000);
        $random = mt_rand(100000, 999999);

        return "1.2.826.0.1.3680043.9.7134.{$timestamp}.{$random}";
    }

    /**
     * Obtiene la lista de estaciones (AE Titles / Salas) registradas dinámicamente en el PACS de la sede.
     *
     * @param  int  $sedeId  ID de la sede asistencial
     * @param  string|null  $modality  Modalidad opcional para priorizar/filtrar equipos (ej. US, DX)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-22
     */
    public function obtenerEstacionesWorklist(int $sedeId, ?string $modality = null): array
    {
        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            return $this->error('Sede no encontrada.', 404);
        }

        // 1. Consultar dispositivos activos configurados en la tabla local config_worklist_dispositivos
        $queryDispositivos = ConfigWorklistDispositivo::query()
            ->where('sede_id', $sedeId)
            ->activos();

        if (! empty($modality)) {
            $modalityNorm = strtoupper(trim((string) $modality));
            if (in_array($modalityNorm, ['US', 'ECO'], true)) {
                $queryDispositivos->where('modulo_id', 2);
            } elseif (in_array($modalityNorm, ['DX', 'CR', 'RG', 'XR'], true)) {
                $queryDispositivos->where('modulo_id', 1);
            }
        }

        $dispositivosLocales = $queryDispositivos->orderBy('nombre')->get();
        if ($dispositivosLocales->isNotEmpty()) {
            $estaciones = [];
            foreach ($dispositivosLocales as $d) {
                $aet = $d->ae_title ?: $d->codigo;
                $etiqueta = ! empty($d->ubicacion)
                    ? "{$d->nombre} ({$aet}) — {$d->ubicacion}"
                    : "{$d->nombre} ({$aet})";

                $estaciones[] = [
                    'aet' => $aet,
                    'nombre' => $etiqueta,
                    'station_name' => $d->nombre,
                    'hostname' => $d->ip_address,
                    'es_ecografo' => (int) $d->modulo_id === 2,
                ];
            }

            return $this->success($estaciones, 'Estaciones obtenidas de la configuración de dispositivos.');
        }

        if (empty($sede->ip_dcm) || empty($sede->bd_dcm)) {
            return $this->error('La sede no tiene configurada la conexión al PACS.', 422);
        }

        $conexionOriginal = Config::get('database.default');
        try {
            Config::set('database.connections.mysql_sucursal', [
                'driver' => 'mysql',
                'host' => $sede->ip_dcm,
                'port' => $sede->puerto_dcm ?: 3306,
                'database' => $sede->bd_dcm,
                'username' => $sede->usuario_dcm,
                'password' => $sede->password_dcm,
                'charset' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'options' => [
                    \PDO::ATTR_TIMEOUT => 4,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
            ]);

            DB::purge('mysql_sucursal');
            $pacsConn = DB::connection('mysql_sucursal');

            // Excluir AETs de infraestructura y servidores que no son consolas de toma
            $infraAets = [
                'DCM4CHEE', 'CDRECORD', 'OVIYAM', 'OVIYAM2', 'WEASIS_AET',
            ];

            $query = $pacsConn->table('ae')
                ->whereNotIn('aet', $infraAets)
                ->where(function ($q) {
                    $q->whereNull('ae_desc')
                        ->orWhere('ae_desc', 'not like', '%archive instance%');
                })
                ->where('hostname', '!=', 'localhost');

            $nodos = $query->orderBy('aet')
                ->get(['aet', 'ae_desc', 'station_name', 'hostname', 'port']);

            $estaciones = [];
            $modalityNorm = strtoupper(trim((string) $modality));

            foreach ($nodos as $nodo) {
                $descripcion = trim($nodo->ae_desc ?: ($nodo->station_name ?: ''));
                $etiqueta = ! empty($descripcion) ? "{$nodo->aet} — {$descripcion}" : $nodo->aet;

                // Indicador de afinidad según modalidad para sugerir primero equipos de ultrasonido si aplica
                $esEcografo = false;
                $aetUpper = strtoupper($nodo->aet);
                $descUpper = strtoupper($descripcion);
                if (
                    str_contains($aetUpper, 'US') ||
                    str_contains($aetUpper, 'ECO') ||
                    str_contains($aetUpper, 'LOGIQ') ||
                    str_contains($aetUpper, 'VOLUSON') ||
                    str_contains($descUpper, 'ULTRASOUND') ||
                    str_contains($descUpper, 'ECOGRAF')
                ) {
                    $esEcografo = true;
                }

                $estaciones[] = [
                    'aet' => $nodo->aet,
                    'nombre' => $etiqueta,
                    'station_name' => $nodo->station_name ?: $nodo->aet,
                    'hostname' => $nodo->hostname,
                    'es_ecografo' => $esEcografo,
                ];
            }

            // Si se solicitó explícitamente modalidad US y existen equipos identificados como ecógrafos, ordenarlos primero
            if ($modalityNorm === 'US') {
                usort($estaciones, function ($a, $b) {
                    return ($b['es_ecografo'] ? 1 : 0) <=> ($a['es_ecografo'] ? 1 : 0);
                });
            }

            return $this->success($estaciones, 'Estaciones recuperadas exitosamente del PACS de la sede.');
        } catch (Exception $e) {
            Log::warning("Error consultando estaciones en PACS de sede {$sedeId}: {$e->getMessage()}");

            return $this->error('No fue posible consultar los equipos en el PACS de la sede ('.$e->getMessage().'). Verifique la conexión.', 500);
        } finally {
            Config::set('database.default', $conexionOriginal);
        }
    }

    /**
     * Envía un paciente admitido a la Modality Worklist (Dcm4chee y trazabilidad local).
     *
     * Flujo del proceso:
     * 1. Valida existencia de paciente, sede y producto.
     * 2. Conecta dinámicamente a la base de datos MySQL de Dcm4chee de la sede.
     * 3. Sincroniza o crea el paciente en la tabla 'patient' de Dcm4chee.
     * 4. Codifica el dataset DICOM (Little Endian Explicit VR) e inserta en 'mwl_item'.
     * 5. Registra la orden en la tabla 'worklist_ordenes' de LIDiag.
     *
     * @param  array  $data  Datos de la orden (paciente_id, sede_id, producto_id, accession_no, modality, station_aet)
     * @param  int  $usuarioId  ID del usuario que realiza la admisión
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function enviarAWorklist(array $data, int $usuarioId): array
    {
        $sedeId = (int) $data["sede_id"];
        $pacienteId = ! empty($data["paciente_id"]) ? (int) $data["paciente_id"] : 0;
        $productoId = (int) $data["producto_id"];

        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            return $this->error("Sede no encontrada.", 404);
        }

        $datosPacienteInterfaz = null;
        if ($pacienteId > 0) {
            $paciente = Paciente::find($pacienteId);
            if (! $paciente) {
                return $this->error("Paciente no encontrado.", 404);
            }
        } elseif (! empty($data["paciente_interfaz"]) && is_array($data["paciente_interfaz"])) {
            $datosPacienteInterfaz = $data["paciente_interfaz"];
            $docInterfaz = trim((string) ($datosPacienteInterfaz["num_docu"] ?? ""));
            if ($docInterfaz === "") {
                return $this->error("El número de documento del paciente es obligatorio.", 422);
            }

            // Reutilizar paciente existente si ya fue creado previamente por otro usuario sin duplicar
            $paciente = Paciente::where("num_docu", $docInterfaz)->first();
            if (! $paciente) {
                $paciente = new Paciente([
                    "tipo_documento_id" => $datosPacienteInterfaz["tipo_documento_id"] ?? null,
                    "num_docu" => $docInterfaz,
                    "nombres" => strtoupper(trim((string) ($datosPacienteInterfaz["nombres"] ?? ""))),
                    "seg_nom" => ! empty($datosPacienteInterfaz["seg_nom"]) ? strtoupper(trim((string) $datosPacienteInterfaz["seg_nom"])) : null,
                    "pri_ape" => ! empty($datosPacienteInterfaz["pri_ape"]) ? strtoupper(trim((string) $datosPacienteInterfaz["pri_ape"])) : null,
                    "seg_ape" => ! empty($datosPacienteInterfaz["seg_ape"]) ? strtoupper(trim((string) $datosPacienteInterfaz["seg_ape"])) : null,
                    "direccion" => $datosPacienteInterfaz["direccion"] ?? null,
                    "email" => $datosPacienteInterfaz["email"] ?? null,
                    "sexo" => strtoupper(trim((string) ($datosPacienteInterfaz["sexo"] ?? "M"))),
                    "fec_naci" => $datosPacienteInterfaz["fec_naci"] ?? null,
                    "tel" => $datosPacienteInterfaz["tel"] ?? null,
                    "cel" => $datosPacienteInterfaz["cel"] ?? null,
                ]);
            }
        } else {
            return $this->error("Debe especificar un paciente válido para admitir.", 422);
        }

        $producto = ConfigProducto::find($productoId);
        if (! $producto) {
            return $this->error("Procedimiento o producto no encontrado.", 404);
        }

        $modality = strtoupper(trim($data['modality'] ?? 'DX'));
        if (! in_array($modality, ['CR', 'DX', 'US'], true)) {
            $modality = 'DX';
        }

        $accessionNo = trim($data['accession_no'] ?? '');
        if (empty($accessionNo)) {
            $accessionNo = $this->generarAccessionSugerido($sedeId, $modality);
        }

        // Validar unicidad del Accession Number en órdenes activas de la sede
        $ordenExistente = WorklistOrden::where('sede_id', $sedeId)
            ->where('accession_no', $accessionNo)
            ->where('estado', 'PENDIENTE')
            ->first();

        if ($ordenExistente) {
            return $this->error("El Accession Number '{$accessionNo}' ya se encuentra activo en la Worklist de esta sede.", 422);
        }

        $stationAetInput = strtoupper(trim($data['station_aet'] ?? ''));
        $studyIuid = $this->generarStudyIuid();
        $mwlItemPk = null;

        // Conectar a la base de datos Dcm4chee de la sede
        $conexionOriginal = Config::get('database.default');
        try {
            Config::set('database.connections.mysql_sucursal', [
                'driver' => 'mysql',
                'host' => $sede->ip_dcm,
                'port' => $sede->puerto_dcm ?: 3306,
                'database' => $sede->bd_dcm,
                'username' => $sede->usuario_dcm,
                'password' => $sede->password_dcm,
                'charset' => 'latin1',
                'collation' => 'latin1_swedish_ci',
                'options' => [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
            ]);

            DB::purge('mysql_sucursal');
            $pacsConn = DB::connection('mysql_sucursal');

            // Determinar Station AET dinámicamente: usar el seleccionado o autodetectar de la tabla ae del PACS
            $stationAet = $stationAetInput;
            if (empty($stationAet)) {
                try {
                    $stationAet = $pacsConn->table('ae')
                        ->whereNotIn('aet', ['DCM4CHEE', 'CDRECORD', 'OVIYAM', 'OVIYAM2', 'WEASIS_AET'])
                        ->where(function ($q) {
                            $q->whereNull('ae_desc')
                                ->orWhere('ae_desc', 'not like', '%archive instance%');
                        })
                        ->where('hostname', '!=', 'localhost')
                        ->orderBy('pk')
                        ->value('aet');
                } catch (\Throwable $e) {
                    $stationAet = null;
                }
            }

            if (empty($stationAet)) {
                return $this->error('No se especificó la estación o equipo DICOM de destino, y no hay equipos configurados en el PACS de la sede.', 422);
            }
            $stationName = $stationAet;

            // Formatear nombre de paciente para DICOM: APELLIDOS^NOMBRES
            $apellidos = trim("{$paciente->pri_ape} {$paciente->seg_ape}");
            $nombres = trim("{$paciente->nombres} {$paciente->seg_nom}");
            $patName = strtoupper(trim("{$apellidos}^{$nombres}", '^ '));
            if (empty($patName)) {
                $patName = strtoupper(trim($paciente->nombres));
            }

            // Fecha de nacimiento en formato YYYYMMDD
            $patBirthdate = '';
            if ($paciente->fec_naci) {
                $patBirthdate = Carbon::parse($paciente->fec_naci)->format('Ymd');
            }

            $patSex = strtoupper(trim($paciente->sexo ?? 'O'));
            if (! in_array($patSex, ['M', 'F'], true)) {
                $patSex = 'O';
            }

            // 1. Sincronizar o crear en tabla 'patient' de Dcm4chee
            $pacienteDcm = $pacsConn->table('patient')
                ->where('pat_id', $paciente->num_docu)
                ->first();

            if ($pacienteDcm) {
                $patientFk = $pacienteDcm->pk;
                // Actualizar datos si han cambiado
                $pacsConn->table('patient')->where('pk', $patientFk)->update([
                    'pat_name' => $patName,
                    'pat_birthdate' => $patBirthdate ?: null,
                    'pat_sex' => $patSex,
                    'updated_time' => now(),
                ]);
            } else {
                $patientFk = $pacsConn->table('patient')->insertGetId([
                    'pat_id' => $paciente->num_docu,
                    'pat_name' => $patName,
                    'pat_birthdate' => $patBirthdate ?: null,
                    'pat_sex' => $patSex,
                    'created_time' => now(),
                    'updated_time' => now(),
                ]);
            }

            // 2. Codificar Dataset DICOM para mwl_item.item_attrs (Deflated Explicit VR Little Endian)
            $itemAttrsBinary = DicomDatasetBuilder::buildMwlItemAttrs([
                'accession_no' => $accessionNo,
                'modality' => $modality,
                'study_desc' => $producto->nom_produc,
                'pat_name' => $patName,
                'pat_id' => $paciente->num_docu,
                'pat_birthdate' => $patBirthdate,
                'pat_sex' => $patSex,
                'study_iuid' => $studyIuid,
                'req_proc_id' => $producto->cod_cups ?: (string) $producto->id,
                'station_aet' => $stationAet,
                'station_name' => $stationName,
                'sps_id' => $accessionNo,
                'sps_date' => date('Ymd'),
                'sps_time' => date('His'),
            ]);

            // 3. Insertar en tabla 'mwl_item' de Dcm4chee
            $mwlItemPk = $pacsConn->table('mwl_item')->insertGetId([
                'patient_fk' => $patientFk,
                'sps_status' => 0, // 0 = SCHEDULED
                'sps_id' => $accessionNo,
                'start_datetime' => now(),
                'station_aet' => $stationAet,
                'station_name' => $stationName,
                'modality' => $modality,
                'perf_physician' => null,
                'perf_phys_fn_sx' => '*',
                'perf_phys_gn_sx' => '*',
                'perf_phys_i_name' => null,
                'perf_phys_p_name' => null,
                'req_proc_id' => $producto->cod_cups ?: (string) $producto->id,
                'accession_no' => $accessionNo,
                'study_iuid' => $studyIuid,
                'created_time' => now(),
                'updated_time' => now(),
                'item_attrs' => $itemAttrsBinary,
            ]);

        } catch (Exception $e) {
            Log::error('Error insertando en Modality Worklist Dcm4chee: '.$e->getMessage(), [
                'sede_id' => $sedeId,
                'paciente_id' => $pacienteId,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('No fue posible conectar con el PACS Dcm4chee de la sede ('.$e->getMessage().'). Verifique la conexión de red.', 500);
        } finally {
            Config::set('database.default', $conexionOriginal);
        }

        // 4. Persistir paciente (si provenía de interfaz), orden de Worklist y registro en admin_admisiones de forma atómica
        $orden = DB::transaction(function () use (
            &$paciente,
            $sedeId,
            $productoId,
            $accessionNo,
            $studyIuid,
            $modality,
            $stationAet,
            $mwlItemPk,
            $data,
            $usuarioId
        ) {
            if (! $paciente->exists) {
                $existente = Paciente::where("num_docu", $paciente->num_docu)->lockForUpdate()->first();
                if ($existente) {
                    $paciente = $existente;
                } else {
                    $paciente->save();
                }
            }

            $nuevaOrden = WorklistOrden::create([
                "paciente_id" => $paciente->id,
                "sede_id" => $sedeId,
                "producto_id" => $productoId,
                "accession_no" => $accessionNo,
                "study_iuid" => $studyIuid,
                "modality" => $modality,
                "station_aet" => $stationAet,
                "mwl_item_pk" => $mwlItemPk,
                "ecografia_id" => ! empty($data["ecografia_id"]) ? (int) $data["ecografia_id"] : null,
                "ecografia_producto_id" => ! empty($data["ecografia_producto_id"]) ? (int) $data["ecografia_producto_id"] : null,
                "estado" => "PENDIENTE",
                "usuario_id" => $usuarioId,
                "observaciones" => $data["observaciones"] ?? null,
            ]);

            $nuevaOrden->load(["paciente", "producto", "sede"]);

            // Registrar admisión clínica sólo para flujo radiológico (no ecografías independientes)
            if (empty($data["ecografia_id"])) {
                app(AdminAdmisionService::class)->registrarDesdeWorklist(
                    $paciente,
                    $nuevaOrden,
                    $data,
                    $usuarioId
                );
            }

            return $nuevaOrden;
        });

        $orden->load("admision");

        $tipoModalidad = ($modality === "US") ? "Ecografía" : "Rayos X";

        return $this->success($orden, "Paciente admitido y enviado a la Worklist ({$tipoModalidad}) exitosamente con Accession No. '{$accessionNo}'.", 201);
    }

    /**
     * Cancela o retira una orden de la Modality Worklist tanto en Dcm4chee como en LIDiag.
     *
     * @param  int  $ordenId  ID de la orden en worklist_ordenes
     * @param  int  $usuarioId  ID del usuario que anula la orden
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function cancelarOrdenWorklist(int $ordenId, int $usuarioId): array
    {
        $orden = WorklistOrden::find($ordenId);
        if (! $orden) {
            return $this->error('Orden de Worklist no encontrada.', 404);
        }

        if ($orden->estado !== 'PENDIENTE') {
            return $this->error("La orden no se puede cancelar porque su estado actual es '{$orden->estado}'.", 422);
        }

        $sede = ConfigSede::find($orden->sede_id);
        if ($sede) {
            $conexionOriginal = Config::get('database.default');
            try {
                Config::set('database.connections.mysql_sucursal', [
                    'driver' => 'mysql',
                    'host' => $sede->ip_dcm,
                    'port' => $sede->puerto_dcm ?: 3306,
                    'database' => $sede->bd_dcm,
                    'username' => $sede->usuario_dcm,
                    'password' => $sede->password_dcm,
                    'charset' => 'latin1',
                    'collation' => 'latin1_swedish_ci',
                    'options' => [
                        \PDO::ATTR_TIMEOUT => 4,
                    ],
                ]);

                DB::purge('mysql_sucursal');
                $pacsConn = DB::connection('mysql_sucursal');

                // Eliminar el registro en Dcm4chee mwl_item para que desaparezca de la consola
                if ($orden->mwl_item_pk) {
                    $pacsConn->table('mwl_item')->where('pk', $orden->mwl_item_pk)->delete();
                } else {
                    $pacsConn->table('mwl_item')->where('accession_no', $orden->accession_no)->delete();
                }
            } catch (Exception $e) {
                Log::warning('Advertencia al retirar orden de Dcm4chee mwl_item: '.$e->getMessage());
                // Continuamos para cancelar en LIDiag aunque el PACS esté offline
            } finally {
                Config::set('database.default', $conexionOriginal);
            }
        }

        $orden->estado = "CANCELADO";
        $orden->observaciones = ($orden->observaciones ? $orden->observaciones." | " : "")."Cancelado por usuario ID: ".$usuarioId." en ".now();
        $orden->save();

        if (app(AdminAdmisionService::class)->tablaDisponible()) {
            AdminAdmision::where("worklist_orden_id", $orden->id)
                ->whereNotIn("estado", [AdminAdmision::ESTADO_ASIGNADO])
                ->update([
                    "estado" => AdminAdmision::ESTADO_CANCELADO,
                    "updated_at" => now(),
                ]);
        }

        return $this->success($orden, "Orden '{$orden->accession_no}' retirada de la Worklist exitosamente.");
    }

    /**
     * Reconcilia automáticamente las órdenes de Worklist con los estudios recibidos en el PACS Dcm4chee.
     *
     * Flujo del proceso:
     * 1. Determina las sedes a evaluar (una puntual o todas las sedes con PACS configurado).
     * 2. Consulta las órdenes en estado 'PENDIENTE' o 'ADQUIRIDO' de la sede.
     * 3. Conecta a la base de datos MySQL de Dcm4chee de cada sede y consulta la tabla 'study'.
     * 4. Si el estudio ya ingresó al PACS (coincidencia por accession_no o study_iuid):
     *    - Actualiza el estado en LIDiag a 'ADQUIRIDO' con timestamp y study_pk.
     *    - Actualiza 'sps_status = 1' (COMPLETED) en 'mwl_item' de Dcm4chee para limpiar la consola del equipo.
     * 5. Verifica si el estudio ya fue asignado en LIDiag para pasarlo directamente a 'COMPLETADO'.
     * 6. Retorna resumen cuantitativo de la reconciliación (evaluadas, adquiridas, completadas).
     *
     * @param  int|null  $sedeId  ID opcional de sede específica a reconciliar
     * @return array{
     *     status: int,
     *     message: string,
     *     data: array{
     *         total_evaluadas: int,
     *         adquiridas: int,
     *         completadas: int,
     *         detalles: array<int, array{orden_id: int, accession_no: string, sede: string, nuevo_estado: string, motivo: string}>
     *     }
     * }
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function reconciliarOrdenes(?int $sedeId = null): array
    {
        $sedesQuery = ConfigSede::query()
            ->whereNotNull('ip_dcm')
            ->where('ip_dcm', '!=', '')
            ->whereNotNull('bd_dcm')
            ->where('bd_dcm', '!=', '');

        if ($sedeId !== null && $sedeId > 0) {
            $sedesQuery->where('id', $sedeId);
        }

        $sedes = $sedesQuery->get();
        if ($sedes->isEmpty()) {
            return $this->error('No hay sedes con PACS Dcm4chee configurado para reconciliar.', 404);
        }

        $resumen = [
            'total_evaluadas' => 0,
            'adquiridas' => 0,
            'completadas' => 0,
            'detalles' => [],
        ];

        $conexionOriginal = Config::get('database.default');

        foreach ($sedes as $sede) {
            $ordenesPendientes = WorklistOrden::where('sede_id', $sede->id)
                ->whereIn('estado', ['PENDIENTE', 'ADQUIRIDO'])
                ->get();

            if ($ordenesPendientes->isEmpty()) {
                continue;
            }

            try {
                Config::set('database.connections.mysql_sucursal', [
                    'driver' => 'mysql',
                    'host' => $sede->ip_dcm,
                    'port' => $sede->puerto_dcm ?: 3306,
                    'database' => $sede->bd_dcm,
                    'username' => $sede->usuario_dcm,
                    'password' => $sede->password_dcm,
                    'charset' => 'latin1',
                    'collation' => 'latin1_swedish_ci',
                    'options' => [
                        \PDO::ATTR_TIMEOUT => 4,
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    ],
                ]);

                DB::purge('mysql_sucursal');
                $pacsConn = DB::connection('mysql_sucursal');

                foreach ($ordenesPendientes as $orden) {
                    $resumen['total_evaluadas']++;

                    // 1. Verificar si ya fue asignado en LIDiag (Estudios RX o Ecografías leídas)
                    $estudioLidiag = Estudio::where("sede_id", $sede->id)
                        ->where(function ($q) use ($orden) {
                            if (! empty($orden->accession_no)) {
                                $q->where("accession_no", $orden->accession_no);
                            }
                            if (! empty($orden->study_iuid)) {
                                $q->orWhere("study_iuid", $orden->study_iuid);
                            }
                        })
                        ->first(["id", "study_pk", "study_iuid", "study_id", "quien_registro_id", "created_at"]);

                    if (! $estudioLidiag && $orden->ecografia_id) {
                        $ecoLidiag = Ecografia::where("id", $orden->ecografia_id)
                            ->where("estado", "LEIDO")
                            ->first(["id", "estado"]);

                        if ($ecoLidiag && $orden->estado !== "COMPLETADO") {
                            $orden->estado = "COMPLETADO";
                            $orden->save();
                            $resumen["completadas"]++;
                            $resumen["detalles"][] = [
                                "orden_id" => $orden->id,
                                "accession_no" => $orden->accession_no,
                                "sede" => $sede->nom_sede,
                                "nuevo_estado" => "COMPLETADO",
                                "motivo" => "Ecografía #{$ecoLidiag->id} ya informada/leída en LIDiag.",
                            ];

                            continue;
                        }
                    }

                    if ($estudioLidiag) {
                        if ($orden->estado !== "COMPLETADO") {
                            $orden->estado = "COMPLETADO";
                            $orden->estudio_id = $estudioLidiag->id;
                            if ($estudioLidiag->study_pk) {
                                $orden->study_pk = (int) $estudioLidiag->study_pk;
                            }
                            $orden->save();
                            $resumen["completadas"]++;
                            $resumen["detalles"][] = [
                                "orden_id" => $orden->id,
                                "accession_no" => $orden->accession_no,
                                "sede" => $sede->nom_sede,
                                "nuevo_estado" => "COMPLETADO",
                                "motivo" => "Estudio #{$estudioLidiag->id} ya asignado en LIDiag.",
                            ];
                        }

                        if (! $orden->ecografia_id) {
                            app(AdminAdmisionService::class)->sincronizarDesdeReconciliacion(
                                $orden,
                                AdminAdmision::ESTADO_ASIGNADO,
                                null,
                                $estudioLidiag
                            );
                        }

                        continue;
                    }

                    // 2. Consultar estado del estudio en tabla study de Dcm4chee (para PENDIENTE y también enriquecer ADQUIRIDO)
                    $estudioPacs = null;
                    try {
                        $estudioPacs = $pacsConn->table("study")
                            ->where(function ($q) use ($orden) {
                                if (! empty($orden->accession_no)) {
                                    $q->where("accession_no", $orden->accession_no);
                                }
                                if (! empty($orden->study_iuid)) {
                                    $q->orWhere("study_iuid", $orden->study_iuid);
                                }
                            })
                            ->first([
                                "pk",
                                "study_iuid",
                                "study_id",
                                "accession_no",
                                "study_datetime",
                                "study_desc",
                                "mods_in_study",
                                "num_instances",
                            ]);
                    } catch (\Throwable $e) {
                        Log::warning("Error consultando tabla study en PACS sede {$sede->id}: {$e->getMessage()}");
                    }

                    if ($estudioPacs) {
                        $numInstancias = isset($estudioPacs->num_instances) ? (int) $estudioPacs->num_instances : 1;

                        if ($numInstancias > 0) {
                            $eraPendiente = ($orden->estado === "PENDIENTE");
                            $orden->estado = "ADQUIRIDO";
                            $orden->study_pk = (int) $estudioPacs->pk;
                            if (! empty($estudioPacs->study_iuid)) {
                                $orden->study_iuid = trim((string) $estudioPacs->study_iuid);
                            }
                            if (! $orden->adquirido_at) {
                                $orden->adquirido_at = $estudioPacs->study_datetime ?: now();
                            }
                            $orden->save();

                            if (! $orden->ecografia_id) {
                                app(AdminAdmisionService::class)->sincronizarDesdeReconciliacion(
                                    $orden,
                                    AdminAdmision::ESTADO_PENDIENTE_LECTURA,
                                    $estudioPacs
                                );
                            }

                            if ($eraPendiente) {
                                // Actualizar status en mwl_item de Dcm4chee para limpiar la consola de la sala
                                try {
                                    if ($orden->mwl_item_pk) {
                                        $pacsConn->table("mwl_item")->where("pk", $orden->mwl_item_pk)->update([
                                            "sps_status" => 1, // 1 = COMPLETED
                                            "updated_time" => now(),
                                        ]);
                                    } else {
                                        $pacsConn->table("mwl_item")->where("accession_no", $orden->accession_no)->update([
                                            "sps_status" => 1,
                                            "updated_time" => now(),
                                        ]);
                                    }
                                } catch (\Throwable $e) {
                                    Log::warning("No se pudo actualizar sps_status en mwl_item Dcm4chee: {$e->getMessage()}");
                                }

                                $resumen["adquiridas"]++;
                                $resumen["detalles"][] = [
                                    "orden_id" => $orden->id,
                                    "accession_no" => $orden->accession_no,
                                    "sede" => $sede->nom_sede,
                                    "nuevo_estado" => "ADQUIRIDO",
                                    "motivo" => "Imágenes detectadas en PACS ({$estudioPacs->num_instances} instancias) -> PENDIENTE LECTURA",
                                ];
                            }
                        } elseif (! $orden->ecografia_id) {
                            app(AdminAdmisionService::class)->sincronizarDesdeReconciliacion(
                                $orden,
                                AdminAdmision::ESTADO_TOMANDO_ESTUDIO,
                                $estudioPacs
                            );
                        }
                    } elseif ($orden->estado === "PENDIENTE" && ! $orden->ecografia_id) {
                        // Verificar si el tecnólogo ya inició la toma en consola (MPPS IN PROGRESS o MWL sps_status > 0)
                        $enTomaConsola = false;
                        try {
                            $mwlQuery = $pacsConn->table("mwl_item");
                            if ($orden->mwl_item_pk) {
                                $mwlQuery->where("pk", $orden->mwl_item_pk);
                            } else {
                                $mwlQuery->where("accession_no", $orden->accession_no);
                            }
                            $spsStatus = $mwlQuery->value("sps_status");
                            if ($spsStatus !== null && (int) $spsStatus > 0) {
                                $enTomaConsola = true;
                            } elseif (! empty($orden->accession_no)) {
                                $mppsIniciado = $pacsConn->table("mpps")
                                    ->where("accession_no", $orden->accession_no)
                                    ->where("mpps_status", 0)
                                    ->exists();
                                if ($mppsIniciado) {
                                    $enTomaConsola = true;
                                }
                            }
                        } catch (\Throwable) {
                            $enTomaConsola = false;
                        }

                        if ($enTomaConsola) {
                            app(AdminAdmisionService::class)->sincronizarDesdeReconciliacion(
                                $orden,
                                AdminAdmision::ESTADO_TOMANDO_ESTUDIO
                            );
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Error conectando a PACS de sede {$sede->id} durante reconciliación: {$e->getMessage()}");
            } finally {
                Config::set('database.default', $conexionOriginal);
            }
        }

        $mensaje = "Reconciliación completada: {$resumen['adquiridas']} orden(es) detectada(s) en PACS, {$resumen['completadas']} completada(s) por asignación.";

        return $this->success($resumen, $mensaje);
    }
}
