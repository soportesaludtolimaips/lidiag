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
    /**
     * Construye el controlador inyectando el servicio de adjuntos del help desk.
     *
     * @param  HelpDeskAdjuntoService  $adjuntoService  servicio encargado de la lógica de adjuntos.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function __construct(
        private readonly HelpDeskAdjuntoService $adjuntoService,
    ) {}

    /**
     * Sube uno o varios archivos adjuntos asociados a un ticket de help desk.
     *
     * @param  HelpDeskTickets  $ticket  ticket al que se adjuntan los archivos.
     * @param  HelpDeskAdjuntoUploadRequest  $request  petición validada con los archivos y comentario.
     *
     * @returns JsonResponse respuesta JSON con los adjuntos creados y el historial generado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function upload(HelpDeskTickets $ticket, HelpDeskAdjuntoUploadRequest $request)
    {
        $result = $this->adjuntoService->upload($ticket, $request->validated(), $request->user());

        return response()->json($result, $result["status"]);
    }

    /**
     * Descarga un archivo adjunto previa validación de permisos sobre el ticket.
     *
     * @param  HelpDeskAdjunto  $adjunto  adjunto que se desea descargar.
     * @param  Request  $request  petición HTTP del usuario autenticado.
     *
     * @returns mixed respuesta de descarga del archivo o JSON de error 404 si no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function download(HelpDeskAdjunto $adjunto, Request $request)
    {
        $response = $this->adjuntoService->download($adjunto, $request->user());
        if ($response === null) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        return $response;
    }

    /**
     * Elimina un adjunto del ticket y su archivo físico en el almacenamiento.
     *
     * @param  HelpDeskAdjunto  $adjunto  adjunto que se desea eliminar.
     * @param  Request  $request  petición HTTP del usuario autenticado.
     *
     * @returns JsonResponse respuesta JSON con la confirmación de la eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function destroy(HelpDeskAdjunto $adjunto, Request $request): JsonResponse
    {
        $result = $this->adjuntoService->delete($adjunto, $request->user());

        return response()->json(['ok' => true], $result['status']);
    }
}
