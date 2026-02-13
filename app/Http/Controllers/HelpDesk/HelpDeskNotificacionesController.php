<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class HelpDeskNotificacionesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $limit = (int) ($request->query('limit', 20));
        if ($limit <= 0) {
            $limit = 20;
        }
        $limit = min($limit, 100);

        $items = $user->notifications()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function ($n) {
                return [
                    'id' => $n->id,
                    'read_at' => $n->read_at,
                    'created_at' => $n->created_at,
                    'data' => $n->data,
                ];
            });

        $unreadCount = $user->unreadNotifications()->count();

        return response()->json([
            'items' => $items,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(string $id, Request $request)
    {
        $user = $request->user();
        $n = $user->notifications()->where('id', $id)->firstOrFail();
        $n->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
