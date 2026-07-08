<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dcm4chee\StudyController as Dcm4cheeStudyController;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use App\Models\Estudio\EstudioProductoAdendum;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TeleRadiologiaController extends Controller
{
    public function __construct()
    {
        // Este controlador se usa principalmente desde rutas /api (Sanctum SPA o token).
        // El webhook `recibirLectura` está protegido por HMAC en ruta (sin auth).
        /* $this->middleware('auth:sanctum')->except('recibirLectura');
        $this->middleware('permission:tele.ragiologia.index|tele.ragiologia.enviar|tele.ragiologia.transcribir|estudio.asignar|estudio.transcribir,web')
            ->except('recibirLectura'); */
    }

    /**
     * Lista estudios desde PACS (proxy a DCM4CHEE).
     *
     * Nota: este endpoint existe para el módulo de Tele-radiología.
     */
    public function listarEstudios(Request $request)
    {
        // Reutilizar el controlador de DCM4CHEE para evitar duplicar lógica
        return app(Dcm4cheeStudyController::class)->listarEstudios($request, new Study);
    }

    /**
     * Endpoint reservado para envío de estudios a teleradiología.
     *
     * Actualmente el flujo principal usa enviarOrdenTeleRadiologia + creación de estudio local.
     */
    public function enviarEstudio(Request $request)
    {
        return response()->json([
            'message' => 'Endpoint no implementado. Use /tele-radiologia-enviar-orden y /estudios.',
        ], 501);
    }

    /**
     * Envía una orden a la API externa de Teleradiología.
     *
     * @param  Request  $request  Datos de la orden.
     * @return JsonResponse Respuesta de la API externa o error.
     */
    /**
     * Envía una orden a la API externa de Teleradiología.
     *
     * @param  Request  $request  Datos de la orden.
     * @return JsonResponse Respuesta de la API externa o error.
     */
    public function enviarOrdenTeleRadiologia(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'orden' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $orden = $request->input('orden');

            if (!$orden) {
                return response()->json([
                    'message' => 'No se recibieron datos de la orden.'
                ], 400);
            }

            $url = config('services.teleradiologia.orden_url');

            $response = Http::withOptions([
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
            ])
                ->connectTimeout((int) config('services.teleradiologia.connect_timeout'))
                ->timeout((int) config('services.teleradiologia.timeout'))
                ->retry((int) config('services.teleradiologia.retries'), 200)
                ->acceptJson()
                ->post($url, $orden);

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

                return response()->json([
                    'message' => $json['message'] ?? 'Orden enviada correctamente.',
                    'confirmation' => $confirmation,
                    'data' => $json,
                ], $response->status());
            }

            return response()->json([
                'message' => 'Error en la API externa',
                'details' => $response->json() ?? $response->body(),
            ], $response->status());
        } catch (\Throwable $e) {

            return response()->json([
                'message' => 'Error interno en el servidor proxy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Recibe y procesa lecturas de TLIMAGEN (TLIMAGEN -> SaludTolima).
     *
     * Payload Lectura: study_pk, study_iuid, fec_estudio, accession_no, study_desc, mods_in_study,
     * sexo, paciente{num_docu,nom_pacien,fec_naci}, priori{id,nom_priori}, cod_cups, nom_produc,
     * fechor_lectura, lectura, medico{reg_medico,num_docu,name,firma_medico}, tipo_resultado.
     *
     * @param  Request  $request  Datos de la lectura.
     * @return JsonResponse Respuesta de confirmación.
     */
    public function recibirLectura(Request $request)
    {
        // 1. Procesamiento de Encoding y JSON (ISO-8859-1 a UTF-8)
        $rawContent = $request->getContent();
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $data = json_decode($utf8Content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'Error al decodificar el payload JSON.',
                'error' => json_last_error_msg(),
            ], 400);
        }
        // 3. Validación de datos mínimos requeridos
        $validator = Validator::make($data, [
            'study_iuid' => 'required|string',
            'cod_cups' => 'required|string',
            'paciente.num_docu' => 'required|string',
            // Validamos médico o adendum.medico según el caso
            'medico.num_docu' => 'required_without:adendum.medico.num_docu|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            $d = $data;

            // 4. Identificar y procesar al Médico (prioriza médico del adendum si existe)
            $medicoData = (isset($d['adendum']['medico']))
                ? $d['adendum']['medico']
                : $d['medico'];

            $medico = $this->procesarMedicoConFirma($medicoData);

            // 5. Buscar el estudio base por IUID
            $estudio = Estudio::where('study_iuid', $d['study_iuid'])->first();

            if (! $estudio) {
                return response()->json(['message' => 'Estudio no encontrado con IUID: ' . $d['study_iuid']], 404);
            }

            // 6. Buscar el producto específico del estudio (CUPS)
            $estudioProducto = EstudioProducto::where('estudio_id', $estudio->id)
                ->where('cod_cups', $d['cod_cups'])
                ->first();

            if (! $estudioProducto) {
                return response()->json(['message' => 'Producto CUPS no encontrado en este estudio.'], 404);
            }

            // --- FLUJO A: TIENE ADENDUM (crea registro de adendum y actualiza lectura principal) ---
            if (isset($d['adendum'])) {
                $adendum = EstudioProductoAdendum::create([
                    'medico_id' => $medico->id,
                    'estudio_producto_id' => $estudioProducto->id,
                    'fechor_lectura' => $d['adendum']['fecha'] ?? now(),
                    'lectura' => $d['adendum']['lectura'] ?? '',
                    'activo' => 1,
                ]);

                $estudioProducto->update([
                    'lectura' => $d['adendum']['lectura'] ?? null,
                    'fechor_lectura' => $d['adendum']['fecha'] ?? $d['fechor_lectura'] ?? null,
                ]);

                return response()->json([
                    'message' => 'Lectura y adendum procesados correctamente.',
                    'estudio_id' => $estudio->id,
                    'adendum_id' => $adendum->id,
                ], 200);
            }

            // --- FLUJO B: ES UNA LECTURA NORMAL ---

            // Procesar Paciente
            $sexo = strtoupper(substr((string) ($d['paciente']['sexo'] ?? 'M'), 0, 1));
            $paciente = Paciente::updateOrCreate(
                ['num_docu' => $d['paciente']['num_docu']],
                [
                    'tipo_documento_id' => 1,
                    'nombres' => $d['paciente']['nom_pacien'] ?? null,
                    'sexo' => in_array($sexo, ['M', 'F']) ? $sexo : 'M',
                    // 'fec_naci' => $d['paciente']['fec_naci'] ?? null,
                ]
            );

            // Asignar médico al estudio si no tiene uno
            if (! $estudio->medico_id) {
                $estudio->update(['medico_id' => $medico->id]);
            }

            // Actualizar el producto con la lectura final
            $estudioProducto->update([
                'fechor_lectura' => $d['fechor_lectura'] ?? $d['fecha_lectura'] ?? null,
                'lectura' => $d['lectura'] ?? null,
                // 'transcriptor_id' => $medico->id, // El médico que firma es el transcriptor/autor
            ]);

            return response()->json([
                'message' => 'Lectura procesada correctamente.',
                'estudio_id' => $estudio->id,
                'medico' => $medico->name,
            ], 200);
        } catch (\Exception $e) {
            Log::error("Error crítico en recibirLectura: " . $e->getMessage());

            return response()->json([
                'message' => 'Error interno del servidor.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesa la información del médico, crea/actualiza el registro
     * y gestiona la firma en base64 si está presente.
     *
     * @param  array  $medicoData  Datos provenientes del JSON
     * @return User
     */
    private function procesarMedicoConFirma(array $medicoData)
    {
        // 1. Buscar o instanciar el médico
        $medico = User::firstOrNew(['reg_med' => $medicoData['reg_medico']]);

        // 2. Actualizar datos básicos
        $medico->name = 'TL ' . ($medicoData['name'] ?? 'Sin Nombre');
        $medico->reg_med = $medicoData['reg_medico'] ?? $medico->reg_med;

        if (! $medico->exists) {
            $medico->email = $medicoData['num_docu'] . '@teleradiologia.com';
            $medico->password = bcrypt('password');
        }

        // 3. Procesar firma si recibimos un base64 real
        $firmaRaw = $medicoData['firma_medico'] ?? null;

        if ($firmaRaw && strlen($firmaRaw) > 100) {
            try {
                // Extraer extensión y limpiar base64
                $extension = 'png'; // Valor por defecto
                if (preg_match('/^data:image\/(\w+);base64,/', $firmaRaw, $type)) {
                    $extension = strtolower($type[1]);
                    $firmaRaw = substr($firmaRaw, strpos($firmaRaw, ',') + 1);
                }

                $imgData = base64_decode($firmaRaw);

                if ($imgData) {
                    // --- LÓGICA DE SUSTITUCIÓN ---
                    // Si el médico ya tenía una ruta de firma guardada, eliminamos el archivo físico
                    if ($medico->firma && Storage::disk('firmas_medicos')->exists($medico->firma)) {
                        Storage::disk('firmas_medicos')->delete($medico->firma);
                    }

                    // Generar nombre aleatorio de 50 caracteres
                    $nombreArchivo = Str::random(50) . "." . $extension;

                    // Guardar en el disco configurado
                    $guardadoExitoso = Storage::disk('firmas_medicos')->put($nombreArchivo, $imgData);

                    if ($guardadoExitoso) {
                        // Actualizar el path en el modelo
                        $medico->firma = 'firmas_medicos/' . $nombreArchivo;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error al sustituir firma del médico {$medico->num_docu}: " . $e->getMessage());
            }
        }

        $medico->save();

        return $medico;
    }
}
