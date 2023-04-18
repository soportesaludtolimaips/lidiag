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
            return response()->json(['authUser' => Auth::user(), 'code' => 200]);
        } else {
            return response()->json(['code' => 401]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        return response()->json(['code' => 204]);
    }
}
