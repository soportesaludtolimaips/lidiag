<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Jobs\SendEcografiaProductoToSahi;
use App\Models\Ecografias\EcografiaProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SAHIEcografiasEnviarController extends Controller
{
    public function estadoPorEcografiaProducto(int $ecografia_producto_id)
    {
        $ecoProd = EcografiaProducto::select([
            'id',
            'estado_envio_sahi',
            'fechor_envio_sahi',
            'error_envio_sahi',
            'sahi_esquema_149',
            'sahi_esquema_150',
        ])->find($ecografia_producto_id);

        if (!$ecoProd) {
            return response()->json([
                'message' => 'No se encontró el producto de ecografía en LIDiag.',
                'status' => 404,
            ], 404);
        }

        return response()->json([
            'message' => 'Estado SAHI consultado.',
            'data' => [
                'ecografia_producto_id' => $ecoProd->id,
                'estado_envio_sahi' => $ecoProd->estado_envio_sahi,
                'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
                'error_envio_sahi' => $ecoProd->error_envio_sahi,
                'sahi_esquema_149' => $ecoProd->sahi_esquema_149,
                'sahi_esquema_150' => $ecoProd->sahi_esquema_150,
            ],
        ], 200);
    }

    /**
     * Envía la lectura de una ecografía a SAHI a partir de `ecografia_producto_id`.
     *
     * Flujo (basado en `Documentos/ecografias/enviar_a_sahi.php`):
     * - Resuelve en LIDiag: atencion_id, medico (users.interface_id), lectura/opinion y producto_id.
     * - En SAHI:
     *   1) Valida médico en SAHI (ASI_USUA + hcePersonal)
     *   2) Obtiene IdUbicacionActual de admAtencion
     *   3) Genera consecutivos (sisTabla / HceEsquema 149)
     *   4) Inserta: hceEsquemaEncabeza(149), hceEsquemasdeAte, hceEsquemaDetMemo, hceEsquemaDetProc(médico)
     *   5) Genera segundo encabezado y agrega procedimiento (hceEsquemaEncabeza(150) + hceEsquemaDetProc(producto))
     */
    public function enviarPorEcografiaProducto(Request $request)
    {
        $data = $request->validate([
            'ecografia_producto_id' => ['required', 'integer'],
        ]);

        $ecoProd = DB::transaction(function () use ($data) {
            return EcografiaProducto::where('id', $data['ecografia_producto_id'])
                ->lockForUpdate()
                ->first();
        });

        if (!$ecoProd) {
            return response()->json([
                'message' => 'No se encontró el producto de ecografía en LIDiag.',
                'status' => 404,
            ], 404);
        }

        if ($ecoProd->estado_envio_sahi === 'ENVIADO') {
            return response()->json([
                'message' => 'La ecografía ya fue enviada a SAHI.',
                'data' => [
                    'ecografia_producto_id' => $ecoProd->id,
                    'sahi_esquema_149' => $ecoProd->sahi_esquema_149,
                    'sahi_esquema_150' => $ecoProd->sahi_esquema_150,
                    'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
                ],
            ], 200);
        }

        if ($ecoProd->estado_envio_sahi === 'ENVIANDO') {
            return response()->json([
                'message' => 'La ecografía ya está en proceso de envío a SAHI.',
                'data' => [
                    'ecografia_producto_id' => $ecoProd->id,
                    'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
                ],
            ], 202);
        }

        DB::transaction(function () use ($ecoProd) {
            EcografiaProducto::where('id', $ecoProd->id)->update([
                'estado_envio_sahi' => 'ENVIANDO',
                'error_envio_sahi' => null,
            ]);
        });

        SendEcografiaProductoToSahi::dispatch($ecoProd->id);

        return response()->json([
            'message' => 'Envío a SAHI encolado.',
            'data' => [
                'ecografia_producto_id' => $ecoProd->id,
                'estado_envio_sahi' => 'ENVIANDO',
            ],
        ], 202);
    }
}
