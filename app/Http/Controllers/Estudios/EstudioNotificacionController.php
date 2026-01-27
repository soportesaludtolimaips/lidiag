<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Mail\NotificacionDeLectura;
use App\Mail\ReportarLectura;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Instance;
use App\Models\Estudio\Estudio;
use App\Models\General\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Twilio\Rest\Client;

class EstudioNotificacionController extends Controller
{
    public function __construct()
    {
        ini_set('max_execution_time', -1);
    }

    /**
     * Lista los estudios que ya tienen transcripción y están activos, listos para notificar.
     *
     * @param Request $request Filtros por número de documento (`bus_num_docu`) o nombre (`bus_nom`).
     * @return \Illuminate\Http\JsonResponse Lista de estudios.
     */
    public function listarEstudios(Request $request)
    {
        $query = Estudio::query();

        $query->select(
            'estudios.id',
            'estudios.study_iuid',
            'estudios.atencion',
            'estudios.fec_estudio',
            'estudios_productos.fechor_lectura',
            'pacientes.num_docu',
            'pacientes.nombres as nom_pacien',
            'pacientes.direccion',
            'pacientes.email',
            'estudios_productos.id AS estudio_producto_id',
            'estudios_productos.lectura',
            'estudios_productos.cod_cups',
            'estudios_productos.nom_produc',
            'estudios_productos.fechor_trascrito',
            'users.num_docu as num_docu_medico',
            'users.name as nombres_medico',
            'users.reg_med as reg_medico',
            'users.firma as firma_medico',
            'config_sedes.id as sede_id',
            'config_sedes.nom_sede',
            'config_sedes.url_oviyam',
            'config_sedes.tap_oviyam',
            'config_sedes.plantilla'
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->join('users', 'users.id', '=', 'estudios.medico_id')
            ->whereNotNull('estudios_productos.transcriptor_id')
            ->where('estudios_productos.activo', '=', 1);

        if ($request->bus_num_docu != "") {
            $query->where('pacientes.num_docu', '=', $request->bus_num_docu);
        }

        if ($request->bus_nom != "") {
            $query->where('pacientes.nombres', 'like', "%$request->bus_nom%");
        }

        $registros = $query->get();
        return response()->json($registros);
    }

    /**
     * Notifica el resultado del estudio por email y actualiza datos del paciente.
     *
     * Flujo del proceso:
     * 1. Envía el reporte a la API externa de Lidiag-reportes.
     * 2. Actualiza el email del paciente en la base de datos local.
     * 3. Si el paciente tiene email:
     *    - Descarga imágenes DICOM (temporalmente).
     *    - Envía email con el PDF adjunto usando `NotificacionDeLectura`.
     * 4. Limpia archivos temporales (PDF e imágenes).
     *
     * @param object $estudio Objeto con datos del estudio y paciente.
     * @return \Illuminate\Http\JsonResponse Confirmación.
     */
    public function notificarEmail($estudio)
    {
        $nombrePaciente = str_replace('^^^', '', $estudio->nom_pacien);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $nomArchivoReporte = $estudio->estudio_producto_id . "-" . $estudio->num_docu . "-" . $nombrePaciente . ".pdf";

        /**
         * Envio la lectura a Lidiag-reportes para que este diponible para la descarga por parte del paciente
         */
        $urlApiReportes = rtrim(config('app.URL_API_REPORTES'), '/') . '/api/almacenarLectura';

        $secret = env('API_HMAC_SECRET'); // mismo secreto que en lidiag-reportes
        $ts = time();

        $payload = [
            'num_docu' => $estudio->num_docu,
            'name' => $estudio->nom_pacien,
            'fec_naci' => $estudio->fec_naci,
            'nom_produc' => $estudio->nom_produc,
            'fechor_lectura' => $estudio->fechor_lectura,
            'nom_sede' => $estudio->nom_sede,
            'url_oviyam' => $estudio->url_oviyam,
            'study_iuid' => $estudio->study_iuid,
            'tap_oviyam' => $estudio->tap_oviyam,
        ];

        $fileBytes = file_get_contents(storage_path('app/reporte_lecturas/' . $nomArchivoReporte));

        $method = 'POST';
        $path = parse_url($urlApiReportes, PHP_URL_PATH) ?: '/api/almacenarLectura';

        ksort($payload);
        $payloadCanonical = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $payloadSha = hash('sha256', $payloadCanonical);
        $fileSha = hash('sha256', $fileBytes ?: '');

        $stringToSign = implode("\n", [$method, $path, (string)$ts, $payloadSha, $fileSha]);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));

