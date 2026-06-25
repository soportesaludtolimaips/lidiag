<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\EcografiaConsentimiento;
use App\Models\Ecografias\EcografiaProducto;
use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class EcografiaConsentimientosController extends Controller
{
    public function __construct()
    {
        // Endpoints internos (usuarios del sistema)
        $this->middleware('auth:sanctum')->only(['index', 'crearToken', 'firmarMedico', 'vigentePorEcografiaProducto', 'generarPdf', 'descargarPdf', 'generarZip']);

        $this->middleware('permission:ecografias.consentimientos.capturar_firma,web')->only(['crearToken']);
        $this->middleware('permission:ecografias.consentimientos.firmar_medico,web')->only(['firmarMedico']);
        $this->middleware('permission:ecografias.consentimientos.show|ecografias.consentimientos.index,web')->only(['vigentePorEcografiaProducto']);
        $this->middleware('permission:ecografias.consentimientos.index,web')->only(['index']);
        $this->middleware('permission:ecografias.consentimientos.pdf.generar,web')->only(['generarPdf']);
        $this->middleware('permission:ecografias.consentimientos.pdf.descargar,web')->only(['descargarPdf']);
        $this->middleware('permission:ecografias.consentimientos.zip.generar,web')->only(['generarZip']);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $sedeId = $request->query('sede_id');

        $query = EcografiaConsentimiento::query()
            ->with([
                'producto:id,cod_cups,nom_produc',
                'ecografiaProducto.ecografia.paciente',
            ])
            ->where('vigente', true);

        if ($sedeId) {
            $query->where('sede_id', (int) $sedeId);
        }
        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }
        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }
        if ($q !== '') {
            $query->whereHas('ecografiaProducto.ecografia.paciente', function ($p) use ($q) {
                $p->where('num_docu', 'like', "%{$q}%")
                    ->orWhere('nombres', 'like', "%{$q}%")
                    ->orWhere('pri_ape', 'like', "%{$q}%")
                    ->orWhere('seg_ape', 'like', "%{$q}%");
            });
        }

        return response()->json($query->orderByDesc('id')->get());
    }

    /**
     * Público (sin login): obtiene la vista previa HTML del consentimiento por token.
     */
    public function infoPorToken(Request $request, string $token)
    {
        $tokenHash = hash('sha256', $token);

        /** @var EcografiaConsentimiento|null $consent */
        $consent = EcografiaConsentimiento::query()
            ->with(['ecografiaProducto.ecografia.paciente', 'producto', 'ecografiaProducto.ecografia.medico', 'sede'])
            ->where('token_hash', $tokenHash)
            ->where('vigente', true)
            ->first();

        if (! $consent) {
            return response()->json(['message' => 'Token inválido o expirado'], 404);
        }

        if ($consent->token_used_at) {
            return response()->json(['message' => 'Este enlace ya fue usado'], 410);
        }

        if (! $consent->token_expires_at || now()->greaterThan($consent->token_expires_at)) {
            return response()->json(['message' => 'Token expirado'], 410);
        }

        $html = $this->renderHtml($consent);
        // En la vista del paciente mostramos marcadores donde van firmas
        $html = str_replace(
            ['${firma_paciente}', '${firma_acompanante}', '${firma_medico}'],
            [
                '<div style="border-bottom:1px solid #999; height:50px;"></div>',
                '<div style="border-bottom:1px solid #999; height:50px;"></div>',
                '<div style="border-bottom:1px solid #999; height:50px;"></div>',
            ],
            $html
        );

        $p = $consent->ecografiaProducto?->ecografia?->paciente;
        $producto = $consent->producto;

        return response()->json([
            'ok' => true,
            'consentimiento_id' => $consent->id,
            'firmante_tipo' => $consent->firmante_tipo,
            'producto' => [
                'id' => $producto?->id,
                'nombre' => $producto?->nom_produc,
                'cod_cups' => $producto?->cod_cups,
            ],
            'paciente' => [
                'num_docu' => $p?->num_docu,
                'nombres' => trim((string) (($p?->nombres ?? '').' '.($p?->pri_ape ?? '').' '.($p?->seg_ape ?? ''))),
            ],
            'html' => $html,
            'expires_at' => $consent->token_expires_at,
        ]);
    }

    /**
     * Transcriptor: crea (si aplica) el consentimiento vigente y genera token/URL
     * para firma de paciente/acompañante (10 minutos).
     */
    public function crearToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ecografia_producto_id' => 'required|integer|exists:ecografias_productos,id',
            'sede_id' => 'nullable|integer|exists:config_sedes,id',
            'firmante_tipo' => 'required|in:PACIENTE,ACOMPANANTE',
            'acompanante' => 'nullable|array',
            'acompanante.cedula' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:30',
            'acompanante.nombres' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:200',
            'acompanante.email' => 'required_if:firmante_tipo,ACOMPANANTE|email|max:150',
            'acompanante.direccion' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:255',
            'acompanante.telefono' => 'required_if:firmante_tipo,ACOMPANANTE|string|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $moduloEcografiasId = 2;
        $ep = EcografiaProducto::query()->with(['ecografia', 'producto'])->findOrFail((int) $request->ecografia_producto_id);
        $productoId = (int) $ep->producto_id;

        /** @var ConsentimientoPlantilla|null $plantilla */
        $plantilla = ConsentimientoPlantilla::query()
            ->where('config_modulo_id', $moduloEcografiasId)
            ->where('producto_id', $productoId)
            ->where('vigente', true)
            ->first();

        if (! $plantilla) {
            return response()->json(['message' => 'El producto no tiene plantilla de consentimiento configurada'], 422);
        }

        $res = DB::transaction(function () use ($request, $ep, $plantilla, $moduloEcografiasId) {
            $user = $request->user();
            $ip = (string) $request->ip();

            // Si existe consentimiento vigente:
            // - Si está ANULADO: lo dejamos como histórico (vigente=0) y creamos uno nuevo.
            // - Si está en progreso/completo: no permitimos duplicados.
            $vigente = EcografiaConsentimiento::query()
                ->where('ecografia_producto_id', $ep->id)
                ->where('vigente', true)
                ->lockForUpdate()
                ->first();

            if ($vigente) {
                if ($vigente->estado !== 'ANULADO') {
                    return [
                        'error' => true,
                        'status' => 422,
                        'message' => 'Ya existe un consentimiento vigente para este producto',
                    ];
                }
                $vigente->vigente = false;
                $vigente->save();
            }

            $token = Str::random(64);
            $tokenHash = hash('sha256', $token);
            $expiresAt = now()->addMinutes(10);

            $consent = EcografiaConsentimiento::create([
                'ecografia_producto_id' => $ep->id,
                'producto_id' => $ep->producto_id,
                'sede_id' => $request->filled('sede_id') ? (int) $request->sede_id : null,
                'config_modulo_id' => $moduloEcografiasId,
                'plantilla_id' => $plantilla->id,
                'plantilla_version' => (int) $plantilla->version,
                'plantilla_html' => (string) $plantilla->html,
                'vigente' => true,
                'vigente_key' => 1,
                'estado' => 'PENDIENTE_PACIENTE',
                'firmante_tipo' => (string) $request->firmante_tipo,
                'acompanante_cedula' => $request->firmante_tipo === 'ACOMPANANTE' ? (string) ($request->input('acompanante.cedula') ?? '') : null,
                'acompanante_nombres' => $request->firmante_tipo === 'ACOMPANANTE' ? (string) ($request->input('acompanante.nombres') ?? '') : null,
                'acompanante_email' => $request->firmante_tipo === 'ACOMPANANTE' ? (string) ($request->input('acompanante.email') ?? '') : null,
                'acompanante_direccion' => $request->firmante_tipo === 'ACOMPANANTE' ? (string) ($request->input('acompanante.direccion') ?? '') : null,
                'acompanante_telefono' => $request->firmante_tipo === 'ACOMPANANTE' ? (string) ($request->input('acompanante.telefono') ?? '') : null,
                'token_hash' => $tokenHash,
                'token_expires_at' => $expiresAt,
                'token_used_at' => null,
                'ip_token' => $ip,
                'transcriptor_id' => (int) $user->id,
                'ip_transcriptor' => $ip,
            ]);

            $url = url("/firma/consentimiento-ecografia/{$token}");

            return [
                'error' => false,
                'consentimiento' => $consent,
                'url' => $url,
                'expires_at' => $expiresAt,
            ];
        });

        if (isset($res['error']) && $res['error'] === true) {
            return response()->json(['message' => $res['message']], (int) ($res['status'] ?? 422));
        }

        return response()->json([
            'ok' => true,
            'consentimiento_id' => $res['consentimiento']->id,
            'url' => $res['url'],
            'expires_at' => $res['expires_at'],
        ]);
    }

    /**
     * Público (sin login): registra firma PNG del paciente/acompañante usando token.
     */
    public function firmarPacientePorToken(Request $request, string $token)
    {
        $validator = Validator::make($request->all(), [
            'firma_png' => 'required|string', // dataURL base64
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $tokenHash = hash('sha256', $token);

        /** @var EcografiaConsentimiento|null $consent */
        $consent = EcografiaConsentimiento::query()
            ->where('token_hash', $tokenHash)
            ->where('vigente', true)
            ->first();

        if (! $consent) {
            return response()->json(['message' => 'Token inválido o expirado'], 404);
        }

        if ($consent->token_used_at) {
            return response()->json(['message' => 'Este enlace ya fue usado'], 410);
        }

        if (! $consent->token_expires_at || now()->greaterThan($consent->token_expires_at)) {
            return response()->json(['message' => 'Token expirado'], 410);
        }

        if ($consent->estado !== 'PENDIENTE_PACIENTE') {
            return response()->json(['message' => 'El consentimiento no está pendiente de firma de paciente'], 422);
        }

        $dataUrl = $request->input('firma_png');
        if (! $dataUrl || ! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            return response()->json(['success' => false, 'message' => 'Formato inválido. Solo PNG.'], 422);
        }

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $decoded = base64_decode($base64, true);
        if ($decoded === false || strlen($decoded) > 2 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'Firma inválida o demasiado grande.'], 422);
        }

        $img = @imagecreatefromstring($decoded);
        if (! $img || imagesx($img) > 2048 || imagesy($img) > 2048) {
            imagedestroy($img);

            return response()->json(['success' => false, 'message' => 'Dimensiones excedidas (max 2048x2048).'], 422);
        }
        imagedestroy($img);

        $raw = $decoded;

        $prefix = $consent->firmante_tipo === 'ACOMPANANTE' ? 'Acompanante_' : 'Paciente_';
        $filename = $prefix.Str::random(40).'.png.enc';

        $path = Storage::disk('firmas_consntimientos')->put($filename, encrypt($raw));
        if (! $path) {
            return response()->json(['message' => 'No fue posible guardar la firma'], 500);
        }

        $consent->firma_paciente_path = "firmas_consntimientos/{$filename}";
        $consent->firma_paciente_at = now();
        $consent->ip_firma_paciente = (string) $request->ip();
        $consent->token_used_at = now();
        $consent->estado = 'PENDIENTE_MEDICO';
        $consent->save();

        return response()->json(['ok' => true, 'message' => 'Firma registrada correctamente']);
    }

    /**
     * Médico: firma (sin capturar trazo) usando users.firma (PNG ya cargada).
     */
    public function firmarMedico(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'consentimiento_id' => 'required|integer|exists:ecografias_consentimientos,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        if (! $user->firma) {
            return response()->json(['message' => 'Debe cargar firma'], 422);
        }

        /** @var EcografiaConsentimiento $consent */
        $consent = EcografiaConsentimiento::query()
            ->with(['ecografiaProducto.ecografia'])
            ->where('id', (int) $request->consentimiento_id)
            ->where('vigente', true)
            ->firstOrFail();

        // Solo puede firmar si el estudio está en su lista (ecografias.medico_id)
        $assignedMedicoId = (int) ($consent->ecografiaProducto?->ecografia?->medico_id ?? 0);
        if ($assignedMedicoId !== (int) $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($consent->estado !== 'PENDIENTE_MEDICO') {
            return response()->json(['message' => 'El consentimiento no está pendiente de firma de médico'], 422);
        }

        $consent->medico_id = (int) $user->id;
        $consent->firma_medico_path = (string) $user->firma; // snapshot
        $consent->firma_medico_at = now();
        $consent->ip_firma_medico = (string) $request->ip();
        $consent->estado = 'COMPLETO';
        $consent->save();

        return response()->json(['ok' => true]);
    }

    public function anular(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'observacion' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $c = EcografiaConsentimiento::query()->where('id', $id)->where('vigente', true)->firstOrFail();

        // Solo permitir anular si no está ya anulado
        if ($c->estado === 'ANULADO') {
            return response()->json(['message' => 'El consentimiento ya está anulado'], 422);
        }

        $c->estado = 'ANULADO';
        $c->anulada_por_id = (int) $request->user()->id;
        $c->anulada_at = now();
        $c->anulacion_observacion = (string) $request->observacion;
        // Dejar histórico y permitir crear uno nuevo
        $c->vigente = false;
        $c->vigente_key = null;
        $c->save();

        return response()->json(['ok' => true]);
    }

    public function vigentePorEcografiaProducto(Request $request, int $ecografia_producto_id)
    {
        $c = EcografiaConsentimiento::query()
            ->where('ecografia_producto_id', $ecografia_producto_id)
            ->where('vigente', true)
            ->first();

        return response()->json($c);
    }

    public function generarPdf(Request $request, int $id)
    {
        /** @var EcografiaConsentimiento $consent */
        $consent = EcografiaConsentimiento::query()
            ->with(['ecografiaProducto.ecografia.paciente', 'producto', 'ecografiaProducto.ecografia.medico'])
            ->where('id', $id)
            ->where('vigente', true)
            ->firstOrFail();

        if ($consent->estado !== 'COMPLETO') {
            return response()->json(['message' => 'Consentimiento incompleto'], 422);
        }

        if (! $consent->firma_paciente_path || ! $consent->firma_medico_path) {
            return response()->json(['message' => 'Faltan firmas para generar PDF'], 422);
        }

        $html = $this->renderHtmlForPdf($consent);

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html)->setPaper('a4', 'portrait');
        $output = $pdf->output();

        $p = $consent->ecografiaProducto?->ecografia?->paciente;
        $numDoc = (string) ($p?->num_docu ?? 'NA');
        $cod = (string) ($consent->producto?->cod_cups ?? 'NA');

        $baseName = "Consentimiento_{$numDoc}_{$cod}.pdf";
        $filename = $this->makeUniquePdfFilename($baseName).'.enc';

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('files_consentimientos');
        $disk->put($filename, encrypt($output));

        $consent->pdf_path = "files_consentimientos/{$filename}";
        $consent->pdf_generated_at = now();
        $consent->save();

        return response()->json(['ok' => true, 'pdf_path' => $consent->pdf_path]);
    }

    public function descargarPdf(Request $request, int $id)
    {
        /** @var EcografiaConsentimiento $consent */
        $consent = EcografiaConsentimiento::query()->where('id', $id)->where('vigente', true)->firstOrFail();
        if (! $consent->pdf_path) {
            return response()->json(['message' => 'PDF no generado'], 404);
        }

        $prefix = 'files_consentimientos/';
        $rel = str_starts_with($consent->pdf_path, $prefix) ? substr($consent->pdf_path, strlen($prefix)) : $consent->pdf_path;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('files_consentimientos');

        if (! $disk->exists($rel)) {
            return response()->json(['message' => 'PDF no encontrado'], 404);
        }

        return $disk->download($rel);
    }

    public function generarZip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $ids = array_values(array_unique(array_map('intval', (array) $request->ids)));
        $cons = EcografiaConsentimiento::query()
            ->whereIn('id', $ids)
            ->where('vigente', true)
            ->whereNotNull('pdf_path')
            ->get();

        $zipName = 'consentimientos_'.now()->format('Ymd_His').'_'.Str::random(8).'.zip';
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('files_consentimientos');

        $zipRel = $zipName;
        $zipFull = $disk->path($zipRel);

        $zip = new \ZipArchive;
        if ($zip->open($zipFull, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'No fue posible crear el ZIP'], 500);
        }

        foreach ($cons as $c) {
            $prefix = 'files_consentimientos/';
            $rel = str_starts_with($c->pdf_path, $prefix) ? substr($c->pdf_path, strlen($prefix)) : $c->pdf_path;
            if (! $disk->exists($rel)) {
                continue;
            }
            $zip->addFile($disk->path($rel), basename($rel));
        }

        $zip->close();

        return response()->download($zipFull)->deleteFileAfterSend(true);
    }

    private function renderHtml(EcografiaConsentimiento $consent): string
    {
        $html = (string) $consent->plantilla_html;

        $p = $consent->ecografiaProducto?->ecografia?->paciente;
        $e = $consent->ecografiaProducto?->ecografia;
        $ep = $consent->ecografiaProducto;
        $producto = $consent->producto;
        $medico = $e?->medico;

        $edad = null;
        if ($p?->fec_naci) {
            try {
                $edad = Carbon::parse($p->fec_naci)->age;
            } catch (\Throwable $t) {
                $edad = null;
            }
        }

        $repl = [
            // Paciente
            '${nombre_paciente}' => trim((string) (($p?->nombres ?? '').' '.($p?->pri_ape ?? '').' '.($p?->seg_ape ?? ''))),
            '${num_docu}' => (string) ($p?->num_docu ?? ''),
            '${fec_naci}' => (string) ($p?->fec_naci ?? ''),
            '${sexo}' => (string) ($p?->sexo ?? ''),
            '${edad}' => $edad !== null ? (string) $edad : '',
            '${dir}' => (string) ($p?->direccion ?? ''),
            '${tel}' => (string) ($p?->tel ?? ''),
            '${cel}' => (string) ($p?->cel ?? ''),
            '${eps}' => (string) ($e?->adminSalud?->nom_admin_salud ?? ''),
            '${contrato}' => (string) ($e?->contrato ?? ''),
            '${nom_sede}' => (string) ($consent->sede?->nom_sede ?? ''),

            // Estudio / producto
            '${cod_producto}' => (string) ($producto?->cod_cups ?? ''),
            '${nom_producto}' => (string) ($producto?->nom_produc ?? ''),
            '${fechor_estudio}' => $e?->created_at ? (string) $e->created_at : '',
            '${fechor_lectura}' => $ep?->fechor_lectura ? (string) $ep->fechor_lectura : '',
            '${lectura}' => (string) ($ep?->lectura ?? ''),
            '${opinion}' => (string) ($ep?->opinion ?? ''),
            '${productos}' => (string) ($producto?->nom_produc ?? ''),

            // Médico
            '${nombres_medico}' => (string) ($medico?->name ?? ''),
            '${num_docu_medico}' => (string) ($medico?->num_docu ?? ''),
            '${reg_medico}' => (string) ($medico?->reg_medico ?? ''),
        ];

        $html = str_replace(array_keys($repl), array_values($repl), $html);

        return $html;
    }

    private function renderHtmlForPdf(EcografiaConsentimiento $consent): string
    {
        $html = $this->renderHtml($consent);

        // Embebido de firmas
        $firmaPaciente = $this->dataUriFromStoredPath($consent->firma_paciente_path);
        $firmaMedico = $this->dataUriFromStoredPath($consent->firma_medico_path);

        if ($firmaPaciente) {
            $html = str_replace('${firma_paciente}', '<img style="max-height:80px;" src="'.$firmaPaciente.'">', $html);
            $html = str_replace('${firma_acompanante}', '<img style="max-height:80px;" src="'.$firmaPaciente.'">', $html);
        }
        if ($firmaMedico) {
            $html = str_replace('${firma_medico}', '<img style="max-height:80px;" src="'.$firmaMedico.'">', $html);
        }

        // Metadatos (fecha/hora de generación + usuario)
        $genBy = Auth::user()?->name ?: 'Sistema';
        $meta = '<hr><small>Generado: '.now()->format('Y-m-d H:i:s').' · Usuario: '.e($genBy).'</small>';

        return '<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px;">'.$html.$meta.'</div>';
    }

    private function dataUriFromStoredPath(?string $storedPath): ?string
    {
        if (! $storedPath) {
            return null;
        }

        // paciente/acompañante: firmas_consntimientos/...
        if (str_starts_with($storedPath, 'firmas_consntimientos/')) {
            $rel = substr($storedPath, strlen('firmas_consntimientos/'));
            if (! Storage::disk('firmas_consntimientos')->exists($rel)) {
                return null;
            }
            $bin = Storage::disk('firmas_consntimientos')->get($rel);

            return 'data:image/png;base64,'.base64_encode($bin);
        }

        // médico: firmas_medicos/...
        if (str_starts_with($storedPath, 'firmas_medicos/')) {
            $rel = substr($storedPath, strlen('firmas_medicos/'));
            if (! Storage::disk('firmas_medicos')->exists($rel)) {
                return null;
            }
            $bin = Storage::disk('firmas_medicos')->get($rel);
            $ext = strtolower(pathinfo($rel, PATHINFO_EXTENSION));
            $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png';

            return "data:{$mime};base64,".base64_encode($bin);
        }

        return null;
    }

    private function makeUniquePdfFilename(string $baseName): string
    {
        $extension = pathinfo($baseName, PATHINFO_EXTENSION) ?: 'pdf';
        $filenameWithoutExtension = pathinfo($baseName, PATHINFO_FILENAME) ?: 'Consentimiento';
        $timestamp = time();
        $uniqueId = uniqid();

        return "{$filenameWithoutExtension}_{$timestamp}_{$uniqueId}.{$extension}";
    }
}
