<?php

namespace App\Services\Estudios;

use App\Helpers\AuditLogger;
use App\Helpers\SahiConnectionHelper;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioSoportesHC;
use App\Models\General\Paciente;
use App\Models\Liquidacion\LiquidacionLinea;
use App\Models\User;
use App\Models\Worklist\WorklistOrden;
use App\Services\BaseService;
use App\Services\Interfaces\SAHITranscibirService;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstudioService extends BaseService
{
    /**
     * Constructor del servicio principal de estudios
     *
     * @param  PlantillaEstudioService  $plantillaEstudioService  Servicio de plantillas Word/PDF
     * @param  EstudioNotificacionService  $estudioNotificacionService  Servicio de notificaciones
     * @param  EstudioLockService  $lockService  Servicio de reservas de lectura/asignación
     * @param  EstudioQueryService  $queryService  Servicio de consultas de estudios
     * @param  SAHITranscibirService  $sahiTranscibirService  Servicio de transcripción hacia SAHI (SQL Server)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function __construct(
        private readonly PlantillaEstudioService $plantillaEstudioService,
        private readonly EstudioNotificacionService $estudioNotificacionService,
        private readonly EstudioLockService $lockService,
        private readonly EstudioQueryService $queryService,
        private readonly SAHITranscibirService $sahiTranscibirService,
    ) {}

    private const DESTINO_TELE_RADIOLOGIA = 'Tele Radiologia';

    /**
     * Obtiene todos los estudios registrados en el sistema
     *
     * @returns array Respuesta con la colección completa de estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->queryService->all();
    }

    /**
     * Crea un nuevo estudio con paciente, productos, diagnósticos y soportes
     *
     * @param  array  $registro  Datos del estudio incluyendo paciente, sede, médico, productos
     * @param  ?UploadedFile  $archivo1  Primer archivo soporte opcional
     * @param  ?UploadedFile  $archivo2  Segundo archivo soporte opcional
     * @param  ?UploadedFile  $archivo3  Tercer archivo soporte opcional
     *
     * @returns array Respuesta con el estudio creado dentro de transacción
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $registro, ?UploadedFile $archivo1 = null, ?UploadedFile $archivo2 = null, ?UploadedFile $archivo3 = null): array
    {
        $validated = $this->validateCreateData($registro);
        if ($validated !== null) {
            return $validated;
        }

        $user = Auth::user();
        if ($user && ! $user->transcrip_remoto && empty($registro['motivo_examen'])) {
            return $this->validationError(['motivo_examen' => ['El motivo del examen es obligatorio.']]);
        }

        $productosMarcados = $this->getCheckedProducts($registro['productosEstudio'] ?? []);
        if (empty($productosMarcados)) {
            return $this->validationError(['productosEstudio' => ['Debe seleccionar al menos un producto.']]);
        }

        $nombrePaciente = $this->sanitizePatientName($registro['nombres']);

        // Validación de reserva: otro usuario con el mismo estudio DICOM abierto bloquea el registro.
        $claveAsignacion = $this->lockService->resolverClave('asignacion', $registro);
        if ($claveAsignacion !== null) {
            $conflicto = $this->lockService->verificar('asignacion', $claveAsignacion);
            if ($conflicto !== null) {
                return $conflicto;
            }
        }

        try {
            return DB::transaction(function () use ($registro, $nombrePaciente, $productosMarcados, $archivo1, $archivo2, $archivo3, $claveAsignacion) {
                $paciente = $this->createOrUpdatePatient($registro, $nombrePaciente);

                $sede = ConfigSede::findOrFail($registro['sede_id']);
                $interfaceData = $this->resolveInterfaceData($sede, $registro);

                $estudio = Estudio::create([
                    'sede_id' => $registro['sede_id'],
                    'study_pk' => $registro['study_pk'] ?? null,
                    'study_iuid' => $registro['study_iuid'] ?? null,
                    'study_id' => $registro['study_id'] ?? null,
                    'fec_estudio' => $registro['study_datetime'] ?? null,
                    'accession_no' => $registro['accession_no'] ?? null,
                    'study_desc' => $registro['study_desc'] ?? null,
                    'mods_in_study' => $registro['mods_in_study'] ?? null,
                    'email_reportar' => $registro['email_reportar'] ?? null,
                    'paciente_id' => $paciente->id,
                    'medico_id' => $registro['medico_id'] ?? null,
                    'quien_registro_id' => Auth::id(),
                    'prioridad_id' => $registro['prioridad_id'],
                    'observaciones' => $registro['observaciones'] ?? null,
                    'motivo_examen' => $registro['motivo_examen'] ?? null,
                    'atencion' => $registro['atencion_id'] ?? null,
                    'servicio' => $interfaceData['servicio'],
                    'eps' => $interfaceData['eps'],
                    'contrato' => $interfaceData['contrato'],
                    'plan' => $interfaceData['plan'],
                    'destino_estudio' => $registro['destino_estudio'] ?? null,
                    'destino_estudio_id' => $registro['destino_estudio_id'] ?? null,
                    'estado' => 1,
                ]);

                $this->syncStudyProducts($estudio->id, $productosMarcados);
                $this->syncStudyDiagnostics($estudio->id, $registro['diagnosticosEstudio'] ?? []);
                $this->processSoportes($estudio->id, $archivo1, $archivo2, $archivo3);
                $this->autoReconciliarWorklistOrden($estudio, $registro);

                AuditLogger::log(
                    'asignar_estudio',
                    $estudio,
                    "Asignación de estudio #{$estudio->id} ({$estudio->study_desc}) — Paciente: {$nombrePaciente}, Médico ID: {$estudio->medico_id}, Prioridad ID: {$estudio->prioridad_id}"
                );

                // Asignación registrada: se libera la reserva (si la transacción revierte, vuelve a existir).
                if ($claveAsignacion !== null) {
                    $this->lockService->liberarPorClave('asignacion', $claveAsignacion);
                }

                return $this->created($estudio, 'La asignación de la lectura se realizó correctamente.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al crear estudio: '.$e->getMessage());

            return $this->error('Error al crear el estudio.', 500);
        }
    }

    /**
     * Lista estudios pendientes de lectura para un médico en una sede específica
     *
     * @param  int  $medicoId  ID del médico
     * @param  int  $sedeId  ID de la sede
     *
     * @returns array Respuesta con la lista de estudios pendientes priorizados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarPendientesMedico(int $medicoId, int $sedeId): array
    {
        return $this->queryService->listarPendientesMedico($medicoId, $sedeId);
    }

    /**
     * Registra la lectura de un estudio producto y marca fecha/hora
     *
     * @param  int  $estudioProductoId  ID del producto de estudio
     * @param  string  $lectura  Texto de la lectura realizada
     *
     * @returns array Respuesta de confirmación de la lectura
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function leerEstudio(int $estudioProductoId, string $lectura): array
    {
        // Validación de reserva: otro usuario con el estudio abierto bloquea el guardado.
        $conflicto = $this->lockService->verificar('lectura', $this->lockService->claveLectura($estudioProductoId));
        if ($conflicto !== null) {
            return $conflicto;
        }

        try {
            return DB::transaction(function () use ($estudioProductoId, $lectura) {
                $item = EstudioProducto::lockForUpdate()->findOrFail($estudioProductoId);

                $estudio = $item->estudio;

                if ($estudio === null || $estudio->medico_id === null) {
                    return $this->error('Este estudio no tiene médico asignado. Asigne un médico antes de leerlo.', 422);
                }

                if ((int) $estudio->medico_id !== (int) Auth::id()) {
                    return $this->error('Solo el médico asignado puede registrar la lectura de este estudio.', 403);
                }

                if ($item->fechor_lectura !== null) {
                    return $this->error('Este estudio ya fue leido por otro medico. Actualice la pagina para ver los cambios.', 409);
                }

                $item->lectura = $lectura;
                $item->fechor_lectura = Carbon::now();
                $item->save();

                AuditLogger::log(
                    'lectura_estudio',
                    $item,
                    "Lectura médica registrada para producto de estudio #{$item->id} (Estudio #{$estudio->id}) por médico: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
                );

                // Guardado exitoso: se libera la reserva del médico.
                $this->lockService->liberarPorClave('lectura', $this->lockService->claveLectura($estudioProductoId));

                try {
                    app(LiquidacionService::class)->registrarLectura($item);
                } catch (\Throwable $e) {
                    Log::error('Error al registrar línea de liquidación: '.$e->getMessage());
                }

                return $this->updated(null, 'La lectura se realizo correctamente.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al leer estudio: '.$e->getMessage());

            return $this->error('Error al procesar la lectura.', 500);
        }
    }

    /**
     * Marca un producto de estudio como visto por el médico
     *
     * @param  int  $id  ID del producto de estudio
     *
     * @returns array Respuesta de confirmación del marcado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function marcarVisto(int $id): array
    {
        $item = EstudioProducto::findOrFail($id);
        $item->visto = 1;
        $item->save();

        return $this->updated(null, 'El estudio se marcó como visto correctamente.');
    }

    /**
     * Lista los diagnósticos asociados a un estudio específico
     *
     * @param  int  $estudioId  ID del estudio
     *
     * @returns array Respuesta con la lista de diagnósticos del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarDiagnosticosPorEstudio(int $estudioId): array
    {
        return $this->queryService->listarDiagnosticosPorEstudio($estudioId);
    }

    /**
     * Lista estudios pendientes de transcripción con filtros aplicados
     *
     * @param  array  $filters  Filtros: sede_id, destino_estudio, fechas, nombre/doc, médico
     *
     * @returns array Respuesta con la lista de estudios pendientes de transcribir
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarPendientesTranscribir(array $filters): array
    {
        return $this->queryService->listarPendientesTranscribir($filters);
    }

    /**
     * Lista estudios asignados a médicos con filtros de búsqueda
     *
     * @param  array  $filters  Filtros: sede_id, fechas, nombre/doc, quién asignó, médico
     *
     * @returns array Respuesta con la lista de estudios asignados ordenados por fecha
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarEstudiosAsignados(array $filters): array
    {
        return $this->queryService->listarEstudiosAsignados($filters);
    }

    /**
     * Guarda la transcripción clínica de un estudio y programa la generación de PDF,
     * sincronización SAHI y envío al portal tras enviar la respuesta HTTP al cliente.
     *
     * Flujo del proceso:
     * 1. Valida en milisegundos que la sede tenga plantilla configurada y médico SAHI válido.
     * 2. Bloquea el registro de estudios_productos evitando doble transcripción concurrente.
     * 3. Actualiza los datos del paciente por llave primaria (paciente_id) solo si hubo cambios.
     * 4. Persiste la transcripción, marca visto = 1 y registra el evento en audit_logs.
     * 5. Difiere la conversión pesada de Word/PDF (LibreOffice), sincronización SAHI y envío al portal con app()->terminating().
     *
     * @param array{
     *     estudio_producto_id: int,
     *     lectura: string,
     *     num_docu: string,
     *     nom_pacien: string,
     *     sede_id: int,
     *     cod_cups: string,
     *     nom_produc: string,
     *     id_estudio?: int,
     *     email?: string,
     *     tel?: string,
     *     medico_id?: int,
     *     fechor_lectura?: string,
     *     fechor_trascrito?: string,
     *     direccion?: string,
     *     sexo?: string,
     *     fec_naci?: string,
     *     destino_estudio?: string,
     *     atencion?: string|int|null,
     *     atencion_id?: int|null,
     * } $data
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function guardarTranscripcion(array $data): array
    {
        try {
            $result = DB::transaction(function () use ($data) {
                $sede = ConfigSede::findOrFail($data['sede_id']);

                if (! $sede->plantilla_rx) {
                    return $this->error(
                        'La plantilla para la sede '.($sede->nom_sede ?? 'N/A').' no está configurada.',
                        402
                    );
                }

                $templatePath = storage_path('app/plantillas_lecturas/'.$sede->plantilla_rx);
                if (! is_file($templatePath)) {
                    return $this->error('Archivo de plantilla no encontrado.', 404);
                }

                if ($sede->interface_software === 'SAHI') {
                    $medico = User::find($data['medico_id']);
                    if (! $medico || ! $medico->interface_id) {
                        return $this->error('El médico seleccionado no está registrado en la interfaz.', 400);
                    }
                }

                $producto = EstudioProducto::lockForUpdate()->findOrFail($data['estudio_producto_id']);

                if ($producto->fechor_trascrito !== null) {
                    return $this->error('Esta transcripción ya fue procesada por otro usuario. Actualice la página.', 409);
                }

                $estudio = ! empty($data['id_estudio'])
                    ? Estudio::find($data['id_estudio'])
                    : $producto->estudio;

                $existingPaciente = ($estudio && $estudio->paciente_id)
                    ? Paciente::find($estudio->paciente_id)
                    : null;

                if ($existingPaciente === null || (string) $existingPaciente->num_docu !== (string) $data['num_docu']) {
                    $existingPaciente = Paciente::where('num_docu', $data['num_docu'])->first();
                }

                $pacienteAttrs = [
                    'num_docu' => $data['num_docu'],
                    'nombres' => $data['nom_pacien'],
                    'direccion' => $data['direccion'] ?? ($existingPaciente->direccion ?? null),
                    'sexo' => $data['sexo'] ?? ($existingPaciente->sexo ?? null),
                    'fec_naci' => $data['fec_naci'] ?? ($existingPaciente->fec_naci ?? null),
                    'tel' => $data['tel'] ?? ($existingPaciente->tel ?? null),
                    'email' => $data['email'] ?? ($existingPaciente->email ?? null),
                ];

                if ($existingPaciente !== null) {
                    $existingPaciente->fill($pacienteAttrs);
                    if ($existingPaciente->isDirty()) {
                        $existingPaciente->save();
                    }
                } else {
                    Paciente::create($pacienteAttrs);
                }

                $producto->lectura = $data['lectura'];
                $producto->transcriptor_id = Auth::id();
                $producto->fechor_trascrito = Carbon::now()->format('Y-m-d H:i:s');
                $producto->visto = 1;
                $producto->save();

                AuditLogger::log(
                    'transcripcion_estudio',
                    $producto,
                    "Transcripción clínica registrada para producto de estudio #{$producto->id} (Estudio #{$producto->estudio_id}) por transcriptor: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
                );

                if (! empty($data['destino_estudio']) && $data['destino_estudio'] === self::DESTINO_TELE_RADIOLOGIA && $estudio) {
                    $estudio->medico_id = $data['medico_id'];
                    $estudio->save();
                }

                return $this->success([
                    'producto_id' => $producto->id,
                    'estudio_id' => $estudio?->id ?? $producto->estudio_id,
                    'atencion_estudio' => $estudio?->atencion,
                    'medico_estudio_id' => $estudio?->medico_id,
                    'sede_id' => $sede->id,
                    'interface_software' => $sede->interface_software,
                    'cod_cups' => $producto->cod_cups,
                    'lectura' => $producto->lectura,
                ], 'La transcripción se guardó correctamente.');
            });

            if (! ($result['success'] ?? false)) {
                return $result;
            }

            $info = $result['data'] ?? [];

            if (app()->runningInConsole()) {
                $this->procesarPostTranscripcion($data, $info);
            } else {
                app()->terminating(function () use ($data, $info) {
                    @ignore_user_abort(true);
                    if (function_exists('fastcgi_finish_request')) {
                        @fastcgi_finish_request();
                    } else {
                        while (ob_get_level() > 0) {
                            @ob_end_flush();
                        }
                        @flush();
                    }

                    $this->procesarPostTranscripcion($data, $info);
                });
            }

            return $this->success(null, 'La transcripción se guardó correctamente.');
        } catch (\Throwable $e) {
            Log::error('Error al guardar transcripción: '.$e->getMessage());

            return $this->error('No fue posible guardar la transcripción.', 500);
        }
    }

    /**
     * Ejecuta en segundo plano las tareas pesadas posteriores al guardado de la transcripción
     * (sincronización con SAHI SQL Server, generación de Word/PDF con LibreOffice y envío al Portal de Pacientes).
     *
     * Flujo del proceso:
     * 1. Si la sede tiene interfaz SAHI y número de atención válido, transmite la lectura a SQL Server y guarda la trazabilidad.
     * 2. Genera el reporte PDF con PlantillaEstudioService::editWord().
     * 3. Envía el reporte generado al portal de pacientes y limpia el archivo temporal.
     *
     * @param  array  $data  Datos de la transcripción enviados desde el controlador
     * @param  array  $info  Metadatos del estudio y sede persistidos en la transacción
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function procesarPostTranscripcion(array $data, array $info): void
    {
        // 1. Sincronización hacia SAHI (SQL Server) si la sede tiene la interfaz habilitada
        if (($info['interface_software'] ?? '') === 'SAHI' && ! empty($info['producto_id'])) {
            try {
                $producto = EstudioProducto::find($info['producto_id']);
                if ($producto) {
                    $atencionId = ! empty($data['atencion_id']) ? (int) $data['atencion_id'] : null;
                    if (! $atencionId && ! empty($data['atencion']) && is_numeric($data['atencion'])) {
                        $atencionId = (int) $data['atencion'];
                    }
                    if (! $atencionId && ! empty($info['atencion_estudio']) && is_numeric($info['atencion_estudio'])) {
                        $atencionId = (int) $info['atencion_estudio'];
                    }

                    if ($atencionId > 0) {
                        $sedeActual = ! empty($info['sede_id']) ? ConfigSede::find($info['sede_id']) : null;
                        SahiConnectionHelper::configureForSede($sedeActual);

                        $sahiResult = $this->sahiTranscibirService->enviarEstudio(
                            $atencionId,
                            (int) ($data['medico_id'] ?? $info['medico_estudio_id'] ?? 0),
                            $info['lectura'] ?? $data['lectura'],
                            $info['cod_cups'] ?? $producto->cod_cups,
                            $data['fechor_lectura'] ?? Carbon::now()->format('Y-m-d H:i:s')
                        );

                        $this->actualizarEstadoSahiProducto($producto, $sahiResult);
                    } else {
                        Log::warning("Estudio producto #{$producto->id} pertenece a sede SAHI pero no cuenta con atencion_id asociada para sincronizar.");
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Error en sincronización SAHI post-transcripción: '.$e->getMessage());
            }
        }

        // 2. Generación de reporte PDF y envío al Portal de Pacientes
        try {
            $plantillaResult = $this->plantillaEstudioService->editWord($data);
            if ($plantillaResult['success'] ?? false) {
                $this->estudioNotificacionService->enviarPortalPacientes((object) $data);
                $outputName = $plantillaResult['data']['output_name'] ?? '';
                if ($outputName !== '') {
                    $this->plantillaEstudioService->eliminarReporte($outputName);
                }
            } else {
                Log::warning('No fue posible generar el PDF post-transcripción: '.($plantillaResult['message'] ?? 'Error desconocido'));
            }
        } catch (\Throwable $e) {
            Log::warning('Error al enviar al portal de pacientes post-transcripción: '.$e->getMessage());
        }
    }

    /**
     * Lista estudios pendientes por leer y transcribir sin filtros
     *
     * @returns array Respuesta con la lista de estudios pendientes priorizados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function pendientesPorLeerTranscribir(): array
    {
        return $this->queryService->pendientesPorLeerTranscribir();
    }

    /**
     * Verifica si un paciente tiene estudios registrados por número de documento
     *
     * @param  string  $numDocu  Número de documento del paciente
     *
     * @returns array Respuesta con indicador booleano si tiene estudios
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function buscarPorNumDocu(string $numDocu): array
    {
        return $this->queryService->buscarPorNumDocu($numDocu);
    }

    /**
     * Anula la asignación de un producto de estudio (lo desactiva)
     *
     * @param  int  $id  ID del producto de estudio a anular
     *
     * @returns array Respuesta de confirmación de anulación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function anularAsignacion(int $id): array
    {
        try {
            return DB::transaction(function () use ($id) {
                $producto = EstudioProducto::lockForUpdate()->findOrFail($id);

                if ($producto->activo == 0) {
                    return $this->error('Este producto ya fue anulado.', 409);
                }

                $producto->activo = 0;
                $producto->save();

                AuditLogger::log(
                    'anular_estudio',
                    $producto,
                    "Asignación anulada para producto de estudio #{$producto->id} por usuario: ".(Auth::user()?->name ?? ('ID: '.Auth::id()))
                );

                LiquidacionLinea::where('estudio_producto_id', $producto->id)->update(['estado' => 0]);

                return $this->updated(null, 'El producto se anuló correctamente.');
            });
        } catch (\Throwable $e) {
            Log::error('Error al anular asignación: '.$e->getMessage());

            return $this->error('No fue posible anular el producto.', 500);
        }
    }

    /**
     * Obtiene la ruta de un archivo PDF de lectura para descarga
     *
     * @param  string  $filename  Nombre del archivo PDF
     *
     * @returns array Respuesta con la ruta y nombre del archivo o error 404
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function descargarPdfLectura(string $filename): array
    {
        $path = storage_path('app/reporte_lecturas/'.$filename);

        if (! file_exists($path)) {
            return $this->notFound('Archivo no encontrado');
        }

        return $this->success(['path' => $path, 'filename' => $filename]);
    }

    /**
     * @param array{
     *     id_estudio: int,
     *     sede_id: int,
     *     email: string,
     *     num_docu: string,
     *     nom_pacien: string,
     *     lectura: string,
     *     estudio_producto_id: int,
     *     cod_cups: string,
     *     nom_produc: string,
     * } $data
     */
    public function notificarEmail(array $data): array
    {
        $plantillaResult = $this->plantillaEstudioService->editWord($data);

        if (! $plantillaResult['success']) {
            return $plantillaResult;
        }

        $this->estudioNotificacionService->enviarPortalPacientes((object) $data);

        $estudio = Estudio::find($data['id_estudio'] ?? null);
        if ($estudio && ! empty($plantillaResult['data']['output_name'])) {
            $this->estudioNotificacionService->notificarEmail(
                $estudio,
                $plantillaResult['data']['output_name']
            );
        }

        $outputName = $plantillaResult['data']['output_name'] ?? '';
        $this->plantillaEstudioService->eliminarReporte($outputName);

        return $this->success(null, 'Notificación reenviada correctamente.');
    }

    /**
     * @param array{
     *     sede_id: int,
     *     num_docu: string,
     *     nom_pacien: string,
     *     lectura: string,
     *     estudio_producto_id: int,
     *     cod_cups: string,
     *     nom_produc: string,
     * } $data
     */
    public function enviarPortalPacientes(array $data): array
    {
        $plantillaResult = $this->plantillaEstudioService->editWord($data);

        if (! $plantillaResult['success']) {
            return $plantillaResult;
        }

        $result = $this->estudioNotificacionService->enviarPortalPacientes((object) $data);

        $outputName = $plantillaResult['data']['output_name'] ?? '';
        $this->plantillaEstudioService->eliminarReporte($outputName);

        return $result;
    }

    /**
     * Valida los datos mínimos de una asignación antes de crear el estudio.
     *
     * El médico es obligatorio salvo cuando el destino es tele-radiología.
     *
     * @param  array  $registro  Datos del formulario de asignación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function validateCreateData(array $registro): ?array
    {
        $destino = $registro['destino_estudio'] ?? '';
        $rules = [
            'prioridad_id' => 'required|integer|not_in:0',
            'num_docu' => 'required',
            'nombres' => 'required',
            'productosEstudio' => 'required|array|min:1',
        ];

        $messages = [
            'prioridad_id.required' => 'Te hizo falta la prioridad del estudio.',
            'prioridad_id.not_in' => 'Te hizo falta la prioridad del estudio.',
            'num_docu.required' => 'Te hizo falta el número de documento del paciente.',
            'nombres.required' => 'Te hizo falta los nombres del paciente.',
            'productosEstudio.required' => 'Debe elegir al menos un producto.',
            'productosEstudio.min' => 'Debe elegir al menos un producto.',
        ];

        if ($destino !== self::DESTINO_TELE_RADIOLOGIA) {
            $rules['medico_id'] = 'required|integer|not_in:0';
            $messages['medico_id.required'] = 'Te hizo falta el medico.';
            $messages['medico_id.not_in'] = 'Te hizo falta el medico.';
        }

        $validated = validator($registro, $rules, $messages);

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        return null;
    }

    /**
     * Filtra la lista de productos marcados (checked = true).
     *
     * @param  array  $productos  Productos enviados desde el formulario
     *
     * @returns array Productos marcados sin claves numéricas huecas
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function getCheckedProducts(array $productos): array
    {
        return array_values(array_filter($productos, fn ($p) => isset($p['checked']) && $p['checked'] == true));
    }

    /**
     * Normaliza el nombre del paciente separado por el delimitador "^" del PACS.
     *
     * @param  string  $name  Nombre crudo proveniente del DICOM
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function sanitizePatientName(string $name): string
    {
        $name = str_replace('^^^', '', $name);
        $name = str_replace(' ^', '', $name);

        return str_replace('^', ' ', $name);
    }

    /**
     * Crea o actualiza el paciente por número de documento, conservando campos vacíos del existente.
     *
     * Flujo del proceso:
     * 1. Busca el paciente existente por número de documento una única vez.
     * 2. Arma los atributos conservando los valores previos cuando el formulario envía campos vacíos.
     * 3. Si el paciente existe, actualiza únicamente si hubo cambios (evita segundo SELECT y UPDATE innecesario).
     * 4. Si no existe, inserta el nuevo registro de paciente.
     *
     * @param  array  $registro  Datos del formulario de asignación
     * @param  string  $nombrePaciente  Nombre ya saneado
     * @return Paciente Instancia del paciente creado o actualizado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function createOrUpdatePatient(array $registro, string $nombrePaciente): Paciente
    {
        $existing = Paciente::where('num_docu', $registro['num_docu'])->first();

        $attributes = [
            'num_docu' => $registro['num_docu'],
            'nombres' => $nombrePaciente,
            'direccion' => ! empty($registro['direccion']) ? $registro['direccion'] : ($existing->direccion ?? ''),
            'sexo' => ! empty($registro['sexo']) ? $registro['sexo'] : ($existing->sexo ?? ''),
            'fec_naci' => ! empty($registro['fec_naci']) ? $registro['fec_naci'] : ($existing->fec_naci ?? null),
            'tel' => ! empty($registro['tel']) ? $registro['tel'] : ($registro['telefono'] ?? ($existing->tel ?? '')),
            'email' => ! empty($registro['email']) ? $registro['email'] : ($existing->email ?? ''),
        ];

        if ($existing !== null) {
            $existing->fill($attributes);
            if ($existing->isDirty()) {
                $existing->save();
            }

            return $existing;
        }

        return Paciente::create($attributes);
    }

    /**
     * Resuelve los datos de interfaz (eps, contrato, plan, servicio) según el software de la sede.
     *
     * @param  ConfigSede  $sede  Sede del estudio
     * @param  array  $registro  Datos del formulario de asignación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function resolveInterfaceData(ConfigSede $sede, array $registro): array
    {
        $default = ['eps' => '', 'contrato' => '', 'plan' => '', 'servicio' => ''];

        if ($sede->interface_software !== 'SAHI') {
            return $default;
        }

        return $default;
    }

    /**
     * Envía manualmente un producto de estudio a SAHI (SQL Server).
     *
     * Flujo del proceso:
     * 1. Consulta el producto de estudio con su estudio padre y sede asociada.
     * 2. Valida que la sede posea interface_software = 'SAHI'.
     * 3. Valida que el producto cuente con transcripción guardada y número de atención válido.
     * 4. Configura dinámicamente la conexión a SQL Server según la sede.
     * 5. Invoca SAHITranscibirService con detección de esquemas existentes.
     * 6. Actualiza el estado de tracking en estudios_productos y retorna la respuesta.
     *
     * @param  int  $estudioProductoId  Identificador del registro en estudios_productos
     * @return array<string, mixed> Resultado del despacho con estado y mensajes
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function enviarSahiEstudioProducto(int $estudioProductoId): array
    {
        $producto = EstudioProducto::with(['estudio.sede'])->find($estudioProductoId);
        if (! $producto) {
            return $this->error('No se encontró el producto de estudio especificado.', 404);
        }

        $estudio = $producto->estudio;
        if (! $estudio) {
            return $this->error('El producto no tiene un estudio padre asociado.', 422);
        }

        $sede = $estudio->sede;
        if (! $sede || $sede->interface_software !== 'SAHI') {
            return $this->error('La sede de este estudio no tiene habilitada la interfaz SAHI.', 422);
        }

        if (empty($producto->lectura)) {
            return $this->error('El estudio no cuenta con transcripción/lectura guardada para enviar a SAHI.', 422);
        }

        $atencionId = (int) $estudio->atencion;
        if ($atencionId <= 0) {
            return $this->error('El estudio no cuenta con un número de atención SAHI válido.', 422);
        }

        SahiConnectionHelper::configureForSede($sede);

        $sahiResult = $this->sahiTranscibirService->enviarEstudio(
            $atencionId,
            (int) $estudio->medico_id,
            $producto->lectura,
            $producto->cod_cups,
            $producto->fechor_lectura ?? Carbon::now()->format('Y-m-d H:i:s')
        );

        $this->actualizarEstadoSahiProducto($producto, $sahiResult);

        return $sahiResult;
    }

    /**
     * Actualiza el estado de sincronización SAHI en el producto del estudio.
     *
     * Flujo del proceso:
     * 1. Verifica si la tabla estudios_productos cuenta con la columna estado_envio_sahi.
     * 2. Si el resultado de SAHI fue exitoso, persiste estado ENVIADO y los consecutivos generados.
     * 3. Si falló, persiste estado ERROR y el mensaje descriptivo del fallo.
     *
     * @param  EstudioProducto  $producto  Instancia del producto de estudio
     * @param  array  $sahiResult  Resultado retornado por SAHITranscibirService
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function actualizarEstadoSahiProducto(EstudioProducto $producto, array $sahiResult): void
    {
        if (! Schema::hasColumn('estudios_productos', 'estado_envio_sahi')) {
            if (! ($sahiResult['success'] ?? false)) {
                Log::warning('Sincronización SAHI para estudio #'.$producto->id.' falló: '.($sahiResult['message'] ?? 'Error desconocido'));
            }

            return;
        }

        if ($sahiResult['success'] ?? false) {
            $dataSahi = $sahiResult['data'] ?? [];
            $producto->update([
                'estado_envio_sahi' => 'ENVIADO',
                'fechor_envio_sahi' => Carbon::now(),
                'sahi_esquema_149' => $dataSahi['conseEsquemaEncavezado'] ?? null,
                'sahi_esquema_150' => $dataSahi['conseEsquemaEncavezado2'] ?? null,
                'error_envio_sahi' => null,
            ]);
        } else {
            $producto->update([
                'estado_envio_sahi' => 'ERROR',
                'fechor_envio_sahi' => Carbon::now(),
                'error_envio_sahi' => $sahiResult['message'] ?? 'Error desconocido en SAHI',
            ]);
        }
    }

    /**
     * Registra en bloque los productos del estudio que requieren lectura (requi_lectura = 1).
     *
     * Flujo del proceso:
     * 1. Extrae los códigos CUPS únicos enviados en los productos marcados.
     * 2. Consulta en una sola sentencia los productos del catálogo con requi_lectura = 1.
     * 3. Construye las filas válidas y las inserta por lote en estudios_productos.
     *
     * @param  int  $estudioId  Identificador del estudio recién creado
     * @param  array  $productosMarcados  Productos marcados en el formulario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function syncStudyProducts(int $estudioId, array $productosMarcados): void
    {
        $codigosCups = array_values(array_unique(array_filter(array_column($productosMarcados, 'cod_cups'))));
        if (empty($codigosCups)) {
            return;
        }

        $productosCatalogo = ConfigProducto::whereIn('cod_cups', $codigosCups)
            ->where('requi_lectura', 1)
            ->get()
            ->keyBy('cod_cups');

        $now = Carbon::now();
        $rows = [];

        foreach ($productosMarcados as $producto) {
            $cups = $producto['cod_cups'] ?? null;
            $productoDicom = $cups !== null ? $productosCatalogo->get($cups) : null;

            if (! $productoDicom) {
                continue;
            }

            $rows[] = [
                'estudio_id' => $estudioId,
                'producto_id' => $productoDicom->id,
                'cod_cups' => $productoDicom->cod_cups,
                'nom_produc' => $productoDicom->nom_produc,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            EstudioProducto::insert($rows);
        }
    }

    /**
     * Registra en bloque los diagnósticos válidos (con código y nombre) asociados al estudio.
     *
     * Flujo del proceso:
     * 1. Filtra los diagnósticos que cuenten con código y nombre no vacíos.
     * 2. Inserta los diagnósticos en una sola operación por lote en estudios_diagnosticos.
     *
     * @param  int  $estudioId  Identificador del estudio recién creado
     * @param  array  $diagnosticos  Diagnósticos enviados desde el formulario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function syncStudyDiagnostics(int $estudioId, array $diagnosticos): void
    {
        if (empty($diagnosticos)) {
            return;
        }

        $now = Carbon::now();
        $rows = [];

        foreach ($diagnosticos as $diagnostico) {
            if (empty($diagnostico['cod_diagnos']) || empty($diagnostico['nom_diagnos'])) {
                continue;
            }

            $rows[] = [
                'estudio_id' => $estudioId,
                'cod_diagnos' => $diagnostico['cod_diagnos'],
                'nom_diagnos' => $diagnostico['nom_diagnos'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            EstudioDiagnostico::insert($rows);
        }
    }

    /**
     * Guarda los soportes de historia clínica encriptados y registra su metadata.
     *
     * @param  int  $estudioId  Identificador del estudio
     * @param  UploadedFile|null  $archivo1  Primer soporte opcional
     * @param  UploadedFile|null  $archivo2  Segundo soporte opcional
     * @param  UploadedFile|null  $archivo3  Tercer soporte opcional
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function processSoportes(int $estudioId, ?UploadedFile $archivo1 = null, ?UploadedFile $archivo2 = null, ?UploadedFile $archivo3 = null): void
    {
        foreach ([$archivo1, $archivo2, $archivo3] as $archivo) {
            if (! $archivo) {
                continue;
            }

            $nombreEncrip = Str::random(50).'.'.$archivo->extension();
            Storage::putFileAs('soportes_hc', $archivo, $nombreEncrip);

            EstudioSoportesHC::create([
                'estudio_id' => $estudioId,
                'archivo_original' => $archivo->getClientOriginalName(),
                'archivo_encrip' => $nombreEncrip,
            ]);
        }
    }

    /**
     * Auto-reconcilia órdenes de Modality Worklist marcándolas como COMPLETADO al asignar el estudio.
     *
     * Flujo del proceso:
     * 1. Extrae el Accession Number o Study Instance UID del registro.
     * 2. Localiza órdenes de worklist en estado PENDIENTE o ADQUIRIDO coincidentes.
     * 3. Actualiza el estado a COMPLETADO y asocia el ID del estudio generado.
     *
     * @param  Estudio  $estudio  Modelo del estudio persistido
     * @param  array  $registro  Datos enviados desde el formulario de asignación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    private function autoReconciliarWorklistOrden(Estudio $estudio, array $registro): void
    {
        $accessionNo = trim($registro['accession_no'] ?? '');
        $studyIuid = trim($registro['study_iuid'] ?? '');

        if (empty($accessionNo) && empty($studyIuid)) {
            return;
        }

        try {
            WorklistOrden::query()
                ->where('sede_id', $estudio->sede_id)
                ->whereIn('estado', ['PENDIENTE', 'ADQUIRIDO'])
                ->where(function ($q) use ($accessionNo, $studyIuid) {
                    if (! empty($accessionNo)) {
                        $q->where('accession_no', $accessionNo);
                    }
                    if (! empty($studyIuid)) {
                        $q->orWhere('study_iuid', $studyIuid);
                    }
                })
                ->update([
                    'estado' => 'COMPLETADO',
                    'estudio_id' => $estudio->id,
                    'study_pk' => ! empty($registro['study_pk']) ? (int) $registro['study_pk'] : null,
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            Log::warning('No fue posible auto-reconciliar WorklistOrden al crear estudio: '.$e->getMessage(), [
                'estudio_id' => $estudio->id,
                'accession_no' => $accessionNo,
            ]);
        }
    }
}