        $response = Http::withoutVerifying()
            ->acceptJson()
            ->withHeaders([
                'X-Timestamp' => (string) $ts,
                'X-Signature' => $signature,
            ])
            ->timeout(60)
            ->attach('file_reporte', $fileBytes, $nomArchivoReporte)
            ->post($urlApiReportes, $payload);

        //return $response;

        if ($response->successful()) {
            // El reporte se subió bien
        }

        /**
         * Actualizo el nombre y correo electronico del paciente
         */
        Paciente::updateOrCreate(
            ['num_docu' => $estudio->num_docu],
            [
                'nombres' => $estudio->nom_pacien,
                'email' => $estudio->email
            ]
        );

        /**
         * Envio de email con el adjunto del reporte de la lectura del estudio
         */
        if ($estudio->email != "") {
            //$imagenes = $this->descargaImgDicom($estudio->sede_id, $estudio->study_iuid);
            //return $imagenes;
            $mailable = new NotificacionDeLectura($estudio, $nomArchivoReporte, $estudio->study_iuid);
            Mail::to($estudio->email)->send($mailable);
        }

        if ($estudio->email_reportar != "") {
            $mailable = new ReportarLectura($estudio, $nomArchivoReporte);
            Mail::to($estudio->email_reportar)->send($mailable);
        }

        unlink(storage_path('app/reporte_lecturas/' . $nomArchivoReporte));

        // Obtener las rutas de las imágenes que se enviaron
        $imagenesAdjuntos = Storage::files('img_dicom/' . $estudio->study_iuid);

        // Eliminar cada imagen
        foreach ($imagenesAdjuntos as $imagen) {
            Storage::delete($imagen);
        }

        // Eliminar la carpeta que contiene las imágenes
        Storage::deleteDirectory('img_dicom/' . $estudio->study_iuid);

