<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Estudio\Estudio;
use App\Models\Estudio\EstudioProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashBoardController extends Controller
{
    /**
     * Obtiene estadísticas del dashboard según el perfil del usuario.
     *
     * Para Administrador:
     * - Total de estudios asignados
     * - Estudios leídos (transcritos)
     * - Estudios pendientes de transcribir
     * - Estudios más solicitados por modalidad
     *
     * Para Médico:
     * - Estudios asignados al médico
     * - Estudios leídos por el médico
     * - Estudios más solicitados del médico por modalidad
     *
     * @param Request $request Parámetros de filtrado (no utilizados actualmente).
     * @return \Illuminate\Http\JsonResponse Estadísticas del dashboard.
     */
    public function dashboard(Request $request)
    {
        $perfil = auth()->user()->roles()->first()->name;
        $idUser = auth()->user()->id;

        if ($perfil === 'Administrador') {
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
        } else {

            $estudiosAsignados = Estudio::join('estudios_productos', 'estudios_productos.estudio_id', 'estudios.id')
                ->where('estudios.medico_id', '=', $idUser)
                ->where('estudios_productos.activo', '=', 1)
                ->count();

            $estudiosLeidos = Estudio::join('estudios_productos', 'estudios_productos.estudio_id', 'estudios.id')
                ->where('estudios.medico_id', '=', $idUser)
                ->whereNotNull('estudios_productos.fechor_trascrito')
                ->where('estudios_productos.activo', '=', 1)
                ->count();

            $estudiosMasSolicitados = Estudio::select(
                DB::raw('count(mods_in_study) as total'),
                'mods_in_study'
            )
                ->where('medico_id', '=', $idUser)
                ->groupBy('mods_in_study')
                ->orderBy('mods_in_study', 'ASC')
                ->get();

            return response()->json(['estudiosAsignados' => $estudiosAsignados, 'estudiosLeidos' => $estudiosLeidos, 'estudiosMasSolicitados' => $estudiosMasSolicitados]);
        }
    }
}
