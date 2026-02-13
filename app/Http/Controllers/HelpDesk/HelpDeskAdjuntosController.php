<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\HelpDesk\HelpDeskAdjunto;
use App\Models\HelpDesk\HelpDeskTicketHistorial;
use App\Models\HelpDesk\HelpDeskTickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class HelpDeskAdjuntosController extends Controller
{
    public function upload(HelpDeskTickets $ticket, Request $request)
    {
        $this->assertCanSeeTicket($ticket, $request);

        $validator = Validator::make($request->all(), [
            'comentario' => 'nullable|string',
            'files' => 'required',
            'files.*' => 'file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $comentario = $request->filled('comentario') ? $this->sanitizeHtml($request->comentario) : null;

        $hist = HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'tipo' => 'ADJUNTO_AGREGADO',
            'comentario' => $comentario,
        ]);

        $saved = [];
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('files_help_desk');
        foreach ((array) $request->file('files') as $file) {
            $original = $file->getClientOriginalName();
            $stored = \App\Helpers\generateUniqueFilename($original);

            $disk->putFileAs("tickets/{$ticket->id}", $file, $stored);
            $path = "tickets/{$ticket->id}/{$stored}";

            $adj = HelpDeskAdjunto::create([
                'historial_id' => $hist->id,
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'original_name' => $original,
                'stored_name' => $stored,
                'path' => $path,
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);

            $saved[] = [
                'id' => $adj->id,
                'original_name' => $adj->original_name,
                'created_at' => $adj->created_at,
            ];
        }

        return response()->json([
            'ok' => true,
            'historial_id' => $hist->id,
            'adjuntos' => $saved,
        ], 201);
    }

    public function download(HelpDeskAdjunto $adjunto, Request $request)
    {
        $ticket = HelpDeskTickets::query()->findOrFail($adjunto->ticket_id);
        $this->assertCanSeeTicket($ticket, $request);

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('files_help_desk');

        if (! $disk->exists($adjunto->path)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        return $disk->download($adjunto->path, $adjunto->original_name);
    }

    public function destroy(HelpDeskAdjunto $adjunto, Request $request)
    {
        $ticket = HelpDeskTickets::query()->findOrFail($adjunto->ticket_id);
        $this->assertCanSeeTicket($ticket, $request);

        // Solo Admin/Manager (vía permiso en rutas)
        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'tipo' => 'ADJUNTO_ELIMINADO',
            'comentario' => 'Adjunto eliminado: '.$this->sanitizeHtml($adjunto->original_name),
        ]);

        // Borrar archivo físico (best-effort)
        try {
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('files_help_desk');
            $disk->delete($adjunto->path);
        } catch (\Throwable $e) {
            // ignorar
        }

        $adjunto->delete();

        return response()->json(['ok' => true]);
    }

    private function assertCanSeeTicket(HelpDeskTickets $ticket, Request $request): void
    {
        $user = $request->user();
        $isOwner = (int) $ticket->quien_registra_id === (int) $user->id;
        $isAssigned = (int) $ticket->quien_atiende_id === (int) $user->id;
        $isManager = $user->hasRole(['Administrador', 'Soporte Manager']);

        if (! $isOwner && ! $isAssigned && ! $isManager) {
            abort(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    private function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
        $clean = strip_tags($html, $allowed);

        // Remueve on* handlers
        $clean = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean) ?? $clean;
        // Evita javascript: en href
        $clean = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $clean) ?? $clean;

        return $clean;
    }
}
