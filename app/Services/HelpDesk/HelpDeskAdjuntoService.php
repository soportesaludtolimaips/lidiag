<?php

namespace App\Services\HelpDesk;

use App\Models\HelpDesk\HelpDeskAdjunto;
use App\Models\HelpDesk\HelpDeskTicketHistorial;
use App\Models\HelpDesk\HelpDeskTickets;
use App\Models\User;
use App\Services\BaseService;
use App\Services\HelpDesk\Traits\TicketAuthorization;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class HelpDeskAdjuntoService extends BaseService
{
    use TicketAuthorization;

    public function upload(HelpDeskTickets $ticket, array $data, User $user): array
    {
        $comentario = (! empty($data['comentario'])) ? $this->sanitizeHtml($data['comentario']) : null;

        $hist = HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'tipo' => 'ADJUNTO_AGREGADO',
            'comentario' => $comentario,
        ]);

        $saved = [];
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('files_help_desk');
        foreach ((array) $data['files'] as $file) {
            /** @var UploadedFile $file */
            $original = $file->getClientOriginalName();
            $stored = \App\Helpers\generateUniqueFilename($original);

            $disk->putFileAs("tickets/{$ticket->id}", $file, $stored);
            $path = "tickets/{$ticket->id}/{$stored}";

            $adj = HelpDeskAdjunto::create([
                'historial_id' => $hist->id,
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
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

        return $this->created([
            'ok' => true,
            'historial_id' => $hist->id,
            'adjuntos' => $saved,
        ]);
    }

    public function download(HelpDeskAdjunto $adjunto, User $user)
    {
        $ticket = HelpDeskTickets::query()->findOrFail($adjunto->ticket_id);
        $this->assertCanSeeTicket($ticket, $user);

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('files_help_desk');

        if (! $disk->exists($adjunto->path)) {
            return null;
        }

        return $disk->download($adjunto->path, $adjunto->original_name);
    }

    public function delete(HelpDeskAdjunto $adjunto, User $user): array
    {
        $ticket = HelpDeskTickets::query()->findOrFail($adjunto->ticket_id);
        $this->assertCanSeeTicket($ticket, $user);

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'tipo' => 'ADJUNTO_ELIMINADO',
            'comentario' => 'Adjunto eliminado: '.$this->sanitizeHtml($adjunto->original_name),
        ]);

        try {
            /** @var FilesystemAdapter $disk */
            $disk = Storage::disk('files_help_desk');
            $disk->delete($adjunto->path);
        } catch (\Throwable $e) {
            Log::warning('No se pudo eliminar archivo físico de adjunto', [
                'adjunto_id' => $adjunto->id,
                'path' => $adjunto->path,
            ]);
        }

        $adjunto->delete();

        return $this->deleted();
    }
}
