<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Inyecta el middleware de autenticación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Muestra la vista principal del panel del usuario autenticado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function index(): View
    {
        return view('home');
    }
}
