<?php

namespace App\Notifications\HelpDesk;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HelpDeskTicketNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $ticketId,
        public readonly ?string $folio,
        public readonly string $titulo,
        public readonly string $evento,
        public readonly string $mensaje,
        public readonly ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        // Notificación interna (database) para dropdown del header
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'folio' => $this->folio,
            'titulo' => $this->titulo,
            'evento' => $this->evento,
            'mensaje' => $this->mensaje,
            'url' => $this->url,
        ];
    }
}
