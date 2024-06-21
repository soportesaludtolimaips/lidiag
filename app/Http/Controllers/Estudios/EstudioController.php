<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Interface\SAHIController;
use App\Http\Controllers\Interface\SAHITranscibirController;
use App\Http\Requests\Estudio\estudioLeerRequest;
use App\Mail\NotificacionAsignacionDeLectura;
use App\Models\General\Paciente;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioDiagnostico;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\Request;
use Mail;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Reportes\ResporteLecturaController;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EstudioSoportesHC;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Str;

use Illuminate\Support\Facades\Validator;

class EstudioController extends Controller
{

    protected $nomArchivoReporte;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $estudios = Estudio::all();
        return response()->json($estudios);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validar el campo 'registro' de la solicitud
        $validatedData = $request->validate([
            'registro' => 'required|json',
        ], [
            'registro.required' => 'El campo "registro" es obligatorio.',
            'registro.json' => 'El campo "registro" debe contener un JSON válido.',
        ]);

        // Decodificar el JSON de 'registro' a un array asociativo
        $registro = json_decode($validatedData['registro'], true);

        // Verifica si la decodificación fue exitosa
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'error' => 'El contenido de "registro" no es un JSON válido.',
            ], 400);
        }

        // Imprime el contenido de $registro para verificar los datos
        // Puedes usar Log::debug() en lugar de dd() en un entorno de producción
        // dd($registro);

        // Validar los campos dentro de 'registro'
        // Validar los campos dentro de 'registro'
        $validator = Validator::make($registro, [
            'medico_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
            'prioridad_id' => 'required|integer|not_in:0',  // Validación para que no sea 0
            'num_docu' => 'required',
            'nombres' => 'required',
            'productosEstudio' => 'required|array|min:1',  // Debe ser un array con al menos un elemento
            'diagnosticosEstudio' => 'required|array|min:1',  // Debe ser un array con al menos un elemento
        ], [
            'medico_id.required' => 'Te hizo falta el medico.',
            'medico_id.not_in' => 'Te hizo falta el medico.',
            'prioridad_id.required' => 'Te hizo falta la prioridad del estudio.',
            'prioridad_id.not_in' => 'Te hizo falta la prioridad del estudio.',
            'num_docu.required' => 'Te hizo falta el número de documento del paciente.',
            'nombres.required' => 'Te hizo falta los nombres del paciente.',
            'productosEstudio.required' => 'Debe elegir al menos un producto.',
            'productosEstudio.min' => 'Debe elegir al menos un producto.',
            'diagnosticosEstudio.required' => 'Debe elegir al menos un diagnostico.',
            'diagnosticosEstudio.min' => 'Debe elegir al menos un diagnostico.',
        ]);

        // Si la validación falla, devuelve los errores
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        // Aquí puedes continuar con el procesamiento de los datos validados
        // Por ejemplo, almacenar los datos en la base de datos, realizar operaciones, etc.

        // Retorna una respuesta de éxito
        /* return response()->json([
            'message' => 'Datos procesados con éxito.',
            'registro' => $registro,
        ], 201); */

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
                'tel' => $registro['telefono'],
                'email' => $registro['email']
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

            $buscarDatos = (new SAHIController)->buscarEpsPlanContrato($registro->atencion_id);

            if (!$buscarDatos) {
                $eps = $buscarDatos[0]->NomTercero;
                $contrato = $buscarDatos[0]->DesContrato;
                $plan = $buscarDatos[0]->DesPlan;
                $servicio = $buscarDatos[0]->NomUbicacion;
            }
        }

        /**
         * Inserto el estudio en Lidiag
         */
        $estudio = Estudio::firstOrCreate(
            ['study_id' => $registro['study_pk']],
            [
                'study_pk' => $registro['study_pk'],
                'study_iuid' => $registro['study_iuid'],
                'study_id' => $registro['study_id'],
                'fec_estudio' => $registro['study_datetime'],
                'accession_no' => $registro['accession_no'],
                'study_desc' => $registro['study_desc'],
                'mods_in_study' => $registro['mods_in_study'],
                'email_reportar' => $registro['email_reportar'],
                'paciente_id' => $paciente->id,
                'medico_id' => $registro['medico_id'],
                'quien_registro_id' => auth()->user()->id,
                'sede_id' => $registro['sede_id'],
                'prioridad_id' => $registro['prioridad_id'],
                'observaciones' => $registro['observaciones'],
                'atencion' => $registro['atencion_id'],
                'servicio' => $servicio,
                'eps' => $eps,
                'contrato' => $contrato,
                'plan' => $plan,
            ]
        );

        /**
         * Elimino los productos existentes para almacenar los nuevos
         */
        $productos = EstudioProducto::where('estudio_id', '=', $estudio->id);
        $productos->delete();
        foreach ($registro['productosEstudio'] as $Producto) {
            if ($Producto['checked'] == true) {

                $productoDicom = ConfigProducto::firstOrCreate(
                    ['cod_cups' => $Producto['cod_cups']],
                    [
                        'cod_cups' => $Producto['cod_cups'],
                        'nom_produc' => $Producto['nom_produc'],
                        'requi_lectura' => 0,
                    ]
                );

                /* $productoDicom = ConfigProducto::where('cod_cups', '=', $Producto->cod_cups)
                    ->where('requi_lectura', '=', 1)->get();

                if ($productoDicom) { */
                EstudioProducto::create([
                    'estudio_id' => $estudio->id,
                    'producto_id' => $productoDicom->id,
                    'cod_cups' => $productoDicom->cod_cups,
                    'nom_produc' => $productoDicom->nom_produc,
                ]);
                //}
            }
        }
        /**
         * Elimino los diagnosticos existentes para almacenar los nuevos
         */
        $diagnosticos = EstudioDiagnostico::where('estudio_id', '=', $estudio->id);
        $diagnosticos->delete();
        foreach ($registro['diagnosticosEstudio'] as $Diagnostico) {
            EstudioDiagnostico::create([
                'estudio_id' => $estudio->id,
                'cod_diagnos' => $Diagnostico['cod_diagnos'],
                'nom_diagnos' => $Diagnostico['nom_diagnos'],
            ]);
        }

        /**
         * Almacenos los soportes de la HC
         */
        if ($request->hasFile('archivo1')) {
            $this->procesarArchivo($request->archivo1, $estudio->id);
        }
        if ($request->hasFile('archivo2')) {
            $this->procesarArchivo($request->archivo2, $estudio->id);
        }
        if ($request->hasFile('archivo3')) {
            $this->procesarArchivo($request->archivo3, $estudio->id);
        }

        if ($request->email != "") {
            $mailable = new NotificacionAsignacionDeLectura($paciente, $estudio);
            Mail::to($request->email)->send($mailable);
        }

        return response()->json(['message' => 'La asignación de la lectura se realizó correctamente.']);
    }

    public function listarPendientesMedico(Request $request, Estudio $estudio)
    {
        $misPendiente = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.sexo',
            'estu_produc.id as id_producto_lectura',
            'estu_produc.cod_cups',
            'estu_produc.nom_produc',
            'estu_produc.fechor_lectura',
            'users.name AS quien_registro',
            'priori.id as priori_id',
            'priori.nom_priori',
            'priori.nivel AS priori_nivel',
            'priori.tiempo AS priori_tiempo',
            'sesde.nom_sede as nom_sede',
            'sesde.url_oviyam',
            'sesde.tap_oviyam'
        )
            ->join('pacientes AS pacien', 'estudios.paciente_id',  '=', 'pacien.id')
            ->join('config_sedes AS sesde', 'estudios.sede_id', '=', 'sesde.id')
            ->join('estudios_productos AS estu_produc', 'estu_produc.estudio_id', '=', 'estudios.id')
            ->join('users', 'estudios.quien_registro_id', '=', 'users.id')
            ->join('config_prioridades AS priori', 'estudios.prioridad_id', '=', 'priori.id')
            ->join('config_productos AS config_produc', 'estu_produc.producto_id', '=', 'config_produc.id')
            ->where('estudios.medico_id', $request->id)
            ->where('estu_produc.activo', 1)
            ->whereNull('estu_produc.transcriptor_id')
            ->where('config_produc.requi_lectura', 1)
            ->whereNull('estu_produc.fechor_trascrito')
            ->whereNull('estu_produc.fechor_lectura')
            ->orderBy('priori.nom_priori')->get();

        return response()->json($misPendiente, 200);
    }

    public function listarDiagnosticoPorEstudio(Request $request)
    {
        $diagnosticos = EstudioDiagnostico::where('estudio_id', '=', $request->id)->get();
        return response()->json($diagnosticos);
    }

    public function leerEstudio(estudioLeerRequest $request, EstudioProducto $estudioProducto)
    {
        $productoLectura = EstudioProducto::findOrFail($request->id_producto_lectura);
        $productoLectura->lectura = $request->lectura;
        $productoLectura->fechor_lectura = Carbon::now();
        $productoLectura->save();

        return response()->json(['message' => 'La lectura se realizó correctamente.']);
    }

    public function listarPendientesTrascribir(Request $request)
    {
        $misPendiente = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.direccion',
            'pacien.email',
            'pacien.tel',
            'estudios_productos.id as id_producto_lectura',
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
            ->whereNotNull('estudios_productos.fechor_lectura')
            ->where('estudios_productos.activo', '=', 1)
            ->whereNull('estudios_productos.transcriptor_id')
            ->orderBy('estudios.created_at', 'ASC')
            ->get();

        return response()->json($misPendiente);
    }

    public function guardarTranscripcion(Request $request)
    {
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
                'email' => $request->email
            ]
        );

        /**
         * Actualizo la lectura del estudio
         */
        $transcribirEstudios = EstudioProducto::findOrFail($request->id_producto_lectura);
        $transcribirEstudios->lectura = $request->lectura;
        $transcribirEstudios->transcriptor_id = auth()->user()->id;
        $transcribirEstudios->fechor_trascrito = Carbon::now();
        $transcribirEstudios->save();

        /**
         * Si la sede donde se realizó el estudio tiene interface hago el envio del reporte a la HC del hospital
         */
        $sede = ConfigSede::findOrfail($request->sede_id);
        if ($sede->interface_software === 'SAHI') {
            $transcribir = (new SAHITranscibirController())->enviarEstudio($request->atencion, $request->medico_id, $request->lectura, $request->cod_cups);
        }

        /**
         * Genero el reporte de la lectura del estudio
         * 1. Genero laplantilla .docx con la informacion de la lectura por prte del medico
         * 2. Convierto la plantilla .docx a .pdf
         * 3. Notificacion por correo electronico
         */

        $genearPlantilla = (new PlantillaEstudioController())->editWord($request);
        if ($genearPlantilla->status() === 402) {
            return response()->json(['message' => 'La plantilla para la sede ' . $sede->nom_sede . ', no está  configurada, por favor consulte con el administrador del sistema'], 402);
        } else {
            if ($genearPlantilla->status() == 200) {

                $notificar = (new EstudioNotificacionController())->notificarEmail($request);

                if ($notificar->status() == 200) {
                    return response()->json('Lectura transferido correctamente', 200);
                }
            } else {
                return response()->json('Error en la trnaferencia de la lectura', 400);
            }
        }

        return response()->json(['message' => 'La transcripción se guardo correctamente.']);
    }

    public function penditesPorLeerTranscribir(Request $request)
    {
        $pendiente = Estudio::select(
            'estudios.*',
            'pacien.num_docu',
            'pacien.nombres as nom_pacien',
            'pacien.fec_naci',
            'pacien.sexo',
            'produc.id as id_producto_lectura',
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
            ->join('pacientes AS pacien', 'estudios.paciente_id',  '=', 'pacien.id')
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

    public function descargarPdfLectura($nomArchivo)
    {
        $rutaArchivo = storage_path('app/reporte_lecturas/' . $nomArchivo);
        $headers = array(
            'Content-Type: application/pdf',
        );
        return response()->download($rutaArchivo, $nomArchivo, $headers);
    }

    public function notificarEmail(Request $request)
    {
        $genearPlantilla = (new PlantillaEstudioController())->editWord($request);
        if ($genearPlantilla->status() == 200) {
            $notificar = (new EstudioNotificacionController())->notificarEmail($request);
            return $notificar;
        } else {
            return $genearPlantilla;
        }
    }
}
