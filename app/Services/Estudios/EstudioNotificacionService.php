<?php

namespace App\Services\Estudios;

use App\Helpers\DicomHelper;
use App\Mail\NotificacionDeLectura;
use App\Mail\ReportarLectura;
use App\Models\Estudio\Estudio;
use App\Services\BaseService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

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

        if (! empty($filters['bus_fech_ini']) && ! empty($filters['bus_fecha_fin'])) {
            $inicio = Carbon::parse($filters['bus_fech_ini'])->startOfDay();
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

        @unlink($reportePath);

        DicomHelper::cleanupTempImages("img_dicom/{$estudio->study_iuid}");

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
        $result = DicomHelper::downloadStudyImages(
            $sedeId,
            $study_iuid,
            'image/jpeg',
            "img_dicom/{$study_iuid}",
            20
        );

        if (isset($result['error'])) {
            return (new self)->error($result['error'], 404);
        }

        if ($result['success'] === 0) {
            return (new self)->error('No se encontraron imágenes para el estudio especificado', 404);
        }

        if ($result['failed'] > 0) {
            return (new self)->error('Error al descargar algunas imágenes', 500);
        }

        return (new self)->success(null, 'Todas las imágenes se han descargado y guardado correctamente');
    }

    public function eliminarReporte($reportePath): void
    {
        @unlink($reportePath);
    }
}
