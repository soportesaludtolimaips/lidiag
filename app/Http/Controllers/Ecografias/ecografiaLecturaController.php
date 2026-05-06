<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Models\Ecografias\Ecografia;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ecografiaLecturaController extends Controller
{
    public function __construct()
    {
        // Estas rutas se exponen por /api (ver routes/api_ecografias.php)
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.leer,web')->only(['leer', 'listarPendientesPorLectura']);
    }

    /**
     * Registra o actualiza la lectura y opinión de una ecografía.
     *
     * Flujo del proceso:
     * 1. Verifica si existen productos asociados a la ecografía.
     * 2. Si la acción es 'IMPRIMIR_LECTURA', actualiza la lectura y opinión del primer producto (guardado parcial).
     * 3. Si la acción es 'TERMINAR_LECTURA', actualiza todos los productos, establece fecha de lectura y cambia el estado a 'LEIDO'.
     * 4. Retorna mensaje de éxito.
     *
     * @param  Request  $request  Datos de la lectura (texto, opinion, accion).
     * @param  int  $id  ID de la ecografía.
     * @return JsonResponse Confirmación de guardado.
     */
    public function leer(Request $request, $id)
    {
        $accion = (string) ($request->accion ?? '');

        return DB::transaction(function () use ($request, $id, $accion) {
            // Verificar si existen productos para la ecografía
            $query = EcografiaProducto::where('ecografia_id', $id);

            if (! $query->exists()) {
                return response()->json(['message' => 'El producto de la ecografía no se encontró'], 404);
            }

            // Acción: IMPRIMIR_LECTURA (guardado sin cerrar lectura)
            if ($accion === "IMPRIMIR_LECTURA") {
                // Guardar en TODOS los productos, sin cambiar estado (para evitar inconsistencias en multi-producto)
                $query->update([
                    'lectura' => $request->registro['lectura'] ?? null,
                    'opinion' => $request->registro['opinion'] ?? null,
                    'usua_regis_id' => auth()->id(),
                    'fechor_lectura' => now(),
                ]);

                // No cambia estado del padre ni marca 'LEIDO'
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
     * @param  int  $idMedico  ID del médico.
     * @return JsonResponse Lista de ecografías pendientes.
     */
    public function listarPendientesPorLectura($idMedico)
    {
        $medico = User::find($idMedico);
        if (! $medico) {
            return response()->json(['message' => 'El medico no se encontro'], 404);
        }

        // Plantillas vigentes del módulo Ecografías (config_modulos.id=2) por producto
        $moduloEcografiasId = 2;

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
            'p.nom_produc',
            'c.id as consentimiento_id',
            'c.estado as consentimiento_estado',
            'c.firma_paciente_at as consentimiento_firma_paciente_at',
            'c.firma_medico_at as consentimiento_firma_medico_at',
            DB::raw('CASE WHEN cp.id IS NULL THEN 0 ELSE 1 END as tiene_plantilla_consentimiento')
        )
            ->join('ecografias_productos as ep', 'ep.ecografia_id', '=', 'ecografias.id')
            ->join('config_productos as p', 'p.id', '=', 'ep.producto_id')
            ->leftJoin('consentimiento_plantillas as cp', function ($join) use ($moduloEcografiasId) {
                $join->on('cp.producto_id', '=', 'p.id')
                    ->where('cp.config_modulo_id', '=', $moduloEcografiasId)
                    ->where('cp.vigente', '=', 1);
            })
            ->leftJoin('ecografias_consentimientos as c', function ($join) {
                $join->on('c.ecografia_producto_id', '=', 'ep.id')
                    ->where('c.vigente', '=', 1);
            })
            ->where('ecografias.medico_id', $idMedico)
            ->where('ep.estado', '=', 'EN PROCESO')
            ->with(['paciente', 'usuarioRegistrador', 'adminSalud', 'medico'])
            ->get();

        return response()->json(['ecografias' => $ecografiasPendientes]);
    }
}
