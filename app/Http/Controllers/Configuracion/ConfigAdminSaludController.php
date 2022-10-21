<?php

namespace App\Http\Controllers\Configuracion;

use App\Models\Configuracion\ConfigAdminSalud;
use Illuminate\Http\Request;

class ConfigAdminSaludController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admin_salud = ConfigAdminSalud::all();
        return response()->json($admin_salud);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Configuracion\ConfigAdminSalud  $configAdminSalud
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ConfigAdminSalud $configAdminSalud)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Configuracion\ConfigAdminSalud  $configAdminSalud
     * @return \Illuminate\Http\Response
     */
    public function destroy(ConfigAdminSalud $configAdminSalud)
    {
        //
    }
}
