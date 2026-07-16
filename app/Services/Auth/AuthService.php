<?php

namespace App\Services\Auth;

use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthService extends BaseService
{
    private const LOCKOUT_MAX_ATTEMPTS = 5;

    private const LOCKOUT_DECAY_MINUTES = 15;

    public function login(string $email, string $password, bool $wantsJson, ?string $ip = null): array
    {
        $key = 'login-lockout:'.md5(mb_strtolower($email).'|'.($ip ?? 'unknown'));

        if (Cache::has($key)) {
            $seconds = Cache::get($key) - now()->timestamp;
            $minutes = (int) ceil($seconds / 60);
            $msg = "Demasiados intentos. Espere {$minutes} minuto(s).";

            if ($wantsJson) {
                return $this->error($msg, 429);
            }

            return $this->error($msg, 302, [
                'redirect' => route('login'),
                'errors' => ['email' => $msg],
                'old' => ['email' => $email],
            ]);
        }

        $res = Auth::attempt(['email' => $email, 'password' => $password, 'estado' => 1]);

        if (! $res) {
            $attempts = (int) Cache::get("{$key}:attempts", 0) + 1;
            Cache::put("{$key}:attempts", $attempts, now()->addMinutes(self::LOCKOUT_DECAY_MINUTES));

            if ($attempts >= self::LOCKOUT_MAX_ATTEMPTS) {
                Cache::put($key, now()->addMinutes(self::LOCKOUT_DECAY_MINUTES)->timestamp, self::LOCKOUT_DECAY_MINUTES * 60);

                Log::warning('LOGIN_LOCKOUT', [
                    'email' => $email,
                    'ip' => $ip ?? 'unknown',
                    'attempts' => $attempts,
                    'lockout_minutes' => self::LOCKOUT_DECAY_MINUTES,
                ]);
            }

            if ($wantsJson) {
                return $this->error('Credenciales inválidas', 401);
            }

            return $this->error('Credenciales inválidas', 302, [
                'redirect' => route('login'),
                'errors' => ['email' => 'Credenciales inválidas'],
                'old' => ['email' => $email],
            ]);
        }

        Cache::forget($key);
        Cache::forget("{$key}:attempts");

        $this->detectPreviousSession();

        $redirectTo = route('dashboard');

        if ($wantsJson) {
            return $this->success([
                'ok' => true,
                'redirect' => $redirectTo,
            ]);
        }

        return $this->success([
            'redirect' => $redirectTo,
        ]);
    }

    public function logout($request): array
    {
        Log::info('SESION_LOGOUT', [
            'user_id' => Auth::id(),
            'session_id' => $request->session()->getId(),
            'logout_at' => now()->toDateTimeString(),
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Sesión cerrada correctamente', 204);
    }

    public function changePassword($user, string $currentPassword, string $newPassword): array
    {
        if (! Hash::check($currentPassword, $user->password)) {
            return $this->error('La contraseña actual no es correcta', 422);
        }

        $user->update([
            'password' => bcrypt($newPassword),
            'password_changed_at' => now(),
        ]);

        return $this->success(null, 'Contraseña actualizada correctamente');
    }

    private function detectPreviousSession(): void
    {
        try {
            $sesionAnterior = DB::table('sessions')
                ->where('user_id', Auth::id())
                ->where('id', '!=', session()->getId())
                ->first();

            if ($sesionAnterior) {
                Log::warning('SESION_ABRUPTA', [
                    'user_id' => Auth::id(),
                    'sesion_anterior_id' => $sesionAnterior->id,
                    'ultima_actividad' => $sesionAnterior->last_activity
                        ? date('Y-m-d H:i:s', $sesionAnterior->last_activity)
                        : null,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('SESION_ABRUPTA_ERROR', ['error' => $e->getMessage()]);
        }
    }
}
