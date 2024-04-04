<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioAudio;
use Illuminate\Http\Request;

class EstudioAudioController extends Controller
{
    public function storage(Request $request)
    {
        if ($request->hasFile('audio')) {

            $audioFile = $request->file('audio');
            $nombreArchivo = 'audio_' . time() . '.' . $audioFile->getClientOriginalExtension();

            $audioFilePath = $audioFile->storeAs('audios', $nombreArchivo);

            $estudioAudio = new EstudioAudio();
            $estudioAudio->estudio_id = $request->estudio_id;
            $estudioAudio->audio = $audioFilePath;
            $estudioAudio->save();

            return response()->json(['success' => true, 'message' => 'Audio subido con éxito']);
        }

        return response()->json(['success' => false, 'message' => 'No se proporcionó un archivo de audio'], 400);
    }


    public function listarAudiosPorEstudio(Request $request)
    {

        $estudioId = $request->estudio_id;
        $audios = EstudioAudio::where('estudio_id', '=', $estudioId)
            ->get();

        return response()->json($audios);
    }
}
