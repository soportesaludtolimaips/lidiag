<?php

namespace App\Services\Interfaces;

use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioProductoAdendum;
use App\Models\General\Paciente;
use App\Models\User;
use App\Services\BaseService;
use App\Services\Dcm4chee\StudyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TeleRadiologiaService extends BaseService
{
    /**
     * Constructor del servicio de tele-radiología.
     *
     * @param  StudyService  $studyService  Servicio para consulta de estudios DICOM en DCM4CHEE.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function __construct(
        private readonly StudyService $studyService,
    ) {}

    /**
     * Lista los estudios disponibles en tele-radiología delegando a StudyService.
     *
     * Flujo del proceso:
     * 1. Normaliza los filtros de búsqueda recibidos (array o Request).
     * 2. Delega la consulta al repositorio PACS de la sede mediante StudyService.
     * 3. Retorna el resultado estructurado con los estudios y su estado de agendamiento.
     *
     * @param  array|Request  $filtros  Filtros de búsqueda: sede_id, bus_nom_num_docu, bus_fech_ini, bus_fecha_fin.
     *
     * @returns array Resultado con los estudios encontrados.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-06
     */
    public function listarEstudios(array|Request $filtros): array
    {
        $data = $filtros instanceof Request ? $filtros->all() : $filtros;

        return $this->studyService->listarEstudios($data);
    }

    /**
     * Endpoint no implementado. Debe usarse enviarOrdenTeleRadiologia.
     *
     * @returns array Respuesta con error 501.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function enviarEstudio(): array
    {
        return $this->error('Endpoint no implementado. Use /tele-radiologia-enviar-orden y /estudios.', 501);
    }

    /**
     * Envía una orden de tele-radiología a la API externa configurada.
     *
     * @param  array  $orden  Datos de la orden a enviar.
     *
     * @returns array Respuesta con confirmación o error de la API externa.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function enviarOrdenTeleRadiologia(array $orden): array
    {
        try {
            $url = config('services.teleradiologia.orden_url');
            $verifySsl = (bool) config('services.teleradiologia.verify_ssl', false);

            $httpClient = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
                ->connectTimeout((int) config('services.teleradiologia.connect_timeout'))
                ->timeout((int) config('services.teleradiologia.timeout'))
                ->retry((int) config('services.teleradiologia.retries'), 200)
                ->acceptJson();

            if (! $verifySsl) {
                $httpClient->withoutVerifying();
            }

            $response = $httpClient->post($url, $orden);

            if ($response->successful()) {
                $json = $response->json();

                $confirmation = null;

                if (is_array($json)) {
                    $confirmation = $json['confirmation']
                        ?? $json['confirmation_id']
                        ?? $json['id']
                        ?? null;

                    if (
                        $confirmation === null &&
                        isset($json['data']) &&
                        is_array($json['data'])
                    ) {
                        $confirmation = $json['data']['confirmation']
                            ?? $json['data']['id']
                            ?? null;
                    }
                }

                return $this->success([
                    'message' => $json['message'] ?? 'Orden enviada correctamente.',
                    'confirmation' => $confirmation,
                    'data' => $json,
                ], $json['message'] ?? 'Orden enviada correctamente.', $response->status());
            }

            return $this->error(
                'Error en la API externa',
                $response->status(),
                $response->json() ?? $response->body()
            );
        } catch (\Throwable $e) {
            Log::error("Error en enviarOrdenTeleRadiologia: ".$e->getMessage());

            return $this->error('Error interno del servidor proxy', 500, $e->getMessage());
        }
    }

    /**
     * Recibe y procesa la lectura de tele-radiología, persistiendo médico, paciente y adendum.
     *
     * @param  array  $data  Datos de la lectura con study_iuid, cod_cups, paciente, medico, lectura.
     *
     * @returns array Respuesta con el ID del estudio procesado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function recibirLectura(array $data): array
    {
        try {
            $d = $data;

            $medicoData = (isset($d['adendum']['medico']))
                ? $d['adendum']['medico']
                : $d['medico'];

            $medico = $this->procesarMedicoConFirma($medicoData);

            $estudio = Estudio::where('study_iuid', $d['study_iuid'])->first();

            if (! $estudio) {
                return $this->notFound('Estudio no encontrado con IUID: '.$d['study_iuid']);
            }

            $estudioProducto = EstudioProducto::where('estudio_id', $estudio->id)
                ->where('cod_cups', $d['cod_cups'])
                ->first();

            if (! $estudioProducto) {
                return $this->notFound('Producto CUPS no encontrado en este estudio.');
            }

            if (isset($d['adendum'])) {
                $lecturaAdendum = $d['adendum']['lectura']
                    ?? $d['adendum']['informe']
                    ?? $d['adendum']['reporte']
                    ?? $d['adendum']['texto_lectura']
                    ?? '';

                if (is_array($lecturaAdendum)) {
                    $lecturaAdendum = json_encode($lecturaAdendum, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                }

                $adendum = EstudioProductoAdendum::create([
                    'medico_id' => $medico->id,
                    'estudio_producto_id' => $estudioProducto->id,
                    'fechor_lectura' => $d['adendum']['fecha'] ?? now(),
                    'lectura' => $lecturaAdendum,
                    'activo' => 1,
                ]);

                $updateAdendum = [
                    'lectura' => $lecturaAdendum,
                    'fechor_lectura' => $d['adendum']['fecha'] ?? $d['fechor_lectura'] ?? now(),
                ];
                if (Schema::hasColumn('estudios_productos', 'fechor_recepcion_tele')) {
                    $updateAdendum['fechor_recepcion_tele'] = now();
                }

                $estudioProducto->update($updateAdendum);

                Log::info('TeleRadiologiaService::recibirLectura - Adendum procesado:', [
                    'estudio_id' => $estudio->id,
                    'adendum_id' => $adendum->id,
                    'lectura_length' => strlen((string) $lecturaAdendum),
                ]);

                return $this->success([
                    'estudio_id' => $estudio->id,
                    'adendum_id' => $adendum->id,
                ], 'Lectura y adendum procesados correctamente.');
            }

            $sexo = strtoupper(substr((string) ($d['paciente']['sexo'] ?? 'M'), 0, 1));
            Paciente::updateOrCreate(
                ['num_docu' => $d['paciente']['num_docu']],
                [
                    'tipo_documento_id' => 1,
                    'nombres' => $d['paciente']['nom_pacien'] ?? null,
                    'sexo' => in_array($sexo, ['M', 'F']) ? $sexo : 'M',
                ]
            );

            if (! $estudio->medico_id) {
                $estudio->update(['medico_id' => $medico->id]);
            }

            // Resolver el texto de la lectura tolerando posibles variantes de clave
            $lecturaTexto = $d['lectura']
                ?? $d['informe']
                ?? $d['reporte']
                ?? $d['resultado']
                ?? $d['texto_lectura']
                ?? $d['conclusion']
                ?? null;

            if (is_array($lecturaTexto)) {
                $lecturaTexto = json_encode($lecturaTexto, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            Log::info('TeleRadiologiaService::recibirLectura - Procesando lectura de estudio:', [
                'study_iuid' => $d['study_iuid'] ?? null,
                'cod_cups' => $d['cod_cups'] ?? null,
                'estudio_id' => $estudio->id,
                'estudio_producto_id' => $estudioProducto->id,
                'has_lectura_key' => array_key_exists('lectura', $d),
                'keys_payload' => array_keys($d),
                'lectura_resolvida_length' => strlen((string) $lecturaTexto),
                'lectura_resolvida_preview' => substr((string) $lecturaTexto, 0, 200),
                'payload_completo' => $d,
            ]);

            $updateLectura = [
                'fechor_lectura' => $d['fechor_lectura'] ?? $d['fecha_lectura'] ?? now(),
                'lectura' => $lecturaTexto,
            ];
            if (Schema::hasColumn('estudios_productos', 'fechor_recepcion_tele')) {
                $updateLectura['fechor_recepcion_tele'] = now();
            }

            $estudioProducto->update($updateLectura);

            return $this->success([
                'estudio_id' => $estudio->id,
                'medico' => $medico->name,
            ], 'Lectura procesada correctamente.');
        } catch (\Throwable $e) {
            Log::error("Error en recibirLectura [study_iuid=".($data['study_iuid'] ?? '?')."]: ".$e->getMessage());

            return $this->error('Error interno del servidor.', 500, $e->getMessage());
        }
    }

    /**
     * Procesa o crea el registro del médico y almacena su firma digital.
     *
     * @param  array  $medicoData  Datos del médico: reg_medico, name, num_docu, firma_medico.
     *
     * @returns User Modelo del médico procesado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    private function procesarMedicoConFirma(array $medicoData)
    {
        $medico = User::firstOrNew(['reg_med' => $medicoData['reg_medico']]);

        $medico->name = 'TL '.($medicoData['name'] ?? 'Sin Nombre');
        $medico->reg_med = $medicoData['reg_medico'] ?? $medico->reg_med;

        if (! $medico->exists) {
            $medico->email = ($medicoData['num_docu'] ?? 'unknown').'@teleradiologia.com';
            $medico->password = bcrypt('password');
        }

        $firmaRaw = $medicoData['firma_medico'] ?? null;

        if ($firmaRaw && strlen($firmaRaw) > 100) {
            try {
                $extension = 'png';
                if (preg_match('/^data:image\/(\w+);base64,/', $firmaRaw, $type)) {
                    $extension = strtolower($type[1]);
                    $firmaRaw = substr($firmaRaw, strpos($firmaRaw, ',') + 1);
                }

                $imgData = base64_decode($firmaRaw);

                if ($imgData) {
                    if ($medico->firma && Storage::disk('firmas_medicos')->exists($medico->firma)) {
                        Storage::disk('firmas_medicos')->delete($medico->firma);
                    }

                    $nombreArchivo = Str::random(50).'.'.$extension;

                    $guardadoExitoso = Storage::disk('firmas_medicos')->put($nombreArchivo, $imgData);

                    if ($guardadoExitoso) {
                        $medico->firma = 'firmas_medicos/'.$nombreArchivo;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al sustituir firma del médico {$medico->num_docu}: ".$e->getMessage());
            }
        }

        $medico->save();

        return $medico;
    }
}
