<?php

namespace App\Http\Controllers\Configuracion;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigTipoDocumento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigTipoDocumentoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:config.tipo.documento.index')->only(['index', 'show', 'listarActivos']);
        $this->middleware('permission:config.tipo.documento.create')->only(['store']);
        $this->middleware('permission:config.tipo.documento.edit')->only(['update']);
        $this->middleware('permission:config.tipo.documento.destroy')->only(['destroy']);
    }

    /**
     * Lista todos los tipos de documento.
     *
     * @return JsonResponse Lista de tipos de documento.
     */
    public function index()
    {
        $tiposDocumento = ConfigTipoDocumento::all();

        return response()->json($tiposDocumento);
    }

    /**
     * Crea un nuevo tipo de documento.
     *
     * @param  Request  $request  Datos del tipo de documento.
     * @return JsonResponse Datos del registro creado.
     */
    public function store(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|max:20',
            'tipo_documento' => 'required|string|max:100',
            'estado' => 'nullable|boolean',
        ]);
        $tipoDocumento = ConfigTipoDocumento::create($request->only(['codigo', 'tipo_documento', 'estado']));

        AuditLogger::crear($tipoDocumento, 'Tipo de documento creado');

        return response()->json($tipoDocumento);
    }

    /**
     * Muestra un tipo de documento específico.
     *
     * @param  int  $id  ID del tipo de documento.
     * @return JsonResponse Datos del tipo de documento o error 404.
     */
    public function show($id)
    {
        $tipoDocumento = ConfigTipoDocumento::find($id);
        if (! $tipoDocumento) {
            return response()->json([
                'message' => 'Tipo de documento no encontrado',
            ], 404);
        }

        return response()->json($tipoDocumento);
    }

    /**
     * Actualiza un tipo de documento.
     *
     * @param  Request  $request  Datos a actualizar.
     * @param  int  $id  ID del tipo de documento.
     * @return JsonResponse Datos actualizados o error 404.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'codigo' => 'required|string|max:20',
            'tipo_documento' => 'required|string|max:100',
            'estado' => 'nullable|boolean',
        ]);
        $tipoDocumento = ConfigTipoDocumento::find($id);
        if (! $tipoDocumento) {
            return response()->json([
                'message' => 'Tipo de documento no encontrado',
            ], 404);
        }

        $datosViejos = $tipoDocumento->toArray();
        $tipoDocumento->update($request->only(['codigo', 'tipo_documento', 'estado']));

        AuditLogger::actualizar($tipoDocumento, $datosViejos, 'Tipo de documento actualizado');

        return response()->json($tipoDocumento);
    }

    /**
     * Elimina un tipo de documento.
     *
     * @param  int  $id  ID del tipo de documento.
     * @return JsonResponse Confirmación de eliminación o error 404.
     */
    public function destroy($id)
    {
        $tipoDocumento = ConfigTipoDocumento::find($id);
        if (! $tipoDocumento) {
            return response()->json([
                'message' => 'Tipo de documento no encontrado',
            ], 404);
        }

        $tipoDocumento->delete();

        AuditLogger::eliminar($tipoDocumento, 'Tipo de documento eliminado');

        return response()->json([
            'message' => 'Tipo de documento eliminado',
        ]);
    }

    /**
     * Lista los tipos de documento activos.
     *
     * @return JsonResponse Lista de tipos de documento activos.
     */
    public function listarActivos()
    {
        $tiposDocumento = ConfigTipoDocumento::where('estado', 1)->get();

        return response()->json($tiposDocumento);
    }
}
