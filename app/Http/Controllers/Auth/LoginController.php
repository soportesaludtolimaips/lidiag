<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\loginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(loginRequest $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validated();
        $result = $this->authService->login(
            $credentials['email'],
            $credentials['password'],
            $request->expectsJson(),
            $request->ip(),
        );

        if ($result['success'] && ! ($result['data']['type'] ?? null)) {
            if ($request->expectsJson()) {
                return response()->json($result, $result["status"]);
            }

            return redirect()->to($result['data']['redirect']);
        }

        if ($request->expectsJson()) {
            return response()->json($result, $result["status"]);
        }

        return redirect()
            ->route('login')
            ->withErrors($result['errors'] ?? ['email' => $result['message']])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $result = $this->authService->logout($request);

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('login');
    }

    public function showLoginForm(): View
    {
        return view('auth/login');
    }
}