        return response()->json(['message' => 'La notificación se generó correctamente.']);
    }

    public function enviarPortalPacientes($estudio)
    {
        $nombrePaciente = str_replace('^^^', '', $estudio->nom_pacien);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $nomArchivoReporte = $estudio->estudio_producto_id . "-" . $estudio->num_docu . "-" . $nombrePaciente . ".pdf";

        /**
         * Envio la lectura a Lidiag-reportes para que este diponible para la descarga por parte del paciente
         */
        $urlApiReportes = rtrim(config('app.URL_API_REPORTES'), '/') . '/api/almacenarLectura';


        $secret = env('API_HMAC_SECRET'); // mismo secreto que en lidiag-reportes
        $ts = time();

        $payload = [
            'num_docu' => $estudio->num_docu,
            'name' => $estudio->nom_pacien,
            'fec_naci' => $estudio->fec_naci,
            'nom_produc' => $estudio->nom_produc,
            'fechor_lectura' => $estudio->fechor_lectura,
            'nom_sede' => $estudio->nom_sede,
            'url_oviyam' => $estudio->url_oviyam,
            'study_iuid' => $estudio->study_iuid,
            'tap_oviyam' => $estudio->tap_oviyam,
        ];

        $fileBytes = file_get_contents(storage_path('app/reporte_lecturas/' . $nomArchivoReporte));

        $method = 'POST';
        $path = parse_url($urlApiReportes, PHP_URL_PATH) ?: '/api/almacenarLectura';

        ksort($payload);
        $payloadCanonical = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $payloadSha = hash('sha256', $payloadCanonical);
        $fileSha = hash('sha256', $fileBytes ?: '');

        $stringToSign = implode("\n", [$method, $path, (string)$ts, $payloadSha, $fileSha]);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));

        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->attach('file_reporte', $fileBytes, $nomArchivoReporte)
                ->post('https://lidiag-reportes.com/api/almacenarLectura', $payload);

            return $response;
            if ($response->successful()) {
                return response()->json(['message' => 'El estudio se ha enviado al portal de pacientes correctamente.']);
            }

            return response()->json([
                'message' => 'Error al enviar el reporte al portal de pacientes.',
                'status' => $response->status(),
                'error' => $response->body(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al enviar el reporte al portal de pacientes.', 'error' => $e->getMessage()], 400);
        } finally {
            // Limpieza best-effort del archivo temporal del reporte
            try {
                @unlink(storage_path('app/reporte_lecturas/' . $nomArchivoReporte));
            } catch (\Exception $e) {
                // no-op
            }
        }
    }

    /**
     * Descarga imágenes DICOM (convertidas a JPEG) desde el servidor WADO de la sede.
     *
     * @param int $sedeId ID de la sede.
     * @param string $study_iuid UID del estudio.
     * @return \Illuminate\Http\JsonResponse Resultado de la descarga.
     */
    public static function descargaImgDicom($sedeId, $study_iuid)
    {
        $sedeActual = ConfigSede::find($sedeId);

        //Configuramos la segunda base de datos
        config(['database.connections.mysql_sucursal' => [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', $sedeActual->ip_dcm),
            'port' => env('DB_SUCURSAL_PUERTO', $sedeActual->puerto_dcm),
            'database' => env('DB_SUCURSAL_DATABASE', $sedeActual->bd_dcm),
            'username' => env('DB_SUCURSAL_USERNAME', $sedeActual->usuario_dcm),
            'password' => env('DB_SUCURSAL_PASSWORD', $sedeActual->password_dcm),
            'charset' => env('DB_SUCURSAL_charset', 'latin1'),
            'collation' => env('DB_SUCURSAL_collation', 'latin1_swedish_ci'),
        ]]);

        //Aplicamos la nueva configuracion de bade datos
        Config::set('database.default', 'mysql_sucursal');

        // Obtener todas las series y las instancias (imágenes) del estudio
        $imagenes = Instance::join('series', 'instance.series_fk', '=', 'series.pk')
            ->join('study', 'series.study_fk', '=', 'study.pk')
            ->where('study.study_iuid', $study_iuid)
            ->select('series.series_iuid', 'instance.sop_iuid')
            ->limit(10)
            ->get();

        // Verificar si se encontraron imágenes
        if ($imagenes->isEmpty()) {
            return response()->json(['error' => 'No se encontraron imágenes para el estudio especificado'], 404);
        }

        // Iterar sobre cada imagen, descargar y guardar en el storage
        foreach ($imagenes as $imagen) {
            $seriesUID = $imagen->series_iuid;
            $objectUID = $imagen->sop_iuid;

            // URL del servicio WADO para la imagen
            $wadoUrl = "http://" . $sedeActual->ip_dcm . ":8080/wado?requestType=WADO&studyUID=$study_iuid&seriesUID=$seriesUID&objectUID=$objectUID&contentType=image/jpeg";
            return $wadoUrl;

            // Hacer la solicitud HTTP GET para obtener la imagen
            $response = Http::get($wadoUrl);

            // Verificar si la solicitud fue exitosa
            if ($response->successful()) {
                // Obtener el contenido de la imagen
                $imageContent = $response->body();

                // Definir el nombre del archivo
                $filename = 'imagen_' . $study_iuid . '_' . $seriesUID . '_' . $objectUID . '.jpg';

                // Guardar la imagen en el directorio 'img_dicom' del storage
                Storage::put('img_dicom/' . $study_iuid . "/" . $filename, $imageContent);
            } else {
                // Manejo de errores si alguna imagen no se pudo descargar
                return response()->json(['error' => 'Error al descargar una de las imágenes'], 500);
            }
        }

        // Devolver un mensaje de éxito
        return response()->json(['success' => 'Todas las imágenes se han descargado y guardado correctamente'], 200);
    }

    /**
     * Envía una notificación por WhatsApp usando Twilio.
     *
     * @param string $numeroTelefono Número de destino.
     * @param string $mensaje Cuerpo del mensaje.
     * @return \Illuminate\Http\JsonResponse Resultado del envío.
     */
    public function enviarMensajeWhatsApp($numeroTelefono, $mensaje)
    {
        $sid    = "ACcdc29948e05772a83ac3e0db74169c96";
        $token  = "b92011d04a5d9d1b929a1b97d7dff9f7";
        $twilio = new Client($sid, $token);

        /* $message = $twilio->messages
            ->create(
                "whatsapp:+573161754039", // to
                array(
                    "from" => "whatsapp:+14155238886",
                    "contentSid" => "HXb5b62575e6e4ff6129ad7c8efe1f983e",
                    //"contentVariables" => {"1":"12/1","2":"3pm"},
                    'body' => 'Hola {nombre}, tu reporte de imagen diagnóstica está listo. Puedes revisarlo aquí: {link}. Gracias por elegirnos.',
                    'mediaUrl' => ['https://tureporte.com/reporte.pdf']
                )
            ); */

        $message = $twilio->messages->create(
            'whatsapp:+573223669021', // Número del destinatario
            [
                "from" => "whatsapp:+14155238886",
                "contentSid" => "HXb5b62575e6e4ff6129ad7c8efe1f983e",
                'body' => 'Hola {nombre}, tu reporte de imagen diagnóstica está listo. Puedes revisarlo aquí: {link}. Gracias por elegirnos.',
                'mediaUrl' => ['https://tureporte.com/reporte.pdf']  // Enlace o URL que quieras compartir
            ]
        );

        return response()->json(['success' => 'Mensaje enviado por WhatsApp', 'message' => $message->sid], 200);
    }
}
