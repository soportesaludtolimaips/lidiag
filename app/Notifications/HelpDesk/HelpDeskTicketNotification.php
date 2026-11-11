<?php

namespace App\Notifications\HelpDesk;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HelpDeskTicketNotification extends Notification
{
    use Queueable;

    /**
     * Constructor de la notificación de ticket de help desk.
     *
     * @param  int  $ticketId  ID del ticket notificado.
     * @param  string|null  $folio  Folio del ticket.
     * @param  string  $titulo  Título del ticket.
     * @param  string  $evento  Evento que dispara la notificación.
     * @param  string  $mensaje  Mensaje mostrado al usuario.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct(
        public readonly int $ticketId,
        public readonly ?string $folio,
        public readonly string $titulo,
        public readonly string $evento,
        public readonly string $mensaje,
    ) {}

    /**
     * Define los canales por los que se envía la notificación.
     *
     * @param  object  $notifiable  Modelo que recibirá la notificación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function via(object $notifiable): array
    {
        // Notificación interna (database) para dropdown del header
        return ['database'];
    }

    /**
     * Convierte la notificación en un array para el dropdown del header.
     *
     * @param  object  $notifiable  Modelo que recibirá la notificación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticketId,
            'folio' => $this->folio,
            'titulo' => $this->titulo,
            'evento' => $this->evento,
            'mensaje' => $this->mensaje,
            'url' => '/helpdesk-tickets/'.$this->ticketId,
        ];
    }
}
