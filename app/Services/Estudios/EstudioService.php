<?php

namespace App\Services\Estudios;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioSoportesHC;
use App\Models\General\Paciente;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EstudioService extends BaseService
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly PlantillaEstudioService $plantillaEstudioService,
        private readonly EstudioNotificacionService $estudioNotificacionService,
    ) {}

    public function all(): array
    {
        return $this->success(Estudio::all());
    }

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

        try {
            return DB::transaction(function () use ($registro, $nombrePaciente, $productosMarcados, $archivo1, $archivo2, $archivo3) {
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

                return $this->created($estudio, 'La asignación de la lectura se realizó correctamente.');
            });
        } catch (\Exception $e) {
            return $this->error('Error al crear el estudio: '.$e->getMessage(), 500);
        }
    }

    public function listarPendientesMedico(int $medicoId, int $sedeId): array
    {
        $results = Estudio::select(
            'estudios.id', 'estudios.study_pk', 'estudios.study_iuid', 'estudios.study_id',
            'estudios.fec_estudio', 'estudios.accession_no', 'estudios.study_desc',
            'estudios.mods_in_study', 'estudios.email_reportar', 'estudios.paciente_id',
            'estudios.medico_id', 'estudios.quien_registro_id', 'estudios.sede_id',
            'estudios.prioridad_id', 'estudios.observaciones', 'estudios.motivo_examen',
            'estudios.atencion', 'estudios.servicio', 'estudios.eps', 'estudios.contrato',
            'estudios.plan', 'estudios.estado',
            DB::raw("DATE_FORMAT(estudios.created_at, '%Y-%m-%d %H:%i:%s') AS created_at"),
            'pacien.num_docu', 'pacien.nombres as nom_pacien', 'pacien.fec_naci', 'pacien.sexo',
            'estu_produc.id as estudio_producto_id', 'estu_produc.cod_cups', 'estu_produc.nom_produc',
            'estu_produc.fechor_lectura', 'estu_produc.visto',
            'users.name AS quien_registro',
            'priori.id as priori_id', 'priori.nom_priori', 'priori.nivel AS priori_nivel', 'priori.tiempo AS priori_tiempo',
            'sesde.nom_sede as nom_sede', 'sesde.url_oviyam', 'sesde.tap_oviyam',
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

    public function leerEstudio(int $estudioProductoId, string $lectura): array
    {
        $item = EstudioProducto::findOrFail($estudioProductoId);
        $item->lectura = $lectura;
        $item->fechor_lectura = Carbon::now();
        $item->save();

        return $this->updated(null, 'La lectura se realizó correctamente.');
    }

    public function marcarVisto(int $id): array
    {
        $item = EstudioProducto::findOrFail($id);
        $item->visto = 1;
        $item->save();

        return $this->updated(null, 'El estudio se marcó como visto correctamente.');
    }

    public function listarDiagnosticosPorEstudio(int $estudioId): array
    {
        return $this->success(
            EstudioDiagnostico::where('estudio_id', $estudioId)->get()
        );
    }

    public function listarPendientesTranscribir(array $filters): array
    {
        $query = Estudio::select(
            'estudios.id', 'estudios.study_pk', 'estudios.study_iuid', 'estudios.study_id',
            'estudios.fec_estudio', 'estudios.accession_no', 'estudios.study_desc',
            'estudios.mods_in_study', 'estudios.email_reportar', 'estudios.paciente_id',
            'estudios.medico_id', 'estudios.quien_registro_id', 'estudios.sede_id',
            'estudios.prioridad_id', 'estudios.observaciones', 'estudios.atencion',
            'estudios.servicio', 'estudios.eps', 'estudios.contrato', 'estudios.plan', 'estudios.estado',
            DB::raw("DATE_FORMAT(estudios.created_at, '%Y-%m-%d %H:%i:%s') AS created_at"),
            'pacien.num_docu', 'pacien.nombres as nom_pacien', 'pacien.fec_naci',
            'pacien.direccion', 'pacien.email', 'pacien.tel',
            'estudios_productos.id as estudio_producto_id', 'estudios_productos.cod_cups',
            'estudios_productos.nom_produc', 'estudios_productos.fechor_lectura', 'estudios_productos.lectura',
            'users.name AS quien_registro',
            'priori.id as priori_id', 'priori.nom_priori', 'priori.nivel AS priori_nivel', 'priori.tiempo AS priori_tiempo',
            'medi.name as nombres_medico', 'medi.firma as firma_medico', 'medi.reg_med as reg_medico',
            'medi.num_docu AS num_docu_medico',
            'sede.nom_sede', 'sede.url_oviyam', 'sede.tap_oviyam',
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

    public function listarEstudiosAsignados(array $filters): array
    {
        $query = Estudio::select(
            'estudios.*',
            'pacien.num_docu', 'pacien.nombres as nom_pacien', 'pacien.fec_naci',
            'pacien.sexo', 'pacien.direccion', 'pacien.email', 'pacien.tel',
            'estudios_productos.id as estudio_producto_id', 'estudios_productos.cod_cups',
            'estudios_productos.nom_produc', 'estudios_productos.fechor_lectura',
            'estudios_productos.lectura', 'estudios_productos.transcriptor_id', 'estudios_productos.activo',
            'users.name AS quien_registro',
            'priori.id as priori_id', 'priori.nom_priori', 'priori.nivel AS priori_nivel', 'priori.tiempo AS priori_tiempo',
            'medi.name as medico', 'medi.firma', 'medi.reg_med', 'medi.num_docu AS num_docu_medi',
            'sede.nom_sede', 'sede.url_oviyam', 'sede.tap_oviyam',
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
            ->limit(1000)
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
            return DB::transaction(function () use ($data) {
                $sede = ConfigSede::findOrFail($data['sede_id']);

                if ($sede->interface_software === 'SAHI') {
                    $medico = User::find($data['medico_id']);
                    if (! $medico || ! $medico->interface_id) {
                        $name = $medico?->name ?? 'N/A';

                        return $this->error("El médico {$name} no está registrado en SAHI", 400);
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

                $producto = EstudioProducto::findOrFail($data['estudio_producto_id']);
                $producto->lectura = $data['lectura'];
                $producto->transcriptor_id = Auth::id();
                $producto->fechor_trascrito = Carbon::now()->format('Y-m-d H:i:s');
                $producto->visto = 1;
                $producto->save();

                if (! empty($data['destino_estudio']) && $data['destino_estudio'] === 'Tele Radiologia') {
                    $estudio = Estudio::findOrFail($data['id_estudio']);
                    $estudio->medico_id = $data['medico_id'];
                    $estudio->save();
                }

                $plantillaResult = $this->plantillaEstudioService->editWord($data);
                if (! $plantillaResult['success']) {
                    return $this->error(
                        "La plantilla para la sede {$sede->nom_sede} no está configurada.",
                        402
                    );
                }

                $this->estudioNotificacionService->enviarPortalPacientes((object) $data);

                return $this->success(null, 'La transcripción se guardó correctamente.');
            });
        } catch (\Exception $e) {
            return $this->error('Error al guardar transcripción: '.$e->getMessage(), 500);
        }
    }

    public function pendientesPorLeerTranscribir(): array
    {
        $results = Estudio::select(
            'estudios.*',
            'pacien.num_docu', 'pacien.nombres as nom_pacien', 'pacien.fec_naci', 'pacien.sexo',
            'produc.id as estudio_producto_id', 'produc.cod_cups', 'produc.nom_produc',
            'produc.fechor_lectura', 'users.name AS quien_registro',
            'priori.id as priori_id', 'priori.nom_priori', 'priori.nivel AS priori_nivel', 'priori.tiempo AS priori_tiempo',
            'sesde.nom_sede as nom_sede', 'sesde.url_oviyam', 'sesde.tap_oviyam',
            'medico.id as medico_id', 'medico.name as medico_name',
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

    public function buscarPorNumDocu(string $numDocu): array
    {
        $count = Estudio::select('pacientes.num_docu')
            ->join('pacientes', 'estudios.paciente_id', 'pacientes.id')
            ->where('pacientes.num_docu', $numDocu)
            ->count();

        return $this->success(['tiene_estudios' => $count > 0]);
    }

    public function anularAsignacion(int $id): array
    {
        $producto = EstudioProducto::findOrFail($id);
        $producto->activo = 0;
        $producto->save();

        return $this->updated(null, 'El producto se anuló correctamente.');
    }

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

        if ($destino !== 'Tele Radiologia') {
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

    private function getCheckedProducts(array $productos): array
    {
        return array_values(array_filter($productos, fn ($p) => isset($p['checked']) && $p['checked'] == true));
    }

    private function sanitizePatientName(string $name): string
    {
        $name = str_replace('^^^', '', $name);
        $name = str_replace(' ^', '', $name);

        return str_replace('^', ' ', $name);
    }

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

    private function resolveInterfaceData(ConfigSede $sede, array $registro): array
    {
        $default = ['eps' => '', 'contrato' => '', 'plan' => '', 'servicio' => ''];

        if ($sede->interface_software !== 'SAHI') {
            return $default;
        }

        return $default;
    }

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

        if (! empty($filters['bus_fehc_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $inicio = Carbon::parse($filters['bus_fehc_ini'])->startOfDay();
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

    private function applyAsignadosFilters($query, array $filters)
    {
        $sedeId = $filters['sede_id'] ?? null;

        $query->where('estudios_productos.activo', 1);

        if ($sedeId) {
            $query->where('estudios.sede_id', $sedeId);
        }

        if (! empty($filters['bus_fehc_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $query->whereBetween('estudios.fec_estudio', [
                $filters['bus_fehc_ini'].' 00:00:00',
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
