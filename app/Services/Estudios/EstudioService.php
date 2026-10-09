<?php

namespace App\Services\Estudios;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioSoportesHC;
use App\Models\General\Paciente;
use App\Models\Liquidacion\LiquidacionLinea;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use App\Services\Liquidacion\LiquidacionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstudioService extends BaseService
{
    /**
     * Constructor del servicio principal de estudios
     *
     * @param  FileServiceInterface  $fileService  Servicio de gestión de archivos
     * @param  PlantillaEstudioService  $plantillaEstudioService  Servicio de plantillas Word/PDF
     * @param  EstudioNotificacionService  $estudioNotificacionService  Servicio de notificaciones
     * @param  EstudioLockService  $lockService  Servicio de reservas de lectura/asignación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly PlantillaEstudioService $plantillaEstudioService,
        private readonly EstudioNotificacionService $estudioNotificacionService,
        private readonly EstudioLockService $lockService,
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
        return $this->success(Estudio::all());
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
        return $this->success(
            EstudioDiagnostico::where('estudio_id', $estudioId)->get()
        );
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
     * } $data
     */
    public function guardarTranscripcion(array $data): array
    {
        try {
            $result = DB::transaction(function () use ($data) {
                $sede = ConfigSede::findOrFail($data['sede_id']);

                if ($sede->interface_software === 'SAHI') {
                    $medico = User::find($data['medico_id']);
                    if (! $medico || ! $medico->interface_id) {
                        return $this->error('El médico seleccionado no está registrado en la interfaz.', 400);
                    }
                }

                $paciente = Paciente::updateOrCreate(
                    ['num_docu' => $data['num_docu']],
                    [
                        'num_docu' => $data['num_docu'],
                        'nombres' => $data['nom_pacien'],
                        'direccion' => $data['direccion'] ?? null,
                        'sexo' => $data['sexo'] ?? null,
                        'fec_naci' => $data['fec_naci'] ?? null,
                        'tel' => $data['tel'] ?? null,
                        'email' => $data['email'] ?? null,
                    ]
                );

                $producto = EstudioProducto::lockForUpdate()->findOrFail($data['estudio_producto_id']);

                if ($producto->fechor_trascrito !== null) {
                    return $this->error('Esta transcripción ya fue procesada por otro usuario. Actualice la página.', 409);
                }

                $producto->lectura = $data['lectura'];
                $producto->transcriptor_id = Auth::id();
                $producto->fechor_trascrito = Carbon::now()->format('Y-m-d H:i:s');
                $producto->visto = 1;
                $producto->save();

                if (! empty($data['destino_estudio']) && $data['destino_estudio'] === DESTINO_TELE_RADIOLOGIA) {
                    $estudio = Estudio::findOrFail($data['id_estudio']);
                    $estudio->medico_id = $data['medico_id'];
                    $estudio->save();
                }

                $plantillaResult = $this->plantillaEstudioService->editWord($data);
                if (! $plantillaResult['success']) {
                    return $this->error(
                        $plantillaResult['message'] ?? 'La plantilla no está configurada para esta sede.',
                        $plantillaResult['status'] ?? 402
                    );
                }

                return $this->success(null, 'La transcripción se guardó correctamente.');
            });

            try {
                $this->estudioNotificacionService->enviarPortalPacientes((object) $data);
            } catch (\Throwable $e) {
                Log::warning('Error al enviar al portal de pacientes: '.$e->getMessage());
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('Error al guardar transcripción: '.$e->getMessage());

            return $this->error('No fue posible guardar la transcripción.', 500);
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
        $count = Estudio::select('pacientes.num_docu')
            ->join('pacientes', 'estudios.paciente_id', 'pacientes.id')
            ->where('pacientes.num_docu', $numDocu)
            ->count();

        return $this->success(['tiene_estudios' => $count > 0]);
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
     * @param  array  $registro  Datos del formulario de asignación
     * @param  string  $nombrePaciente  Nombre ya saneado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function createOrUpdatePatient(array $registro, string $nombrePaciente): Paciente
    {
        $existing = Paciente::where('num_docu', $registro['num_docu'])->first();

        return Paciente::updateOrCreate(
            ['num_docu' => $registro['num_docu']],
            [
                'num_docu' => $registro['num_docu'],
                'nombres' => $nombrePaciente,
                'direccion' => ! empty($registro['direccion']) ? $registro['direccion'] : ($existing->direccion ?? ''),
                'sexo' => ! empty($registro['sexo']) ? $registro['sexo'] : ($existing->sexo ?? ''),
                'fec_naci' => ! empty($registro['fec_naci']) ? $registro['fec_naci'] : ($existing->fec_naci ?? null),
                'tel' => ! empty($registro['tel']) ? $registro['tel'] : ($registro['telefono'] ?? ($existing->tel ?? '')),
                'email' => ! empty($registro['email']) ? $registro['email'] : ($existing->email ?? ''),
            ]
        );
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
     * Reemplaza los productos del estudio por los marcados que requieren lectura (requi_lectura = 1).
     *
     * @param  int  $estudioId  Identificador del estudio
     * @param  array  $productosMarcados  Productos marcados en el formulario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function syncStudyProducts(int $estudioId, array $productosMarcados): void
    {
        EstudioProducto::where('estudio_id', $estudioId)->delete();

        foreach ($productosMarcados as $producto) {
            $productoDicom = ConfigProducto::where('cod_cups', $producto['cod_cups'])->first();

            if (! $productoDicom || (int) $productoDicom->requi_lectura === 0) {
                continue;
            }

            EstudioProducto::create([
                'estudio_id' => $estudioId,
                'producto_id' => $productoDicom->id,
                'cod_cups' => $productoDicom->cod_cups,
                'nom_produc' => $productoDicom->nom_produc,
            ]);
        }
    }

    /**
     * Reemplaza los diagnósticos del estudio por los válidos (con código y nombre).
     *
     * @param  int  $estudioId  Identificador del estudio
     * @param  array  $diagnosticos  Diagnósticos enviados desde el formulario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function syncStudyDiagnostics(int $estudioId, array $diagnosticos): void
    {
        EstudioDiagnostico::where('estudio_id', $estudioId)->delete();

        foreach ($diagnosticos as $diagnostico) {
            if (empty($diagnostico['cod_diagnos']) || empty($diagnostico['nom_diagnos'])) {
                continue;
            }

            EstudioDiagnostico::create([
                'estudio_id' => $estudioId,
                'cod_diagnos' => $diagnostico['cod_diagnos'],
                'nom_diagnos' => $diagnostico['nom_diagnos'],
            ]);
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
     * Aplica los filtros del listado de pendientes por transcribir.
     *
     * @param  Builder  $query  Consulta base con los joins ya aplicados
     * @param  array  $filters  Filtros: sede_id, destino, fechas, nombre/doc, quién asignó, médico
     *
     * @returns \Illuminate\Database\Eloquent\Builder Consulta con los filtros aplicados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function applyTranscripcionFilters($query, array $filters)
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
     * @returns \Illuminate\Database\Eloquent\Builder Consulta con los filtros aplicados
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    private function applyAsignadosFilters($query, array $filters)
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
