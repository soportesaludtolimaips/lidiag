<?php

namespace App\Notifications\Consentimientos;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación (canal database) que informa al médico cuántos consentimientos
 * tiene pendientes de firma, para recordarle que debe firmarlos.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-08-29
 */
class ConsentimientosPendientesNotification extends Notification
{
    use Queueable;

    /**
     * @param  int  $pendientes  Cantidad de consentimientos pendientes de firma.
     */
    public function __construct(
        private readonly int $pendientes,
    ) {}

    /**
     * Canales por los que se envía la notificación.
     *
     * @param  mixed  $notifiable  Destinatario.
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Representación para el canal database.
     *
     * @param  mixed  $notifiable  Destinatario.
     * @return array<string, mixed>
     */
    public function toDatabase($notifiable): array
    {
        return [
            'tipo' => 'consentimientos_pendientes',
            'pendientes' => $this->pendientes,
            'mensaje' => "Tiene {$this->pendientes} consentimiento(s) pendiente(s) de firma. Sus lecturas están bloqueadas hasta firmarlos.",
            'url' => '/consentimientos-medico',
        ];
    }
}
