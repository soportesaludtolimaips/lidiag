<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Http\Requests\Interfaces\EnviarOrdenTeleRadiologiaRequest;
use App\Services\Interfaces\TeleRadiologiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeleRadiologiaController extends Controller
{
    public function __construct(
        private readonly TeleRadiologiaService $teleRadiologiaService,
    ) {}

    public function listarEstudios(Request $request): JsonResponse
    {
        $result = $this->teleRadiologiaService->listarEstudios($request);

        return response()->json($result, $result['status']);
    }

    public function enviarEstudio(Request $request): JsonResponse
    {
        $result = $this->teleRadiologiaService->enviarEstudio();

        return response()->json($result, $result['status']);
    }

    public function enviarOrdenTeleRadiologia(EnviarOrdenTeleRadiologiaRequest $request): JsonResponse
    {
        $result = $this->teleRadiologiaService->enviarOrdenTeleRadiologia($request->input('orden'));

        return response()->json($result, $result['status']);
    }

    public function recibirLectura(Request $request): JsonResponse
    {
        $rawContent = $request->getContent();
        $utf8Content = mb_convert_encoding($rawContent, 'UTF-8', 'ISO-8859-1');
        $data = json_decode($utf8Content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'Error al decodificar el payload JSON.',
                'error' => json_last_error_msg(),
            ], 400);
        }

        $validator = Validator::make($data, [
            'study_iuid' => 'required|string',
            'cod_cups' => 'required|string',
            'paciente.num_docu' => 'required|string',
            'medico.num_docu' => 'required_without:adendum.medico.num_docu|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $result = $this->teleRadiologiaService->recibirLectura($data);

        return response()->json($result, $result['status']);
    }
}
