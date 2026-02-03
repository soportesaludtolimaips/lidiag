<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Dcm4chee\StudyController as Dcm4cheeStudyController;
use App\Models\Dcm4chee\Study;
use App\Models\User;
use App\Models\General\Paciente;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;

class TeleRadiologiaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:estudio.asignar|estudio.transcribir,web');
    }

    /**
     * Lista estudios desde PACS (proxy a DCM4CHEE).
     *
     * Nota: este endpoint existe para el módulo de Tele-radiología.
     */
    public function listarEstudios(Request $request)
    {
        // Reutilizar el controlador de DCM4CHEE para evitar duplicar lógica
        return app(Dcm4cheeStudyController::class)->listarEstudios($request, new Study());
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
     * @param Request $request Datos de la orden.
     * @return \Illuminate\Http\JsonResponse Respuesta de la API externa o error.
     */
    public function enviarOrdenTeleRadiologia(Request $request)
    {
        return $request;
        try {
            $validator = Validator::make($request->all(), [
                'orden' => 'required|array',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $orden = $request->input('orden');

            // Validar que la orden exista
            if (!$orden) {
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
                    'details' => $response->body()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno en el servidor proxy',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recibe y procesa lecturas de estudios realizadas por tele radiología.
     *
     * @param Request $request Datos de la lectura.
     * @return \Illuminate\Http\JsonResponse Respuesta de confirmación.
     */
    public function recibirLectura(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'study_pk' => 'required|integer',
                'study_iuid' => 'required|string',
                'fec_estudio' => 'required|date',
                'accession_no' => 'required|string',
                'study_desc' => 'required|string',
                'mods_in_study' => 'required|string',
                'sexo' => 'required|string|in:M,F',
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
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Datos inválidos.',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $request->all();

            // 1. Verificar/Crear médico
            try {
                $medico = User::where('num_docu', $data['medico']['num_docu'])->first();
                if (!$medico) {
                    $medico = User::create([
                        'num_docu' => $data['medico']['num_docu'],
                        'reg_med' => $data['medico']['reg_medico'] ?? null,
                        'name' => 'TL ' . $data['medico']['name'],
                        'email' => $data['medico']['num_docu'] . '@teleradiologia.com', // Email temporal
                        'password' => bcrypt('password'), // Contraseña temporal
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al procesar el médico.',
                    'error' => 'No se pudo encontrar o crear el médico: ' . $e->getMessage()
                ], 500);
            }

            // 2. Verificar/Crear paciente
            try {
                $paciente = Paciente::where('num_docu', $data['paciente']['num_docu'])->first();
                if (!$paciente) {
                    $paciente = Paciente::create([
                        'tipo_documento_id' => 1, // Asumir CC
                        'num_docu' => $data['paciente']['num_docu'],
                        'nombres' => $data['paciente']['nom_pacien'],
                        'sexo' => $data['sexo'],
                        'fec_naci' => $data['paciente']['fec_naci'],
                    ]);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al procesar el paciente.',
                    'error' => 'No se pudo encontrar o crear el paciente: ' . $e->getMessage()
                ], 500);
            }

            // 3. Encontrar el estudio
            try {
                $estudio = Estudio::where('study_pk', $data['study_pk'])
                    ->orWhere('accession_no', $data['accession_no'])
                    ->first();

                if (!$estudio) {
                    return response()->json([
                        'message' => 'Estudio no encontrado.',
                        'error' => 'No se encontró un estudio con study_pk: ' . $data['study_pk'] . ' o accession_no: ' . $data['accession_no']
                    ], 404);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al buscar el estudio.',
                    'error' => $e->getMessage()
                ], 500);
            }

            // Actualizar estudio con médico si no lo tiene
            try {
                if (!$estudio->medico_id) {
                    $estudio->update(['medico_id' => $medico->id]);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al actualizar el estudio.',
                    'error' => 'No se pudo asignar el médico al estudio: ' . $e->getMessage()
                ], 500);
            }

            // 4. Actualizar estudios_productos
            try {
                $estudioProducto = EstudioProducto::where('estudio_id', $estudio->id)
                    ->where('cod_cups', $data['cod_cups'])
                    ->first();

                if (!$estudioProducto) {
                    return response()->json([
                        'message' => 'Producto del estudio no encontrado.',
                        'error' => 'No se encontró un producto con cod_cups: ' . $data['cod_cups'] . ' para el estudio ID: ' . $estudio->id
                    ], 404);
                }

                $estudioProducto->update([
                    'fechor_lectura' => $data['fechor_lectura'],
                    'lectura' => $data['lectura'],
                    //'transcriptor_id' => $medico->id, // Asumir que el médico es el transcriptor
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Error al actualizar el producto del estudio.',
                    'error' => $e->getMessage()
                ], 500);
            }

            return response()->json([
                'message' => 'Lectura recibida y procesada correctamente.',
                'data' => [
                    'study_pk' => $data['study_pk'],
                    'accession_no' => $data['accession_no'],
                    'medico_id' => $medico->id,
                    'paciente_id' => $paciente->id,
                    'estudio_id' => $estudio->id,
                    'estudio_producto_id' => $estudioProducto->id,
                    'procesado_en' => now()->toISOString(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno al procesar la lectura.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
