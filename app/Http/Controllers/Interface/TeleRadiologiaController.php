<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dcm4chee\StudyController as Dcm4cheeStudyController;
use App\Models\Dcm4chee\Study;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use App\Models\General\Paciente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class TeleRadiologiaController extends Controller
{
    public function __construct()
    {
        // Este controlador se usa principalmente desde rutas /api (Sanctum SPA o token).
        // El webhook `recibirLectura` está protegido por HMAC en ruta (sin auth).
        $this->middleware('auth:sanctum')->except('recibirLectura');
        $this->middleware('permission:tele.ragiologia.index|tele.ragiologia.enviar|tele.ragiologia.transcribir|estudio.asignar|estudio.transcribir,web')
            ->except('recibirLectura');
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
     * @return \Illuminate\Http\JsonResponse Respuesta de la API externa o error.
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

            // Validar que la orden exista
            if (! $orden) {
                return response()->json(['message' => 'No se recibieron datos de la orden.'], 400);
            }

            $url = env('TELERADIOLOGIA_ORDEN_URL', 'https://ris.tlimagen.com/RISServices/integracion/salud_tolima/ordenes');

            // Realizar la petición a la API externa
            $response = Http::timeout((int) env('TELERADIOLOGIA_TIMEOUT', 30))
                ->retry((int) env('TELERADIOLOGIA_RETRIES', 1), 200)
                ->post($url, $orden);

            // Retornar la respuesta de la API externa al frontend
            if ($response->successful()) {
                $json = $response->json();

                // Normalizar "confirmation" para el frontend
                $confirmation = null;
                if (is_array($json)) {
                    $confirmation = $json['confirmation'] ?? $json['confirmation_id'] ?? $json['id'] ?? null;
                    if ($confirmation === null && isset($json['data']) && is_array($json['data'])) {
                        $confirmation = $json['data']['confirmation'] ?? $json['data']['id'] ?? null;
                    }
                }

                return response()->json([
                    'message' => $json['message'] ?? 'Orden enviada correctamente.',
                    'confirmation' => $confirmation,
                    'data' => $json,
                ], $response->status() === 201 ? 201 : 200);
            } else {
                return response()->json([
                    'message' => 'Error en la API externa',
                    'details' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
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
     * @return \Illuminate\Http\JsonResponse Respuesta de confirmación.
     */
    public function recibirLectura(Request $request)
    {
        $rules = [
            'study_pk' => 'required|integer',
            'study_iuid' => 'required|string',
            'fec_estudio' => 'required|date',
            'accession_no' => 'required|string',
            'study_desc' => 'required|string',
            'mods_in_study' => 'required|string',
            'sexo' => 'required|string|min:1',
            'paciente' => 'required|array',
            'paciente.num_docu' => 'required|string',
            'paciente.nom_pacien' => 'required|string',
            'paciente.fec_naci' => 'nullable|date',
            'priori' => 'required|array',
            'priori.id' => 'required|integer',
            'priori.nom_priori' => 'required|string',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'fechor_lectura' => 'required|date',
            'lectura' => 'required|string',
            'medico' => 'required|array',
            'medico.reg_medico' => 'nullable|string',
            'medico.num_docu' => 'required|string',
            'medico.name' => 'required|string',
            'medico.firma_medico' => 'nullable|string',
            'tipo_resultado' => 'nullable|integer|in:1,2,3', // 1 Norma, 2 Patologico, 3 Critico
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 400);
        }

        try {
            $d = $request->all();

            $medico = User::firstOrCreate(
                ['num_docu' => $d['medico']['num_docu']],
                [
                    'reg_med' => $d['medico']['reg_medico'] ?? null,
                    'name' => 'TL '.$d['medico']['name'],
                    'email' => $d['medico']['num_docu'].'@teleradiologia.com',
                    'password' => bcrypt('password'),
                ]
            );

            $sexo = strtoupper(substr((string) ($d['sexo'] ?? 'M'), 0, 1));
            if ($sexo !== 'M' && $sexo !== 'F') {
                $sexo = 'M';
            }

            $paciente = Paciente::firstOrCreate(
                ['num_docu' => $d['paciente']['num_docu']],
                [
                    'tipo_documento_id' => 1,
                    'nombres' => $d['paciente']['nom_pacien'],
                    'sexo' => $sexo,
                    'fec_naci' => $d['paciente']['fec_naci'] ?? null,
                ]
            );

            $estudio = Estudio::where('study_pk', $d['study_pk'])
                ->where('study_iuid', $d['study_iuid'])
                ->first();

            if (! $estudio) {
                return response()->json([
                    'message' => 'Estudio no encontrado.',
                    'error' => 'No se encontró con study_pk: '.$d['study_pk'].' y study_iuid: '.($d['study_iuid'] ?? ''),
                ], 404);
            }

            if (! $estudio->medico_id) {
                $estudio->update(['medico_id' => $medico->id]);
            }

            $estudioProducto = EstudioProducto::where('estudio_id', $estudio->id)
                ->where('cod_cups', $d['cod_cups'])
                ->first();

            if (! $estudioProducto) {
                return response()->json([
                    'message' => 'Producto del estudio no encontrado.',
                    'error' => 'No se encontró producto con cod_cups: '.$d['cod_cups'],
                ], 404);
            }

            $estudioProducto->update([
                'fechor_lectura' => $d['fechor_lectura'],
                'lectura' => $d['lectura'],
                'transcriptor_id' => $medico->id,
            ]);

            return response()->json([
                'message' => 'Lectura recibida y procesada correctamente.',
                'data' => [
                    'study_pk' => $d['study_pk'],
                    'accession_no' => $d['accession_no'],
                    'tipo_resultado' => (int) ($d['tipo_resultado'] ?? 1),
                    'medico_id' => $medico->id,
                    'paciente_id' => $paciente->id,
                    'estudio_id' => $estudio->id,
                    'estudio_producto_id' => $estudioProducto->id,
                    'procesado_en' => now()->toISOString(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno al procesar la lectura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
