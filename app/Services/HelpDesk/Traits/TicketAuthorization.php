<?php

namespace App\Services\HelpDesk\Traits;

use App\Models\HelpDesk\HelpDeskTickets;
use App\Models\User;

trait TicketAuthorization
{
    /**
     * Verifica que el usuario tenga permiso para ver un ticket o aborta con error 403.
     *
     * @param  HelpDeskTickets  $ticket  ticket que se intenta visualizar.
     * @param  User  $user  usuario autenticado que solicita el acceso.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-15
     */
    public function assertCanSeeTicket(HelpDeskTickets $ticket, User $user): void
    {
        $isOwner = (int) $ticket->quien_registra_id === (int) $user->id;
        $isAssigned = (int) $ticket->quien_atiende_id === (int) $user->id;
        $isManager = $user->hasRole(['Administrador', 'Soporte Manager']);

        if (! $isOwner && ! $isAssigned && ! $isManager) {
            abort(403, 'Forbidden');
        }
    }

    /**
     * Sanea una cadena HTML permitiendo solo etiquetas seguras y eliminando atributos y esquemas peligrosos.
     *
     * @param  string  $html  contenido HTML crudo a sanear.
     *
     * @returns string cadena HTML saneada con etiquetas permitidas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-15
     */
    public function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*([\'"])\s*data:[^\'"]*\1/i', 'href="#"', $clean) ?? $clean;

        return $clean;
    }
}
