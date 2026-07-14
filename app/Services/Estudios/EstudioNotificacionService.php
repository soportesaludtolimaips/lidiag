<?php

namespace App\Services\Estudios;

use App\Mail\NotificacionDeLectura;
use App\Mail\ReportarLectura;
use App\Models\Configuracion\ConfigSede;
use App\Models\Dcm4chee\Instance;
use App\Models\Estudio\Estudio;
use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Twilio\Rest\Client;

class EstudioNotificacionService extends BaseService
{
    public function listarEstudios(array $filters): array
    {
        $query = Estudio::query()->select(
            'estudios.id', 'estudios.study_iuid', 'estudios.atencion', 'estudios.fec_estudio',
            'estudios_productos.fechor_lectura',
            'pacientes.num_docu', 'pacientes.nombres as nom_pacien',
            'pacientes.direccion', 'pacientes.email', 'pacientes.tel',
            'pacientes.sexo', 'pacientes.fec_naci',
            'estudios_productos.id AS estudio_producto_id',
            'estudios_productos.lectura', 'estudios_productos.cod_cups', 'estudios_productos.nom_produc',
            'estudios_productos.fechor_trascrito',
            'users.num_docu as num_docu_medico', 'users.name as nombres_medico',
            'users.reg_med as reg_medico', 'users.firma as firma_medico',
            'config_sedes.id as sede_id', 'config_sedes.nom_sede',
            'config_sedes.url_oviyam', 'config_sedes.tap_oviyam', 'config_sedes.plantilla_rx',
        )
            ->join('estudios_productos', 'estudios_productos.estudio_id', '=', 'estudios.id')
            ->join('pacientes', 'estudios.paciente_id', '=', 'pacientes.id')
            ->join('config_sedes', 'estudios.sede_id', '=', 'config_sedes.id')
            ->join('users', 'users.id', '=', 'estudios.medico_id')
            ->whereNotNull('estudios_productos.transcriptor_id')
            ->where('estudios_productos.activo', '=', 1);

        if (! empty($filters['bus_num_docu'])) {
            $query->where('pacientes.num_docu', '=', $filters['bus_num_docu']);
        }

        if (! empty($filters['bus_nom'])) {
            $query->where('pacientes.nombres', 'like', "%{$filters['bus_nom']}%");
        }

        if (! empty($filters['bus_fehc_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $inicio = Carbon::parse($filters['bus_fehc_ini'])->startOfDay();
            $fin = Carbon::parse($filters['bus_fecha_fin'])->endOfDay();
            $query->whereBetween('estudios.fec_estudio', [$inicio, $fin]);
        }

        $registros = $query->orderByDesc('estudios_productos.fechor_trascrito')
            ->limit(1000)
            ->get();

        return $this->success($registros);
    }

    public function notificarEmail($estudio, string $archivoReporte): array
    {
        $reportePath = storage_path('app/reporte_lecturas/'.$archivoReporte);
        if (! file_exists($reportePath)) {
            return $this->notFound('No se encontró el reporte PDF para enviar al portal.');
        }

        if (! empty($estudio->email)) {
            $imagenes = self::descargaImagenesDicom($estudio->sede_id, $estudio->study_iuid);
            $mailable = new NotificacionDeLectura($estudio, $archivoReporte, $estudio->study_iuid);
            Mail::to($estudio->email)->send($mailable);
        }

        if (! empty($estudio->email_reportar)) {
            $mailable = new ReportarLectura($estudio, $archivoReporte);
            Mail::to($estudio->email_reportar)->send($mailable);
        }

        @unlink($archivoReporte);

        $imagenesAdjuntos = Storage::exists('img_dicom/'.$estudio->study_iuid)
            ? Storage::files('img_dicom/'.$estudio->study_iuid) : [];

        foreach ($imagenesAdjuntos as $imagen) {
            Storage::delete($imagen);
        }
        Storage::deleteDirectory('img_dicom/'.$estudio->study_iuid);

        return $this->success(null, 'La notificación se generó correctamente.');
    }

    public function enviarPortalPacientes($estudio): array
    {
        $nombrePaciente = str_replace(['^^^', ' ^', '^'], ['', '', ' '], $estudio->nom_pacien ?? '');
        $nomArchivoReporte = ($estudio->estudio_producto_id ?? '0').'-'.($estudio->num_docu ?? '0').'-'.$nombrePaciente.'.pdf';

        $urlApiReportes = rtrim(config('app.URL_API_REPORTES'), '/').'/api/almacenarLectura';
        $secret = env('API_HMAC_SECRET');
        $ts = time();

        $payload = [
            'num_docu' => $estudio->num_docu ?? '',
            'name' => $estudio->nom_pacien ?? '',
            'fec_naci' => $estudio->fec_naci ?? '',
            'nom_produc' => $estudio->nom_produc ?? '',
            'fechor_lectura' => $estudio->fechor_lectura ?? '',
            'nom_sede' => $estudio->nom_sede ?? '',
            'url_oviyam' => $estudio->url_oviyam ?? '',
            'study_iuid' => $estudio->study_iuid ?? '',
            'tap_oviyam' => $estudio->tap_oviyam ?? '',
        ];

        $reportePath = storage_path('app/reporte_lecturas/'.$nomArchivoReporte);
        if (! file_exists($reportePath)) {
            return $this->notFound('No se encontró el reporte PDF para enviar al portal.');
        }

        $fileBytes = file_get_contents($reportePath);
        $method = 'POST';
        $path = parse_url($urlApiReportes, PHP_URL_PATH) ?: '/api/almacenarLectura';

        ksort($payload);
        $payloadCanonical = http_build_query($payload, '', '&', PHP_QUERY_RFC3986);
        $payloadSha = hash('sha256', $payloadCanonical);
        $fileSha = hash('sha256', $fileBytes ?: '');
        $stringToSign = implode("\n", [$method, $path, (string) $ts, $payloadSha, $fileSha]);
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $secret, true));

        try {
            $response = Http::withoutVerifying()
                ->acceptJson()
                ->withHeaders([
                    'X-Timestamp' => (string) $ts,
                    'X-Signature' => $signature,
                ])
                ->timeout(60)
                ->attach('file_reporte', $fileBytes, $nomArchivoReporte)
                ->post($urlApiReportes, $payload);

            if ($response->successful()) {
                return $this->success([
                    'reportePath' => $nomArchivoReporte,
                ], 'El estudio se ha enviado al portal de pacientes correctamente.');
            }

            return $this->error(
                'Error al enviar el reporte al portal de pacientes.',
                400
            );
        } catch (\Exception $e) {
            return $this->error('Error al enviar el reporte al portal de pacientes: '.$e->getMessage(), 400);
        } finally {
            try {
                @unlink($reportePath);
            } catch (\Exception $e) {
            }
        }
    }

