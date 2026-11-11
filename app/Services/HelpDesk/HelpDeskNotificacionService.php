<?php

namespace App\Services\HelpDesk;

use App\Models\User;
use App\Services\BaseService;

class HelpDeskNotificacionService extends BaseService
{
    /**
     * Obtiene la lista paginada de notificaciones de un usuario con su conteo de no leídas.
     *
     * @param  User  $user  usuario dueño de las notificaciones.
     * @param  int  $limit  cantidad de registros por página (entre 1 y 100).
     * @param  int  $page  número de página actual.
     * @param  bool  $unreadOnly  indica si solo se listan las notificaciones no leídas.
     *
     * @returns array respuesta con los items, conteo de no leídas y metadatos de paginación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function list(User $user, int $limit, int $page = 1, bool $unreadOnly = false): array
    {
        $limit = max(1, min(100, $limit));
        $page = max(1, $page);

        $query = $user->notifications()->orderByDesc('created_at');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = $paginator->getCollection()->map(function ($n) {
            return [
                'id' => $n->id,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at,
                'data' => $n->data,
            ];
        });

        $unreadCount = $user->unreadNotifications()->count();

        return $this->success([
            'items' => $items,
            'unread_count' => $unreadCount,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Marca como leída una notificación concreta perteneciente al usuario indicado.
     *
     * @param  User  $user  usuario dueño de la notificación.
     * @param  string  $id  identificador de la notificación a marcar como leída.
     *
     * @returns array respuesta de éxito con la confirmación de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function markRead(User $user, string $id): array
    {
        $n = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return $this->success(['ok' => true]);
    }

    /**
     * Marca como leídas todas las notificaciones pendientes del usuario indicado.
     *
     * @param  User  $user  usuario dueño de las notificaciones.
     *
     * @returns array respuesta de éxito con la confirmación de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function markAllRead(User $user): array
    {
        $user->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(['ok' => true]);
    }
}
