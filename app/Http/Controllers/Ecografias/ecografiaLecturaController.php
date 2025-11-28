<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Estudios\PlantillaEstudioController;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use Illuminate\Http\Request;

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
        // Verificar si existen productos para la ecografía
        $query = EcografiaProducto::where('ecografia_id', $id);

        if (!$query->exists()) {
            return response()->json(['message' => 'El producto de la ecografía no se encontró'], 404);
        }

        // Acción: IMPRIMIR_LECTURA (solo primer producto)
        if ($request->accion === "IMPRIMIR_LECTURA") {
            $producto = $query->first();

            $producto->update([
                'lectura' => $request->registro['lectura'],
                'opinion' => $request->registro['opinion'],
                'usua_regis_id' => auth()->id(),
                'fechor_lectura' => now(),
            ]);
        }

        // Acción: TERMINAR_LECTURA (todos los productos)
        if ($request->accion === "TERMINAR_LECTURA") {
            $query->update([
                'lectura' => $request->registro['lectura'],
                'opinion' => $request->registro['opinion'],
                'usua_regis_id' => auth()->id(),
                'fechor_lectura' => now(),
                'estado' => 'LEIDO'
            ]);
        }

        return response()->json(['message' => 'Lectura guardada exitosamente'], 201);
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
        if(!$medico){
            return response()->json(['message' => 'El medico no se encontro'], 404);
        }
        $ecografiasPendientes = Ecografia::select(
            'ecografias.*',
            'ep.id as ecografia_producto_id',
            'ep.lectura',
            'ep.opinion',
            'ep.fechor_lectura',
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
