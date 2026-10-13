<?php

namespace App\Services\Auth;

use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;

class PasswordChangeService extends BaseService
{
    /**
     * Cambia la contraseña del usuario verificando primero la contraseña actual.
     *
     * @param  object  $user  Usuario cuya contraseña será cambiada
     * @param  string  $currentPassword  Contraseña actual para verificación
     * @param  string  $newPassword  Nueva contraseña a almacenar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function update(object $user, string $currentPassword, string $newPassword): array
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return $this->error('La contraseña actual no es correcta.', 422);
        }

        $user->update([
            'password' => bcrypt($newPassword),
            'password_changed_at' => now(),
        ]);

        return $this->success(null, 'Contraseña actualizada correctamente.');
    }
}
