<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioAudio;
use Illuminate\Http\Request;

class EstudioAudioController extends Controller
{
    public function uploadAudio(Request $request)
    {
        if ($request->hasFile('audio')) {

            $audioFile = $request->file('audio');
            $nombreArchivo = 'audio_' . time() . '.' . $audioFile->getClientOriginalExtension();
            if ($audioFile->getSize() > 50000) {

                $audioFilePath = $audioFile->storeAs('audios', $nombreArchivo);

                $estudioAudio = new EstudioAudio();
                $estudioAudio->estudio_id = $request->estudio_id;
                $estudioAudio->estudio_producto_id = $request->estudio_producto_id;
                $estudioAudio->audio = $audioFilePath;
                $estudioAudio->save();

                return response()->json(['success' => true, 'message' => 'Audio subido con éxito'], 200);
            } else {
                return response()->json(['success' => false, 'message' => 'El audio debe tener contenido'], 400);
            }
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
