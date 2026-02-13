<?php

namespace App\Http\Controllers\Consentimientos;

use App\Http\Controllers\Controller;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConsentimientoPlantillasController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.consentimientos.plantillas.index,web')->only(['index', 'show', 'versions']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.create,web')->only(['store']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.edit,web')->only(['update', 'setVigente']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.destroy,web')->only(['destroy']);
        $this->middleware('permission:ecografias.consentimientos.plantillas.preview_pdf,web')->only(['previewPdf']);
    }

    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'config_modulo_id' => 'required|integer|exists:config_modulos,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $moduloId = (int) $request->config_modulo_id;

        $q = trim((string) $request->query('q', ''));
        $onlyVigentes = $request->query('vigentes', '1') !== '0';

        $query = ConsentimientoPlantilla::query()
            ->with(['producto:id,cod_cups,nom_produc', 'usuarioCrea:id,name'])
            ->where('config_modulo_id', $moduloId);

        if ($onlyVigentes) {
            $query->where('vigente', true);
        }

        if ($q !== '') {
            $query->whereHas('producto', function ($p) use ($q) {
                $p->where('nom_produc', 'like', "%{$q}%")
                    ->orWhere('cod_cups', 'like', "%{$q}%");
            })->orWhere('nombre', 'like', "%{$q}%");
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    public function show(int $id)
    {
        $plantilla = ConsentimientoPlantilla::query()
            ->with(['producto:id,cod_cups,nom_produc', 'usuarioCrea:id,name'])
            ->findOrFail($id);

        return response()->json($plantilla);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'config_modulo_id' => 'required|integer|exists:config_modulos,id',
            'producto_id' => 'required|integer|exists:config_productos,id',
            'nombre' => 'required|string|max:255',
            'html' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $moduloId = (int) $request->config_modulo_id;
        $productoId = (int) $request->producto_id;

        $plantilla = DB::transaction(function () use ($request, $moduloId, $productoId) {
            // Si existe vigente, la desactivamos y creamos una nueva versión
            $vigente = ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', $productoId)
                ->where('vigente', true)
                ->lockForUpdate()
                ->first();

            $nextVersion = 1;
            if ($vigente) {
                $nextVersion = (int) $vigente->version + 1;
                $vigente->vigente = false;
                $vigente->vigente_key = null;
                $vigente->save();
            }

            return ConsentimientoPlantilla::create([
                'config_modulo_id' => $moduloId,
                'producto_id' => $productoId,
                'usuario_crea_id' => (int) $request->user()->id,
                'nombre' => (string) $request->nombre,
                'html' => (string) $request->html,
                'version' => $nextVersion,
                'vigente' => true,
                'vigente_key' => 1,
            ]);
        });

        return response()->json($plantilla->load(['producto', 'usuarioCrea']), 201);
    }

    /**
     * Actualizar = crear nueva versión (no editar en sitio).
     */
    public function update(Request $request, int $id)
    {
        $current = ConsentimientoPlantilla::query()->findOrFail($id);
        $moduloId = (int) $current->config_modulo_id;

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'html' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $productoId = (int) $current->producto_id;

        $new = DB::transaction(function () use ($request, $moduloId, $productoId) {
            // Desactivar vigente actual de ese producto (sea cual sea)
            ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', $productoId)
                ->where('vigente', true)
                ->lockForUpdate()
                ->update(['vigente' => false, 'vigente_key' => null]);

            $maxVersion = (int) ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', $productoId)
                ->max('version');

            return ConsentimientoPlantilla::create([
                'config_modulo_id' => $moduloId,
                'producto_id' => $productoId,
                'usuario_crea_id' => (int) $request->user()->id,
                'nombre' => (string) $request->nombre,
                'html' => (string) $request->html,
                'version' => $maxVersion + 1,
                'vigente' => true,
                'vigente_key' => 1,
            ]);
        });

        return response()->json($new->load(['producto', 'usuarioCrea']));
    }

    public function versions(int $productoId)
    {
        $moduloId = (int) request()->query('config_modulo_id', 0);
        if ($moduloId <= 0) {
            return response()->json(['message' => 'config_modulo_id es obligatorio'], 422);
        }
        $items = ConsentimientoPlantilla::query()
            ->with(['usuarioCrea:id,name'])
            ->where('config_modulo_id', $moduloId)
            ->where('producto_id', $productoId)
            ->orderByDesc('version')
            ->get();

        return response()->json($items);
    }

    public function setVigente(Request $request, int $id)
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $moduloId = (int) $p->config_modulo_id;

        DB::transaction(function () use ($moduloId, $p) {
            ConsentimientoPlantilla::query()
                ->where('config_modulo_id', $moduloId)
                ->where('producto_id', (int) $p->producto_id)
                ->where('vigente', true)
                ->lockForUpdate()
                ->update(['vigente' => false, 'vigente_key' => null]);

            $p->vigente = true;
            $p->vigente_key = 1;
            $p->save();
        });

        return response()->json(['ok' => true]);
    }

    public function destroy(int $id)
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $p->vigente = false;
        $p->save();

        return response()->json(['ok' => true]);
    }

    public function previewPdf(int $id)
    {
        $p = ConsentimientoPlantilla::query()->findOrFail($id);
        $html = '<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px;">'.$p->html.'</div>';

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream('plantilla_consentimiento.pdf');
    }
}
