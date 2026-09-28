<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class PasswordChangeController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function showForm()
    {
        return view('auth.passwords.cambiar');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.required' => 'La nueva contraseña es obligatoria.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
        ]);

        $result = $this->authService->changePassword(
            $request->user(),
            $request->current_password,
            $request->password
        );

        if (! $result['success']) {
            return back()->withErrors(['current_password' => $result['message']]);
        }

        $intended = session()->pull('url.intended');
        $intendedPath = $intended !== null ? trim((string) parse_url($intended, PHP_URL_PATH), '/') : null;
        $formPath = trim((string) parse_url(route('password.cambiar'), PHP_URL_PATH), '/');

        if ($intendedPath === null || $intendedPath === '' || $intendedPath === $formPath) {
            $intended = null;
        }

        return redirect()->to($intended ?: route('dashboard'))->with('success', 'Contraseña actualizada correctamente.');
    }
}
