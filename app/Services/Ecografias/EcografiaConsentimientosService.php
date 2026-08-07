<?php

namespace App\Services\Ecografias;

use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\EcografiaConsentimiento;
use App\Models\Ecografias\EcografiaProducto;
use App\Services\BaseService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EcografiaConsentimientosService extends BaseService
{
    public function index(array $filters = []): array
    {
        $query = EcografiaConsentimiento::query()
            ->with([
                'producto:id,cod_cups,nom_produc',
                'ecografiaProducto.ecografia.paciente',
            ])
            ->where('vigente', true);

        if (! empty($filters['sede_id'])) {
            $query->where('sede_id', (int) $filters['sede_id']);
        }
        if (! empty($filters['desde'])) {
            $query->whereDate('created_at', '>=', $filters['desde']);
        }
        if (! empty($filters['hasta'])) {
            $query->whereDate('created_at', '<=', $filters['hasta']);
        }
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->whereHas('ecografiaProducto.ecografia.paciente', function ($p) use ($q) {
                $p->where('num_docu', 'like', "%{$q}%")
                    ->orWhere('nombres', 'like', "%{$q}%")
                    ->orWhere('pri_ape', 'like', "%{$q}%")
                    ->orWhere('seg_ape', 'like', "%{$q}%");
            });
        }

        return $this->success($query->orderByDesc('id')->get());
    }

    public function infoPorToken(string $token): array
    {
        $tokenHash = hash('sha256', $token);

        $consent = EcografiaConsentimiento::query()
            ->with(['ecografiaProducto.ecografia.paciente', 'producto', 'ecografiaProducto.ecografia.medico', 'sede'])
            ->where('token_hash', $tokenHash)
            ->where('vigente', true)
            ->first();

        if (! $consent) {
            return $this->notFound('Token inválido o expirado');
        }

        if ($consent->token_used_at) {
            return $this->error('Este enlace ya fue usado', 410);
        }

        if (! $consent->token_expires_at || now()->greaterThan($consent->token_expires_at)) {
            return $this->error('Token expirado', 410);
        }

        $html = $this->renderHtml($consent);
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

        return $this->success([
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

    public function crearToken(array $data, $user, ?string $ip): array
    {
        $moduloEcografiasId = 2;
        $ep = EcografiaProducto::query()->with(['ecografia', 'producto'])->findOrFail((int) $data['ecografia_producto_id']);
        $productoId = (int) $ep->producto_id;

        $plantilla = ConsentimientoPlantilla::query()
            ->where('config_modulo_id', $moduloEcografiasId)
            ->where('producto_id', $productoId)
            ->where('vigente', true)
            ->first();

        if (! $plantilla) {
            return $this->validationError(null, 'El producto no tiene plantilla de consentimiento configurada');
        }

        $res = DB::transaction(function () use ($data, $ep, $plantilla, $moduloEcografiasId, $user, $ip) {
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
                'sede_id' => ! empty($data['sede_id']) ? (int) $data['sede_id'] : null,
                'config_modulo_id' => $moduloEcografiasId,
                'plantilla_id' => $plantilla->id,
                'plantilla_version' => (int) $plantilla->version,
                'plantilla_html' => (string) $plantilla->html,
                'vigente' => true,
                'vigente_key' => 1,
                'estado' => 'PENDIENTE_PACIENTE',
                'firmante_tipo' => (string) $data['firmante_tipo'],
                'acompanante_cedula' => $data['firmante_tipo'] === 'ACOMPANANTE' ? (string) ($data['acompanante']['cedula'] ?? '') : null,
                'acompanante_nombres' => $data['firmante_tipo'] === 'ACOMPANANTE' ? (string) ($data['acompanante']['nombres'] ?? '') : null,
                'acompanante_email' => $data['firmante_tipo'] === 'ACOMPANANTE' ? (string) ($data['acompanante']['email'] ?? '') : null,
                'acompanante_direccion' => $data['firmante_tipo'] === 'ACOMPANANTE' ? (string) ($data['acompanante']['direccion'] ?? '') : null,
                'acompanante_telefono' => $data['firmante_tipo'] === 'ACOMPANANTE' ? (string) ($data['acompanante']['telefono'] ?? '') : null,
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
            return $this->error($res['message'], (int) ($res['status'] ?? 422));
        }

        return $this->success([
            'ok' => true,
            'consentimiento_id' => $res['consentimiento']->id,
            'url' => $res['url'],
            'expires_at' => $res['expires_at'],
        ]);
    }

    public function firmarPacientePorToken(string $token, array $data, ?string $ip): array
    {
        $tokenHash = hash('sha256', $token);

        $consent = EcografiaConsentimiento::query()
            ->where('token_hash', $tokenHash)
            ->where('vigente', true)
            ->first();

        if (! $consent) {
            return $this->notFound('Token inválido o expirado');
        }

        if ($consent->token_used_at) {
            return $this->error('Este enlace ya fue usado', 410);
        }

        if (! $consent->token_expires_at || now()->greaterThan($consent->token_expires_at)) {
            return $this->error('Token expirado', 410);
        }

        if ($consent->estado !== 'PENDIENTE_PACIENTE') {
            return $this->validationError(null, 'El consentimiento no está pendiente de firma de paciente');
        }

        $dataUrl = $data['firma_png'];
        if (! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            return $this->validationError(null, 'Formato inválido. Solo PNG.');
        }

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $decoded = base64_decode($base64, true);
        if ($decoded === false || strlen($decoded) > 2 * 1024 * 1024) {
            return $this->validationError(null, 'Firma inválida o demasiado grande.');
        }

        $img = @imagecreatefromstring($decoded);
        if (! $img || imagesx($img) > 2048 || imagesy($img) > 2048) {
            imagedestroy($img);

            return $this->validationError(null, 'Dimensiones excedidas (max 2048x2048).');
        }
        imagedestroy($img);

        $raw = $decoded;
        $prefix = $consent->firmante_tipo === 'ACOMPANANTE' ? 'Acompanante_' : 'Paciente_';
        $filename = $prefix.Str::random(40).'.png.enc';

        $path = Storage::disk('firmas_consntimientos')->put($filename, encrypt($raw));
        if (! $path) {
            return $this->error('No fue posible guardar la firma', 500);
        }

        $consent->firma_paciente_path = "firmas_consntimientos/{$filename}";
        $consent->firma_paciente_at = now();
        $consent->ip_firma_paciente = $ip;
        $consent->token_used_at = now();
        $consent->estado = 'PENDIENTE_MEDICO';
        $consent->save();

        return $this->success(['ok' => true, 'message' => 'Firma registrada correctamente']);
    }

    public function firmarMedico(array $data, $user, ?string $ip): array
    {
        if (! $user->firma) {
            return $this->validationError(null, 'Debe cargar firma');
        }

        $consent = EcografiaConsentimiento::query()
            ->with(['ecografiaProducto.ecografia'])
            ->where('id', (int) $data['consentimiento_id'])
            ->where('vigente', true)
            ->firstOrFail();

        $assignedMedicoId = (int) ($consent->ecografiaProducto?->ecografia?->medico_id ?? 0);
        if ($assignedMedicoId !== (int) $user->id) {
            return $this->unauthorized('Forbidden');
        }

        if ($consent->estado !== 'PENDIENTE_MEDICO') {
            return $this->validationError(null, 'El consentimiento no está pendiente de firma de médico');
        }

        $consent->medico_id = (int) $user->id;
        $consent->firma_medico_path = (string) $user->firma;
        $consent->firma_medico_at = now();
        $consent->ip_firma_medico = $ip;
        $consent->estado = 'COMPLETO';
        $consent->save();

        return $this->success(['ok' => true]);
    }

    public function anular(int $id, array $data, $user): array
    {
        $c = EcografiaConsentimiento::query()->where('id', $id)->where('vigente', true)->firstOrFail();

        if ($c->estado === 'ANULADO') {
            return $this->validationError(null, 'El consentimiento ya está anulado');
        }

        $c->estado = 'ANULADO';
        $c->anulada_por_id = (int) $user->id;
        $c->anulada_at = now();
        $c->anulacion_observacion = (string) $data['observacion'];
        $c->vigente = false;
        $c->vigente_key = null;
        $c->save();

        return $this->success(['ok' => true]);
    }

    public function vigentePorEcografiaProducto(int $ecografiaProductoId): array
    {
        $c = EcografiaConsentimiento::query()
            ->where('ecografia_producto_id', $ecografiaProductoId)
            ->where('vigente', true)
            ->first();

        return $this->success($c);
    }

    public function generarPdf(int $id): array
    {
        $consent = EcografiaConsentimiento::query()
            ->with(['ecografiaProducto.ecografia.paciente', 'producto', 'ecografiaProducto.ecografia.medico'])
            ->where('id', $id)
            ->where('vigente', true)
            ->firstOrFail();

        if ($consent->estado !== 'COMPLETO') {
            return $this->validationError(null, 'Consentimiento incompleto');
        }

        if (! $consent->firma_paciente_path || ! $consent->firma_medico_path) {
            return $this->validationError(null, 'Faltan firmas para generar PDF');
        }

        $html = $this->renderHtmlForPdf($consent);

        $dompdf = new Dompdf;
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();

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

        return $this->success(['ok' => true, 'pdf_path' => $consent->pdf_path]);
    }

    public function descargarPdf(int $id): array
    {
        $consent = EcografiaConsentimiento::query()->where('id', $id)->where('vigente', true)->firstOrFail();

        if (! $consent->pdf_path) {
            return $this->notFound('PDF no generado');
        }

        $prefix = 'files_consentimientos/';
        $rel = str_starts_with($consent->pdf_path, $prefix) ? substr($consent->pdf_path, strlen($prefix)) : $consent->pdf_path;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('files_consentimientos');

        if (! $disk->exists($rel)) {
            return $this->notFound('PDF no encontrado');
        }

        return $this->success(['path' => $rel, 'disk' => 'files_consentimientos']);
    }

    public function generarZip(array $data): array
    {
        $ids = array_values(array_unique(array_map('intval', (array) $data['ids'])));
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
            return $this->error('No fue posible crear el ZIP', 500);
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

        return $this->success(['path' => $zipFull]);
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
            '${cod_producto}' => (string) ($producto?->cod_cups ?? ''),
            '${nom_producto}' => (string) ($producto?->nom_produc ?? ''),
            '${fechor_estudio}' => $e?->created_at ? (string) $e->created_at : '',
            '${fechor_lectura}' => $ep?->fechor_lectura ? (string) $ep->fechor_lectura : '',
            '${lectura}' => (string) ($ep?->lectura ?? ''),
            '${opinion}' => (string) ($ep?->opinion ?? ''),
            '${productos}' => (string) ($producto?->nom_produc ?? ''),
            '${nombres_medico}' => (string) ($medico?->name ?? ''),
            '${num_docu_medico}' => (string) ($medico?->num_docu ?? ''),
            '${reg_medico}' => (string) ($medico?->reg_medico ?? ''),
        ];

        return str_replace(array_keys($repl), array_values($repl), $html);
    }

    private function renderHtmlForPdf(EcografiaConsentimiento $consent): string
    {
        $html = $this->renderHtml($consent);

        $firmaPaciente = $this->dataUriFromStoredPath($consent->firma_paciente_path);
        $firmaMedico = $this->dataUriFromStoredPath($consent->firma_medico_path);

        if ($firmaPaciente) {
            $html = str_replace('${firma_paciente}', '<img style="max-height:80px;" src="'.$firmaPaciente.'">', $html);
            $html = str_replace('${firma_acompanante}', '<img style="max-height:80px;" src="'.$firmaPaciente.'">', $html);
        }
        if ($firmaMedico) {
            $html = str_replace('${firma_medico}', '<img style="max-height:80px;" src="'.$firmaMedico.'">', $html);
        }

        $genBy = Auth::user()?->name ?: 'Sistema';
        $meta = '<hr><small>Generado: '.now()->format('Y-m-d H:i:s').' · Usuario: '.e($genBy).'</small>';

        return '<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px;">'.$html.$meta.'</div>';
    }

    private function dataUriFromStoredPath(?string $storedPath): ?string
    {
        if (! $storedPath) {
            return null;
        }

        if (str_starts_with($storedPath, 'firmas_consntimientos/')) {
            $rel = substr($storedPath, strlen('firmas_consntimientos/'));
            if (! Storage::disk('firmas_consntimientos')->exists($rel)) {
                return null;
            }
            $bin = Storage::disk('firmas_consntimientos')->get($rel);

            return 'data:image/png;base64,'.base64_encode($bin);
        }

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
