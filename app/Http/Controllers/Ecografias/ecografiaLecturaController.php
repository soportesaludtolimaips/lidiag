<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Estudios\PlantillaEstudioController;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ecografiaLecturaController extends Controller
{
    /**
     * Registra o actualiza la lectura y opinión de una ecografía.
     *
     * Flujo del proceso:
     * 1. Verifica si existen productos asociados a la ecografía.
     * 2. Si la acción es 'IMPRIMIR_LECTURA', actualiza la lectura y opinión del primer producto (guardado parcial).
     * 3. Si la acción es 'TERMINAR_LECTURA', actualiza todos los productos, establece fecha de lectura y cambia el estado a 'LEIDO'.
     * 4. Retorna mensaje de éxito.
     *
     * @param Request $request Datos de la lectura (texto, opinion, accion).
     * @param int $id ID de la ecografía.
     * @return \Illuminate\Http\JsonResponse Confirmación de guardado.
     */
    public function leer(Request $request, $id)
    {
        $accion = (string) ($request->accion ?? '');

        return DB::transaction(function () use ($request, $id, $accion) {
            // Verificar si existen productos para la ecografía
            $query = EcografiaProducto::where('ecografia_id', $id);

            if (!$query->exists()) {
                return response()->json(['message' => 'El producto de la ecografía no se encontró'], 404);
            }

            // Acción: IMPRIMIR_LECTURA (solo primer producto)
            if ($accion === "IMPRIMIR_LECTURA") {
                $producto = $query->first();

                $producto->update([
                    'lectura' => $request->registro['lectura'] ?? null,
                    'opinion' => $request->registro['opinion'] ?? null,
                    'usua_regis_id' => auth()->id(),
                    'fechor_lectura' => now(),
                ]);

                // No cambia estado del padre ni de todos los productos
                return response()->json(['message' => 'Lectura guardada exitosamente'], 201);
            }

            // Acción: TERMINAR_LECTURA (todos los productos) + sincronizar estado del padre
            if ($accion === "TERMINAR_LECTURA") {
                $query->update([
                    'lectura' => $request->registro['lectura'] ?? null,
                    'opinion' => $request->registro['opinion'] ?? null,
                    'usua_regis_id' => auth()->id(),
                    'fechor_lectura' => now(),
                    'estado' => 'LEIDO',
                ]);

                Ecografia::where('id', $id)->update([
                    'estado' => 'LEIDO',
                ]);

                return response()->json(['message' => 'Lectura guardada exitosamente'], 201);
            }

            return response()->json([
                'message' => 'Acción no soportada.',
            ], 422);
        });
    }



    /**
     * Lista las ecografías asignadas a un médico que están pendientes de lectura.
     *
     * Flujo del proceso:
     * 1. Verifica la existencia del médico.
     * 2. Consulta ecografías uniendo con productos y configuración.
     * 3. Filtra por ID del médico y estado del producto 'EN PROCESO'.
     * 4. Retorna la lista de ecografías pendientes.
     *
     * @param int $idMedico ID del médico.
     * @return \Illuminate\Http\JsonResponse Lista de ecografías pendientes.
     */
    public function listarPendientesPorLectura($idMedico)
    {
        $medico = User::find($idMedico);
        if (!$medico) {
            return response()->json(['message' => 'El medico no se encontro'], 404);
        }

        $ecografiasPendientes = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.opinion',
            'ep.fechor_lectura',
            'ep.estado_envio_sahi',
            'ep.fechor_envio_sahi',
            'ep.sahi_esquema_149',
            'ep.sahi_esquema_150',
            'ep.error_envio_sahi',
            'p.id as producto_id',
            'p.nom_produc'
        )
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id')
            ->where('ecografias.medico_id', $idMedico)
            ->where('ep.estado', '=', 'EN PROCESO')
            ->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->get();

        return response()->json(['ecografias' => $ecografiasPendientes]);
    }
}
