<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioAudio;
use Illuminate\Http\Request;

class AudiosEstudioController extends Controller
{
    public function listarAudiosPorEstudio(Request $request)
    {

        $estudioId = $request->estudio_id;
        $audios = EstudioAudio::where('estudio_id', '=', $estudioId)
            ->get();

        return response()->json($audios);
    }
}
