<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Http\Requests\HelpDesk\HelpDeskAdjuntoUploadRequest;
use App\Models\HelpDesk\HelpDeskAdjunto;
use App\Models\HelpDesk\HelpDeskTickets;
use App\Services\HelpDesk\HelpDeskAdjuntoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpDeskAdjuntosController extends Controller
{
    public function __construct(
        private readonly HelpDeskAdjuntoService $adjuntoService,
    ) {}

    public function upload(HelpDeskTickets $ticket, HelpDeskAdjuntoUploadRequest $request)
    {
        $result = $this->adjuntoService->upload($ticket, $request->validated(), $request->user());

        return response()->json($result, $result["status"]);
    }

    public function download(HelpDeskAdjunto $adjunto, Request $request)
    {
        $response = $this->adjuntoService->download($adjunto, $request->user());
        if ($response === null) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        return $response;
    }

    public function destroy(HelpDeskAdjunto $adjunto, Request $request): JsonResponse
    {
        $result = $this->adjuntoService->delete($adjunto, $request->user());

        return response()->json(['ok' => true], $result['status']);
    }
}
