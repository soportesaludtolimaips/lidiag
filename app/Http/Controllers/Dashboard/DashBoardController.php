<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    public function dashboard(Request $request)
    {
        $estudiosAsignados = Estudio::count();
        $estudiosLeidos = EstudioProducto::whereNotNull('fechor_trascrito')
            ->where('activo', '=', 1)
            ->count();
        $estudiosPorTranscribir = EstudioProducto::whereNull('fechor_trascrito')
            ->where('activo', '=', 1)
            ->count();
        $estudiosMasSolicitados = Estudio::select(
            DB::raw('count(mods_in_study) as total'),
            'mods_in_study'
        )->groupBy('mods_in_study')
            ->orderBy('mods_in_study', 'ASC')
            ->get();

        return response()->json(['estudiosAsignados' => $estudiosAsignados, 'estudiosLeidos' => $estudiosLeidos, 'estudiosPorTranscribir' => $estudiosPorTranscribir, 'estudiosMasSolicitados' => $estudiosMasSolicitados]);
    }
}