    public static function descargaImagenesDicom($sedeId, $study_iuid): array
    {
        $sedeActual = ConfigSede::find($sedeId);
        if (! $sedeActual) {
            return (new self)->notFound('Sede no encontrada');
        }

        Config::set('database.connections.mysql_sucursal', [
            'driver' => 'mysql',
            'host' => env('DB_SUCURSAL_HOST', $sedeActual->ip_dcm),
            'port' => env('DB_SUCURSAL_PUERTO', $sedeActual->puerto_dcm),
            'database' => env('DB_SUCURSAL_DATABASE', $sedeActual->bd_dcm),
            'username' => env('DB_SUCURSAL_USERNAME', $sedeActual->usuario_dcm),
            'password' => env('DB_SUCURSAL_PASSWORD', $sedeActual->password_dcm),
            'charset' => env('DB_SUCURSAL_charset', 'latin1'),
            'collation' => env('DB_SUCURSAL_collation', 'latin1_swedish_ci'),
        ]);

        $originalDefault = Config::get('database.default');
        Config::set('database.default', 'mysql_sucursal');

        try {
            $imagenes = Instance::join('series', 'instance.series_fk', '=', 'series.pk')
                ->join('study', 'series.study_fk', '=', 'study.pk')
                ->where('study.study_iuid', $study_iuid)
                ->select('series.series_iuid', 'instance.sop_iuid')
                ->limit(20)
                ->get();

            if ($imagenes->isEmpty()) {
                return (new self)->notFound('No se encontraron imágenes para el estudio especificado');
            }

            foreach ($imagenes as $imagen) {
                $wadoUrl = "http://{$sedeActual->ip_dcm}:8080/wado?requestType=WADO&studyUID={$study_iuid}&seriesUID={$imagen->series_iuid}&objectUID={$imagen->sop_iuid}&contentType=image/jpeg";

                $response = Http::get($wadoUrl);
                if ($response->successful()) {
                    $filename = "imagen_{$study_iuid}_{$imagen->series_iuid}_{$imagen->sop_iuid}.jpg";
                    Storage::put("img_dicom/{$study_iuid}/{$filename}", $response->body());
                } else {
                    return (new self)->error('Error al descargar una de las imágenes', 500);
                }
            }

            return (new self)->success(null, 'Todas las imágenes se han descargado y guardado correctamente');
        } finally {
            Config::set('database.default', $originalDefault);
        }
    }

    public function enviarWhatsApp(string $numeroTelefono, string $mensaje): array
    {
        $sid = env('TWILIO_SID', "ACcdc29948e05772a83ac3e0db74169c96");
        $token = env('TWILIO_AUTH_TOKEN', "b92011d04a5d9d1b929a1b97d7dff9f7");
        $twilio = new Client($sid, $token);

        try {
            $message = $twilio->messages->create(
                'whatsapp:+573223669021',
                [
                    "from" => "whatsapp:+14155238886",
                    "contentSid" => "HXb5b62575e6e4ff6129ad7c8efe1f983e",
                    'body' => $mensaje,
                    'mediaUrl' => ['https://tureporte.com/reporte.pdf'],
                ]
            );

            return $this->success(['message_sid' => $message->sid], 'Mensaje enviado por WhatsApp');
        } catch (\Exception $e) {
            return $this->error('Error al enviar WhatsApp: '.$e->getMessage(), 500);
        }
    }

    public function eliminarReporte($reportePath): void
    {
        @unlink($reportePath);
    }
}
