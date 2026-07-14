<?php

namespace App\Services\HelpDesk;

use App\Models\User;
use App\Services\BaseService;

class HelpDeskUsuarioService extends BaseService
{
    public function asignables(): array
    {
        $usuarios = User::query()
            ->select(['id', 'name', 'email'])
            ->where('estado', 1)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Administrador', 'Soporte Manager', 'Soporte Tecnico']);
            })
            ->with(['roles:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'roles' => $u->roles->pluck('name')->values(),
                ];
            });

        return $this->success($usuarios);
    }
}
