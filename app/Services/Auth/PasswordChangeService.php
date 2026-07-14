<?php

namespace App\Services\Auth;

use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;

class PasswordChangeService extends BaseService
{
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
