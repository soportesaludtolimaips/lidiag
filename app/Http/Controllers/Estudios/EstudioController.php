<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Http\Requests\Estudios\StoreEstudioRequest;
use App\Services\Estudios\EstudioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstudioController extends Controller
{
    public function __construct(
        private readonly EstudioService $estudioService,
    ) {}

    public function index(): JsonResponse
    {
        $result = $this->estudioService->all();

        return response()->json($result, $result['status']);
    }

    public function store(StoreEstudioRequest $request): JsonResponse
    {
        $result = $this->estudioService->create(
            $request->decoded(),
            $request->file('archivo1'),
            $request->file('archivo2'),
            $request->file('archivo3'),
        );

        return response()->json($result, $result['status']);
    }

    public function listarPendientesMedico(Request $request): JsonResponse
    {
        $request->validate([
            'id' => 'required|integer|exists:users,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
        ]);

        $result = $this->estudioService->listarPendientesMedico(
            $request->integer('id'),
            $request->integer('sede_id'),
        );

        return response()->json($result, $result['status']);
    }

    public function leerEstudio(Request $request): JsonResponse
    {
        $request->validate([
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'lectura' => 'required|string|min:3',
        ], ['lectura.required' => 'Debe ingresar la lectura.']);

        $result = $this->estudioService->leerEstudio(
            $request->integer('estudio_producto_id'),
            $request->input('lectura'),
        );

        return response()->json($result, $result['status']);
    }

    public function marcarEstudioVisto(int $id): JsonResponse
    {
        $result = $this->estudioService->marcarVisto($id);

        return response()->json($result, $result['status']);
    }

    public function listarDiagnosticoPorEstudio(Request $request): JsonResponse
    {
        $result = $this->estudioService->listarDiagnosticosPorEstudio(
            $request->integer('id')
        );

        return response()->json($result, $result['status']);
    }

    public function listarPendientesTrascribir(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_fech_ini' => 'nullable|date',
            'bus_fecha_fin' => 'nullable|date',
            'bus_nom_num_docu' => 'nullable|string|max:255',
            'quien_asigno_id' => 'nullable|integer|exists:users,id',
            'medico_id' => 'nullable|integer|exists:users,id',
        ], [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ]);

        $result = $this->estudioService->listarPendientesTranscribir($request->all());

        return response()->json($result, $result['status']);
    }

    public function listarEstudiosAsignados(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'bus_fech_ini' => 'nullable|date',
            'bus_fecha_fin' => 'nullable|date',
            'bus_nom_num_docu' => 'nullable|string|max:255',
            'quien_asigno_id' => 'nullable|integer|exists:users,id',
            'medico_id' => 'nullable|integer|exists:users,id',
        ], [
            'sede_id.required' => 'La sede es obligatoria.',
            'sede_id.exists' => 'La sede seleccionada no existe.',
        ]);

        $result = $this->estudioService->listarEstudiosAsignados($request->all());

        return response()->json($result, $result['status']);
    }

    public function guardarTranscripcion(Request $request): JsonResponse
    {
        $data = $request->validate([
            'estudio_producto_id' => 'required|integer|exists:estudios_productos,id',
            'id_estudio' => 'nullable|integer',
            'lectura' => 'required|string|min:3',
            'num_docu' => 'required|string|max:50',
            'nom_pacien' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'tel' => 'nullable|string|max:50',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'cod_cups' => 'required|string|max:50',
            'nom_produc' => 'required|string|max:255',
            'medico_id' => 'nullable|integer',
            'fec_naci' => 'nullable|string|max:50',
            'nom_sede' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'fechor_lectura' => 'nullable|date',
        ], ['lectura.required' => 'Debe ingresar la lectura.']);

        $data['fechor_lectura'] = $request->input('fechor_lectura', $request->input('fec_lectura'));
        $data['fechor_trascrito'] = $request->input('fechor_trascrito', Carbon::now()->format('Y-m-d H:i:s'));

        $result = $this->estudioService->guardarTranscripcion($data);

        return response()->json($result, $result['status']);
    }

    public function penditesPorLeerTranscribir(Request $request): JsonResponse
    {
        $result = $this->estudioService->pendientesPorLeerTranscribir();

        return response()->json($result, $result['status']);
    }

    public function buscarEstudioPorNumDocu(Request $request): JsonResponse
    {
        $result = $this->estudioService->buscarPorNumDocu($request->input('numDocu'));

        return response()->json($result, $result['status']);
    }

    public function anularAsignacion(Request $request): JsonResponse
    {
        $result = $this->estudioService->anularAsignacion($request->integer('id'));

        return response()->json($result, $result['status']);
    }

    public function descargarPdfLectura(string $nomArchivo)
    {
        $result = $this->estudioService->descargarPdfLectura($nomArchivo);

        if (! $result['success']) {
            return response()->json($result, $result['status']);
        }

        return response()->file($result['data']['path']);
    }

    public function notificarEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id_estudio' => 'required|integer',
            'sede_id' => 'required|integer',
            'email' => 'required|email',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'lectura' => 'required|string',
            'estudio_producto_id' => 'required|integer',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'fec_naci' => 'nullable|string|max:50',
            'nom_sede' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'fechor_lectura' => 'nullable|date',
        ]);

        $result = $this->estudioService->notificarEmail($data);

        return response()->json($result, $result['status']);
    }

    public function enviarPortalPacientes(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sede_id' => 'required|integer',
            'num_docu' => 'required|string',
            'nom_pacien' => 'required|string',
            'lectura' => 'required|string',
            'estudio_producto_id' => 'required|integer',
            'cod_cups' => 'required|string',
            'nom_produc' => 'required|string',
            'fec_naci' => 'nullable|string|max:50',
            'nom_sede' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:500',
            'study_iuid' => 'nullable|string|max:500',
            'tap_oviyam' => 'nullable|string|max:500',
            'fechor_lectura' => 'nullable|date',
        ]);

        $result = $this->estudioService->enviarPortalPacientes($data);

        return response()->json($result, $result['status']);
    }
}
