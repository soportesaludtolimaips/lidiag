<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Autenticacion\loginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(loginRequest $request)
    {
        $email = $request->email;
        $password = $request->password;

        $res = Auth::attempt(['email' => $email, 'password' => $password, 'estado' => 1]);

        if ($res) {
            return redirect()->route('dashboard');
        } else {
            return redirect()->route('login')->withErrors(['Invalid credentials']);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return response()->json(['code' => 204]);
    }

    public function showLoginForm()
    {
        return view('auth/login');
    }
}
