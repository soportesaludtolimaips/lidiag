<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TeleRadiologiaController extends Controller
{
    public function enviarOrdenTeleRadiologia(Request $request)
    {
        try {
            $orden = $request->input('orden');

            // Validar que la orden exista
            if (!$orden) {
                return response()->json(['message' => 'No se recibieron datos de la orden.'], 400);
            }

            // Realizar la petición a la API externa
            $response = Http::post('https://ris.tlimagen.com/RISServices/integracion/salud_tolima/ordenes', $orden);

            // Retornar la respuesta de la API externa al frontend
            if ($response->successful()) {
                return response()->json($response->json(), $response->status());
            } else {
                return response()->json([
                    'message' => 'Error en la API externa',
                    'details' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno en el servidor proxy',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
