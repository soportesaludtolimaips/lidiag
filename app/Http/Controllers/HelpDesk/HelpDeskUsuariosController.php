<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class HelpDeskUsuariosController extends Controller
{
    public function asignables(Request $request)
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

        return response()->json($usuarios);
    }
}
