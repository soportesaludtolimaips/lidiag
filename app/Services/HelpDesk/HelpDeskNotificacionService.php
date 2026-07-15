<?php

namespace App\Services\HelpDesk;

use App\Models\User;
use App\Services\BaseService;

class HelpDeskNotificacionService extends BaseService
{
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

    public function markRead(User $user, string $id): array
    {
        $n = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return $this->success(['ok' => true]);
    }

    public function markAllRead(User $user): array
    {
        $user->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(['ok' => true]);
    }
}
