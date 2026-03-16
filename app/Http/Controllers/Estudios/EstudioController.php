<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Interface\SAHIController;
use App\Http\Controllers\Interface\SAHITranscibirController;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioSoportesHC;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EstudioController extends Controller
{
    protected $nomArchivoReporte;

    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Lista todos los estudios.
     *
     * @return \Illuminate\Http\JsonResponse Lista de estudios.
     */
    public function index()
    {
        $estudios = Estudio::all();

        return response()->json($estudios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    /**
     * Almacena un nuevo estudio, incluyendo paciente, productos, diagnósticos y soportes.
     *
     * Flujo del proceso:
     * 1. Valida los datos de entrada (médico, prioridad, paciente, productos).
     * 2. Crea o actualiza el Paciente.
     * 3. Si la sede usa SAHI, consulta EPS, Contrato y Plan.
     * 4. Crea el registro del Estudio.
     * 5. Asocia los productos (EstudioProducto), validando existencia y requerimiento de lectura.
     * 6. Asocia los diagnósticos (EstudioDiagnostico).
     * 7. Procesa y guarda los archivos de soporte (HC) si existen.
     * 8. Retorna confirmación.
     *
     * @param  Request  $request  Datos del estudio y archivos.
     * @return \Illuminate\Http\JsonResponse Confirmación de creación o errores.
     */
    public function store(Request $request)
    {
        $registro = json_decode($request->input('registro'), true);

        if ($registro['destino_estudio'] === 'Tele Radiologia') {
            $validator = Validator::make($registro, [
                'prioridad_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
                'num_docu' => 'required',
                'nombres' => 'required',
                'productosEstudio' => 'required|array|min:1',  // Debe ser un array con al menos un elemento
            ], [
                'prioridad_id.required' => 'Te hizo falta la prioridad del estudio.',
                'prioridad_id.not_in' => 'Te hizo falta la prioridad del estudio.',
                'num_docu.required' => 'Te hizo falta el número de documento del paciente.',
                'nombres.required' => 'Te hizo falta los nombres del paciente.',
                'productosEstudio.required' => 'Debe elegir al menos un producto.',
                'productosEstudio.min' => 'Debe elegir al menos un producto.',
            ]);
        } else {
            $validator = Validator::make($registro, [
                'medico_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
                'prioridad_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
                'num_docu' => 'required',
                'nombres' => 'required',
                'productosEstudio' => 'required|array|min:1',  // Debe ser un array con al menos un elemento
            ], [
                'medico_id.required' => 'Te hizo falta el medico.',
                'medico_id.not_in' => 'Te hizo falta el medico.',
                'prioridad_id.required' => 'Te hizo falta la prioridad del estudio.',
                'prioridad_id.not_in' => 'Te hizo falta la prioridad del estudio.',
                'num_docu.required' => 'Te hizo falta el número de documento del paciente.',
                'nombres.required' => 'Te hizo falta los nombres del paciente.',
                'productosEstudio.required' => 'Debe elegir al menos un producto.',
                'productosEstudio.min' => 'Debe elegir al menos un producto.',
            ]);
        }

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Asegurar que venga al menos un producto marcado (checked=true)
        $productosMarcados = array_values(array_filter(
            $registro['productosEstudio'] ?? [],
            fn($p) => isset($p['checked']) && $p['checked'] == true
        ));
        if (count($productosMarcados) === 0) {
            return response()->json(['productosEstudio' => ['Debe seleccionar al menos un producto (marcar el check).']], 400);
        }

        $nombrePaciente = str_replace('^^^', '', $registro['nombres']);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $paciente = Paciente::updateOrCreate(
            ['num_docu' => $registro['num_docu']],
            [
                'num_docu' => $registro['num_docu'],
                'nombres' => $nombrePaciente,
                'direccion' => $registro['direccion'],
                'sexo' => $registro['sexo'],
                'fec_naci' => $registro['fec_naci'],
                // Unificar nombre de campo: el frontend envía `tel` (con fallback a `telefono` por compatibilidad)
                'tel' => $registro['tel'] ?? ($registro['telefono'] ?? null),
                'email' => $registro['email'],
            ]
        );

        /**
         * Si la sede tiene interface relleno la informacion necesaria con los datos de la Eps, Plan y contrato
         */
        $eps = "";
        $contrato = "";
        $plan = "";
        $servicio = "";

        $sede = ConfigSede::findOrfail($registro['sede_id']);
        if ($sede->interface_software === 'SAHI') {

            $buscarDatos = (new SAHIController)->buscarEpsPlanContrato($registro['atencion_id']);

            if (count($buscarDatos) > 0) {
                $eps = $buscarDatos[0]->NomTercero;
                $contrato = $buscarDatos[0]->DesContrato;
                $plan = $buscarDatos[0]->DesPlan;
                $servicio = $buscarDatos[0]->NomUbicacion;
            }
        }

        // return $registro['destino_estudio'];
        /**
         * Inserto el estudio en Lidiag
         */
        return DB::transaction(function () use ($registro, $paciente, $eps, $contrato, $plan, $servicio, $request, $productosMarcados) {
            $estudio = Estudio::updateOrCreate(
                [
                    'sede_id' => $registro['sede_id'],
                    'study_pk' => $registro['study_pk'],
                ],
                [
                    'study_iuid' => $registro['study_iuid'],
                    'study_id' => $registro['study_id'],
                    'fec_estudio' => $registro['study_datetime'],
                    'accession_no' => $registro['accession_no'],
                    'study_desc' => $registro['study_desc'],
                    'mods_in_study' => $registro['mods_in_study'],
                    'email_reportar' => $registro['email_reportar'],
                    'paciente_id' => $paciente->id,
                    'quien_registro_id' => auth()->user()->id,
                    'prioridad_id' => $registro['prioridad_id'],
                    'observaciones' => $registro['observaciones'],
                    'atencion' => $registro['atencion_id'],
                    'servicio' => $servicio,
                    'eps' => $eps,
                    'contrato' => $contrato,
                    'plan' => $plan,
                    'destino_estudio' => $registro['destino_estudio'],
                ]
            );

            if ($registro['destino_estudio'] === 'Salud Tolima') {
                $estudio->medico_id = $registro['medico_id'];
            } else {
                $estudio->destino_estudio_id = $registro['destino_estudio_id'] ?? null;
            }
            $estudio->save();

            // Reemplazar productos
            EstudioProducto::where('estudio_id', $estudio->id)->delete();
            foreach ($productosMarcados as $Producto) {
                $productoDicom = ConfigProducto::where('cod_cups', $Producto['cod_cups'])->first();

                if (! $productoDicom) {
                    return response()->json(['message' => 'No se encontró el producto con código CUPS ' . $Producto['cod_cups'] . ' en la base de datos.'], 400);
                }
                if ((int) $productoDicom->requi_lectura === 0) {
                    return response()->json(['message' => 'El producto ' . $productoDicom->nom_produc . ' no requiere lectura.'], 400);
                }

                EstudioProducto::create([
                    'estudio_id' => $estudio->id,
                    'producto_id' => $productoDicom->id,
                    'cod_cups' => $productoDicom->cod_cups,
                    'nom_produc' => $productoDicom->nom_produc,
                ]);
            }

            // Reemplazar diagnósticos (si llegan)
            EstudioDiagnostico::where('estudio_id', $estudio->id)->delete();
            foreach (($registro['diagnosticosEstudio'] ?? []) as $Diagnostico) {
                if (! isset($Diagnostico['cod_diagnos']) || ! isset($Diagnostico['nom_diagnos'])) {
                    continue;
                }
                EstudioDiagnostico::create([
                    'estudio_id' => $estudio->id,
                    'cod_diagnos' => $Diagnostico['cod_diagnos'],
                    'nom_diagnos' => $Diagnostico['nom_diagnos'],
                ]);
            }

            // Almacenar soportes HC
            if ($request->hasFile('archivo1')) {
                $this->procesarArchivo($request->archivo1, $estudio->id);
            }
            if ($request->hasFile('archivo2')) {
                $this->procesarArchivo($request->archivo2, $estudio->id);
            }
            if ($request->hasFile('archivo3')) {
                $this->procesarArchivo($request->archivo3, $estudio->id);
            }

            return response()->json(['message' => 'La asignación de la lectura se realizó correctamente.']);
        });
    }

    /**
     * Procesa y almacena un archivo de soporte de Historia Clínica.
     *
     * @param  \Illuminate\Http\UploadedFile  $archivo  Archivo a procesar.
     * @param  int  $estudioId  ID del estudio asociado.
     * @return void
     */
    public function procesarArchivo($archivo, $estudioId)
    {
        $nombreEncrip = Str::random(50) . "." . $archivo->extension();
        $nombreOriginal = $archivo->getClientOriginalName();

        $archivoSoporteHC = new EstudioSoportesHC;
        $archivoSoporteHC->estudio_id = $estudioId;
        $archivoSoporteHC->archivo_original = $nombreOriginal;
        $archivoSoporteHC->archivo_encrip = $nombreEncrip;
        $archivoSoporteHC->save();

        Storage::putFileAs('soportes_hc', $archivo, $nombreEncrip);
    }

    /**
     * Lista los estudios pendientes asignados a un médico.
     *
     * Flujo del proceso:
     * 1. Consulta estudios con relaciones (paciente, sede, productos, usuario, prioridad).
     * 2. Filtra por médico, estado activo, sin transcriptor y sin fecha de lectura.
     * 3. Ordena por prioridad (nivel).
     * 4. Retorna la lista de pendientes.
     *
     * @param  Request  $request  Filtros (id médico, sede_id).
     * @param  Estudio  $estudio  Instancia vacía (no usada).
     * @return \Illuminate\Http\JsonResponse Lista de estudios pendientes.
     */
    public function listarPendientesMedico(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:users,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $misPendiente = Estudio::select(
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
            'sesde.tap_oviyam'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sesde', 'estudios.sede_id', '=', 'sesde.id')
            ->join('estudios_productos AS estu_produc', 'estu_produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos AS config_produc', 'estu_produc.producto_id', '=', 'config_produc.id')
            ->where('estudios.medico_id', $request->id)
            ->where('estu_produc.activo', 1)
            ->whereNull('estu_produc.transcriptor_id')
            ->where('config_produc.requi_lectura', "=", 1)
            ->whereNull('estu_produc.fechor_trascrito')
            ->whereNull('estu_produc.fechor_lectura')
            ->where('sesde.id', $request->sede_id)
            ->orderBy('priori.nivel', 'ASC')
            ->get();

        return response()->json($misPendiente, 200);
    }

    /**
     * Guarda la lectura realizada por el médico.
     *
     * @param  Request  $request  Datos de la lectura.
     * @param  EstudioProducto  $estudioProducto  Instancia vacía (no usada).
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function leerEstudio(Request $request, EstudioProducto $estudioProducto)
    {
        $validator = Validator::make($request->all(), [
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'lectura' => 'required|string|min:3',
        ], [
            'lectura.required' => 'Debe ingresar la lectura.',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $productoLectura = EstudioProducto::findOrFail($request->estudio_producto_id);
        $productoLectura->lectura = $request->lectura;
        $productoLectura->fechor_lectura = Carbon::now();
        $productoLectura->save();

        return response()->json(['message' => 'La lectura se realizó correctamente.']);
    }

    /**
     * Marca un estudio como visto.
     *
     * @param  int  $id  ID del producto de estudio.
     * @return void
     */
    public function marcarEstudioVisto($id)
    {
        $estudioProducto = EstudioProducto::FindOrFail($id);
        $estudioProducto->visto = 1;
        $estudioProducto->save();
    }

    /**
     * Lista los diagnósticos asociados a un estudio.
     *
     * @param  Request  $request  ID del estudio.
     * @return \Illuminate\Http\JsonResponse Lista de diagnósticos.
     */
    public function listarDiagnosticoPorEstudio(Request $request)
    {
        $diagnosticos = EstudioDiagnostico::where('estudio_id', '=', $request->id)->get();

        return response()->json($diagnosticos);
    }

    /**
     * Lista los estudios pendientes de transcribir.
     *
     * Flujo del proceso:
     * 1. Consulta estudios con información detallada.
     * 2. Filtra por estado activo, sin transcriptor.
     * 3. Aplica filtros opcionales:
     *    - `estudioParaTranscibir`: Solo estudios ya leídos.
     *    - Fechas, nombre/documento, usuario asignador, médico.
     * 4. Ordena por prioridad.
     *
     * @param  Request  $request  Filtros de búsqueda.
     * @return \Illuminate\Http\JsonResponse Lista de estudios pendientes.
     */
    public function listarPendientesTrascribir(Request $request)
    {
        // Validación mínima solicitada: solo sede_id
        $validator = Validator::make($request->all(), [
            'sede_id' => 'required|integer|exists:config_sedes,id',
        ], [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.integer' => 'La sede debe ser un número entero.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        /**
         * @estudioParaTranscibir para saber si se van a listar los estudio que ya estan leidos o no,
         * para transcribir los estudios que se envian a trys o a teleradiologia
         */
        $misPendiente = Estudio::select(
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
            'sede.tap_oviyam'
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sede', 'estudios.sede_id', '=', 'sede.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->leftJoin('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos', 'estudios_productos.producto_id', '=', 'config_productos.id')
            ->when($request->estudioParaTranscibir, function ($query) {
                /**
                 * Si el destino es = Salud Tolima se debe filtrar por los estudios que ya tienen lectura porque para transcribir los medicos ya realizaron la lectura
                 * Si el destino es diferente a Salud Tolima se deben filtrar los estudios que no tienen lectura porque son los estudios que se van a leer porque vienen de Teleradiologia
                 */
                $query->whereNotNull('estudios_productos.fechor_lectura');
                /* $query->where(function ($q) use ($request) {
                    if ($request->destino_estudio === 'Salud Tolima') {
                        $q->whereNotNull('estudios_productos.fechor_lectura');
                    } else {
                        $q->whereNotNull('estudios_productos.fechor_lectura');
                    }
                }); */
            })
            ->when($request->bus_fehc_ini && $request->bus_fecha_fin, function ($query) use ($request) {
                // Si vienen solo fechas (YYYY-MM-DD), incluir el día completo para no excluir por hora
                $inicio = Carbon::parse($request->bus_fehc_ini)->startOfDay()->format('Y-m-d H:i:s');
                $fin = Carbon::parse($request->bus_fecha_fin)->endOfDay()->format('Y-m-d H:i:s');

                return $query->whereBetween('estudios.fec_estudio', [$inicio, $fin]);
            })
            ->where('estudios_productos.activo', '=', 1)
            ->whereNull('estudios_productos.transcriptor_id')
            ->where('estudios.sede_id', $request->sede_id)
            ->where(function ($q) use ($request) {
                $q->where('estudios.destino_estudio', $request->destino_estudio)
                    ->orWhereNull('estudios.destino_estudio');
            })
            ->when($request->bus_nom_num_docu, function ($query, $bus_nom_num_docu) {
                return $query->where(function ($q) use ($bus_nom_num_docu) {
                    $q->where('pacien.num_docu', $bus_nom_num_docu)
                        ->orWhere('pacien.nombres', 'LIKE', '%' . $bus_nom_num_docu . '%');
                });
            })
            ->when($request->quien_asigno_id, function ($query, $quien_asigno_id) {
                return $query->where('estudios.quien_registro_id', $quien_asigno_id);
            })
            ->when($request->medico_id, function ($query, $medico_id) {
                return $query->where('estudios.medico_id', $medico_id);
            })
            ->orderBy('priori.nivel', 'ASC')
            ->get();

        return response()->json($misPendiente);
    }

    /**
     * Lista todos los estudios asignados con filtros avanzados.
     *
     * Flujo del proceso:
     * 1. Consulta estudios con todas las relaciones necesarias.
     * 2. Aplica filtros por fechas, sede, paciente, usuario asignador y médico.
     * 3. Ordena por fecha de creación.
     *
     * @param  Request  $request  Filtros de búsqueda.
     * @return \Illuminate\Http\JsonResponse Lista de estudios asignados.
     */
    public function listarEstudiosAsignados(Request $request)
    {
        // Validación mínima solicitada: solo sede_id
        $validator = Validator::make($request->all(), [
            'sede_id' => 'required|integer|exists:config_sedes,id',
        ], [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.integer' => 'La sede debe ser un número entero.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $misPendiente = Estudio::select(
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
            'sede.tap_oviyam'
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sede', 'estudios.sede_id', '=', 'sede.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('users as medi', 'estudios.medico_id', '=', 'medi.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos', 'estudios_productos.producto_id', '=', 'config_productos.id')
            // ->where('estudios_productos.activo', '=', 1)
            ->when($request->bus_fehc_ini && $request->bus_fecha_fin, function ($query) use ($request) {
                return $query->whereBetween(
                    DB::raw('DATE(estudios.fec_estudio)'),
                    [$request->bus_fehc_ini, $request->bus_fecha_fin]
                );
            })
            ->where('estudios.sede_id', $request->sede_id)
            ->when($request->bus_nom_num_docu, function ($query, $bus_nom_num_docu) {
                return $query->where(function ($q) use ($bus_nom_num_docu) {
                    $q->where('pacien.num_docu', $bus_nom_num_docu)
                        ->orWhere('pacien.nombres', 'LIKE', '%' . $bus_nom_num_docu . '%');
                });
            })
            ->when($request->quien_asigno_id, function ($query, $quien_asigno_id) {
                return $query->where('estudios.quien_registro_id', $quien_asigno_id);
            })
            ->when($request->medico_id, function ($query, $medico_id) {
                return $query->where('estudios.medico_id', $medico_id);
            })
            ->orderByDesc('estudios.fec_estudio')
            ->orderByDesc('estudios.created_at')
            ->limit(1000)
            ->get();

        return response()->json($misPendiente);
    }

    /**
     * Guarda la transcripción de un estudio, genera el reporte y notifica.
     *
     * Flujo del proceso:
     * 1. Actualiza datos del paciente.
     * 2. Actualiza la lectura del estudio (transcriptor, fecha, estado visto).
     * 3. Si la sede usa SAHI, envía el estudio a la interface.
     * 4. Genera el reporte:
     *    - Crea plantilla .docx con `PlantillaEstudioController`.
     *    - Convierte a PDF.
     *    - Notifica por email con `EstudioNotificacionController`.
     *
     * @param  Request  $request  Datos de transcripción y paciente.
     * @return \Illuminate\Http\JsonResponse Confirmación o error.
     */
    public function guardarTranscripcion(Request $request)
    {
        /**
         * Si la sede donde se realizó el estudio tiene interface hago el envio del reporte a la HC del hospital
         */
        $sede = ConfigSede::findOrfail($request->sede_id);
        if ($sede->interface_software === 'SAHI') {

            $medico = User::find($request->medico_id);
            if (! $medico->interface_id) {
                return response()->json(['message' => 'El medico ' . $medico->name . ' no está registrado en SAHI'], 400);
            }
        }

        $validator = Validator::make($request->all(), [
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'id_estudio' => 'nullable|integer|exists:estudios,id',
            'lectura' => 'required|string|min:3',
            'num_docu' => 'required|string|max:50',
            'nom_pacien' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'tel' => 'nullable|string|max:50',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'cod_cups' => 'required|string|max:50',
            'nom_produc' => 'required|string|max:255',
            'medico_id' => 'nullable|integer',
        ], [
            'lectura.required' => 'Debe ingresar la lectura.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Completar campos esperados por plantilla/notificación
        $request->merge([
            'fechor_lectura' => $request->input('fechor_lectura', $request->input('fec_lectura')),
            'fechor_trascrito' => $request->input('fechor_trascrito', Carbon::now()->format('Y-m-d H:i:s')),
        ]);

        // return $request;
        /**
         * Actualizo los datos del paciente
         */
        $paciente = Paciente::updateOrCreate(
            ['num_docu' => $request->num_docu],
            [
                'num_docu' => $request->num_docu,
                'nombres' => $request->nom_pacien,
                'direccion' => $request->direccion,
                'sexo' => $request->sexo,
                'fec_naci' => $request->fec_naci,
                'tel' => $request->tel,
                'email' => $request->email,
            ]
        );

        /**
         * Actualizo la lectura del estudio
         */
        $transcribirEstudios = EstudioProducto::findOrFail($request->estudio_producto_id);
        $transcribirEstudios->lectura = $request->lectura;
        $transcribirEstudios->transcriptor_id = auth()->user()->id;
        $transcribirEstudios->fechor_trascrito = Carbon::now()->format('Y-m-d H:i:s');
        $transcribirEstudios->visto = 1;
        $transcribirEstudios->save();

        /**
         * Si el estudio se va a enviar a Teleradiologia o Trys no se asigna medico porque se asigna en la plataforma de ellos, pero si el estudio se va a enviar a Salud Tolima se asigna el medico que realizo la lectura porque es para que el medico de Salud Tolima pueda identificar quien realizo la lectura del estudio
         */
        if ($request->destino_estudio === 'Tele Radiologia') {
            $estudio = Estudio::findOrFail($request->id_estudio);
            $estudio->medico_id = $request['medico_id'];
            $estudio->save();
        }

        /**
         * Si la sede donde se realizó el estudio tiene interface hago el envio del reporte a la HC del hospital
         */
        if ($sede->interface_software === 'SAHI') {
            $transcribir = (new SAHITranscibirController)->enviarEstudio($request->atencion, $request->medico_id, $request->lectura, $request->cod_cups, $request->fec_lectura);

            if ($transcribir->status() != 200) {
                return response()->json(['message' => 'Error al enviar el reporte a la interface SAHI ' . $transcribir->message()], 400);
            }
        }

        /**
         * Genero el reporte de la lectura del estudio
         * 1. Genero laplantilla .docx con la informacion de la lectura por prte del medico
         * 2. Convierto la plantilla .docx a .pdf
         * 3. Notificacion por correo electronico
         */
        $genearPlantilla = (new PlantillaEstudioController)->editWord($request);
        if ($genearPlantilla->status() === 402) {
            return response()->json(['message' => 'La plantilla para la sede ' . $sede->nom_sede . ', no está  configurada, por favor consulte con el administrador del sistema'], 402);
        } elseif ($genearPlantilla->status() == 200) {

            $notificar = (new EstudioNotificacionController)->enviarPortalPacientes($request);

            if ($notificar->status() == 200) {
                return response()->json(['message' => 'Lectura transferida correctamente'], 200);
            } else {
                return response()->json(['message' => 'Error en la trnaferencia de la lectura'], 400);
            }
        } else {
            return response()->json(['message' => 'Error en la trnaferencia de la lectura'], 400);
        }

        return response()->json(['message' => 'La transcripción se guardo correctamente.']);
    }

    /**
     * Lista estudios pendientes por leer o transcribir.
     *
     * @param  Request  $request  Filtros (no usados explícitamente en la consulta actual).
     * @return \Illuminate\Http\JsonResponse Lista de pendientes.
     */
    public function penditesPorLeerTranscribir(Request $request)
    {
        $pendiente = Estudio::select(
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
            'medico.name as medico_name'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id', '=', 'pacien.id')
            ->join('config_sedes AS sesde', 'estudios.sede_id', '=', 'sesde.id')
            ->join('estudios_productos AS produc', 'produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('users as medico', 'estudios.medico_id', '=', 'medico.id')
            ->whereNull('produc.fechor_lectura')
            ->where('medico.permitir_transcribir', '=', 1)
            ->orderBy('priori.nom_priori')->get();

        return response()->json($pendiente, 200);
    }

    /**
     * Busca si un paciente tiene estudios por su número de documento.
     *
     * @param  Request  $request  Número de documento.
     * @return \Illuminate\Http\JsonResponse 'Si' o 'No'.
     */
    public function buscarEstudioPorNumDocu(Request $request)
    {
        $estudios = Paciente::where('num_docu', '=', $request->numDocu)->first()->lecturas;

        $estudios = Estudio::select('pacientes.num_docu')
            ->join('pacientes', 'estudios.paciente_id', 'pacientes.id')
            ->where('pacientes.num_docu', '=', $request->numDocu)
            ->count();

        if ($estudios > 0) {
            return response()->json(['message' => 'Si', 200]);
        } else {
            return response()->json(['message' => 'No', 200]);
        }
    }

    /**
     * Anula la asignación de un producto de estudio (lo desactiva).
     *
     * @param  Request  $request  ID del producto.
     * @param  EstudioProducto  $estudioProducto  Instancia vacía (no usada).
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function anularAsignacion(Request $request, EstudioProducto $estudioProducto)
    {
        $productoEstudio = EstudioProducto::findOrFail($request->id);
        $productoEstudio->activo = 0;
        $productoEstudio->save();

        return response()->json(['message' => 'El producto se anulo correctament'], 200);
    }

    /**
     * Descarga el archivo PDF de una lectura.
     *
     * @param  string  $nomArchivo  Nombre del archivo.
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse Archivo PDF.
     */
    public function descargarPdfLectura($nomArchivo)
    {
        $rutaArchivo = storage_path('app/reporte_lecturas/' . $nomArchivo);

        return response()->file($rutaArchivo);
    }

    /**
     * Reenvía la notificación por email de un estudio.
     *
     * @param  Request  $request  Datos para generar reporte y notificar.
     * @return mixed Respuesta del notificador.
     */
    public function notificarEmail(Request $request)
    {

        $genearPlantilla = (new PlantillaEstudioController)->editWord($request);
        if ($genearPlantilla->status() == 200) {
            $enviarPortaPaciente = (new EstudioNotificacionController)->enviarPortalPacientes($request);

            if ($enviarPortaPaciente->status() == 200) {
                $respon = json_decode($enviarPortaPaciente->getContent(), true);
                $enviarEmail = (new EstudioNotificacionController)->notificarEmail($request, $respon['reportePath']);

                return response()->json(['message' => 'Notificación reenviada correctamente'], 200);
            } else {
                return response()->json(['message' => 'Error al reenviar la notificación'], 400);
            }

            $eliminarReporte = (new PlantillaEstudioController)->eliminarReporte($respon['reportePath']);

            return $notificar;
        } else {
            return $genearPlantilla;
        }
    }

    public function enviarPortalPacientes(Request $request)
    {
        $genearPlantilla = (new PlantillaEstudioController)->editWord($request);
        if ($genearPlantilla->status() == 200) {
            $enviarPortaPaciente = (new EstudioNotificacionController)->enviarPortalPacientes($request);
            $respon = json_decode($enviarPortaPaciente->getContent(), true);
            $eliminarReporte = (new PlantillaEstudioController)->eliminarReporte($respon['reportePath']);

            return $enviarPortaPaciente;
        } else {
            return $genearPlantilla;
        }
    }
}
