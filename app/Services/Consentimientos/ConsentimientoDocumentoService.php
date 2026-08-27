<?php

namespace App\Services\Consentimientos;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigSede;
use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\Consentimientos\ConsentimientoDocumentoAuditoria;
use App\Models\Consentimientos\ConsentimientoDocumentoToken;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\ConsentimientoPlantillaProducto;
use App\Models\General\Paciente;
use App\Models\User;
use App\Services\BaseService;
use Dompdf\Dompdf;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConsentimientoDocumentoService extends BaseService
{
    /** Vida por defecto del documento (reloj de expiración): 24 horas. */
    private const DOCUMENTO_TTL_MINUTOS = 24 * 60;

    /** Vida por defecto del enlace de firma del paciente. */
    private const TOKEN_TTL_MINUTOS = 10;

    private const FIRMAS_DISK = 'firmas_consntimientos';

    private const PDF_DISK = 'files_consentimientos';

    public function index(array $filters = []): array
    {
        $query = ConsentimientoDocumento::query()
            ->with(['producto:id,cod_cups,nom_produc', 'sede:id,nom_sede', 'medico:id,name'])
            ->where('vigente', true);

        if (! empty($filters['sede_id'])) {
            $query->where('sede_id', (int) $filters['sede_id']);
        }
        if (! empty($filters['estado'])) {
            $query->where('estado', (string) $filters['estado']);
        }
        if (! empty($filters['desde'])) {
            $query->whereDate('created_at', '>=', $filters['desde']);
        }
        if (! empty($filters['hasta'])) {
            $query->whereDate('created_at', '<=', $filters['hasta']);
        }
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($w) use ($q) {
                $w->where('num_docu', 'like', "%{$q}%")
                    ->orWhere('nombres', 'like', "%{$q}%")
                    ->orWhere('apellidos', 'like', "%{$q}%");
            });
        }

        return $this->success($query->orderByDesc('id')->get());
    }

    /**
     * Busca un paciente por número de documento en la tabla de pacientes,
     * para prellenar los datos del consentimiento sin retipear.
     */
    public function buscarPaciente(string $numDocu): array
    {
        $p = Paciente::query()->where('num_docu', $numDocu)->first();
        if (! $p) {
            return $this->notFound('Paciente no encontrado');
        }

        return $this->success([
            'num_docu' => $p->num_docu,
            'nombres' => trim((string) ($p->nombres ?? '').' '.(string) ($p->seg_nom ?? '')),
            'apellidos' => trim((string) ($p->pri_ape ?? '').' '.(string) ($p->seg_ape ?? '')),
            'sexo' => $p->sexo,
            'fec_naci' => $p->fec_naci,
        ]);
    }

    /**
     * Lista de médicos (rol Medico) para asignar en la generación.
     *
     * @return array<string, mixed>
     */
    public function medicos(): array
    {
        $medicos = User::query()
            ->role('Medico')
            ->orderBy('name')
            ->get(['id', 'name']);

        return $this->success($medicos);
    }

    public function show(int $id): array
    {
        $doc = ConsentimientoDocumento::query()
            ->with(['producto', 'sede', 'medico', 'generadoPor', 'marcas', 'adjuntos'])
            ->findOrFail($id);

        return $this->success($doc);
    }

    /**
     * Genera un documento de consentimiento (estado GENERADO) con snapshot
     * inmutable de la plantilla vigente para el producto/sede.
     */
    public function generar(array $data, User $user, ?string $ip): array
    {
        $productoId = (int) $data['producto_id'];
        $sedeId = ! empty($data['sede_id']) ? (int) $data['sede_id'] : null;

        [$plantilla, $requiereFirmaMedico] = $this->resolverPlantilla($productoId, $sedeId);
        if (! $plantilla) {
            return $this->validationError(null, 'El producto no tiene plantilla de consentimiento configurada');
        }

        return $this->transaction(function () use ($data, $productoId, $sedeId, $plantilla, $requiereFirmaMedico, $user, $ip) {
            $doc = ConsentimientoDocumento::create([
                'origen_tipo' => (string) ($data['origen_tipo'] ?? 'MANUAL'),
                'origen_id' => ! empty($data['origen_id']) ? (int) $data['origen_id'] : null,
                'producto_id' => $productoId,
                'sede_id' => $sedeId,
                'config_modulo_id' => (int) $plantilla->config_modulo_id,
                'plantilla_id' => $plantilla->id,
                'plantilla_version' => (int) $plantilla->version,
                'plantilla_html' => (string) $plantilla->html,
                'num_docu' => (string) $data['num_docu'],
                'nombres' => (string) $data['nombres'],
                'apellidos' => (string) ($data['apellidos'] ?? ''),
                'medico_id' => ! empty($data['medico_id']) ? (int) $data['medico_id'] : null,
                'vigente' => true,
                'vigente_key' => 1,
                'estado' => 'GENERADO',
                'requiere_firma_medico' => $requiereFirmaMedico,
                'fecha_expiracion' => now()->addMinutes(self::DOCUMENTO_TTL_MINUTOS),
                'generado_por_id' => (int) $user->id,
                'ip_generado' => $ip,
            ]);

            $doc->contenido_renderizado = $this->renderHtml($doc);
            $doc->save();

            $this->auditar($doc->id, 'create', 'Documento generado', $user->id, $ip);

            return $this->created([
                'consentimiento_id' => $doc->id,
                'estado' => $doc->estado,
                'fecha_expiracion' => $doc->fecha_expiracion,
            ], 'Consentimiento generado');
        });
    }

    /**
     * Crea un enlace/token de firma para el paciente o acompañante.
     * Solo se guarda el hash SHA-256 del token.
     */
    public function crearToken(array $data, User $user, ?string $ip): array
    {
        $doc = ConsentimientoDocumento::query()
            ->where('id', (int) $data['documento_id'])
            ->where('vigente', true)
            ->firstOrFail();

        if (in_array($doc->estado, ['ANULADO', 'EXPIRADO', 'COMPLETO'], true)) {
            return $this->validationError(null, 'El consentimiento no admite nuevas firmas');
        }
        if ($this->estaExpirado($doc)) {
            $this->marcarExpirado($doc);

            return $this->error('El consentimiento expiró', 410);
        }

        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);
        $minutos = ! empty($data['minutos']) ? (int) $data['minutos'] : self::TOKEN_TTL_MINUTOS;
        $expiresAt = now()->addMinutes($minutos);

        return $this->transaction(function () use ($doc, $data, $token, $tokenHash, $expiresAt, $user, $ip) {
            // Desactiva tokens previos aún activos del documento
            ConsentimientoDocumentoToken::query()
                ->where('documento_id', $doc->id)
                ->where('activo', true)
                ->update(['activo' => false]);

            $tipo = (string) $data['firmante_tipo'];
            $esAcomp = $tipo === 'ACOMPANANTE';
            $acomp = (array) ($data['acompanante'] ?? []);

            $doc->firmante_tipo = $tipo;
            $doc->acompanante_cedula = $esAcomp ? (string) ($acomp['cedula'] ?? '') : null;
            $doc->acompanante_nombres = $esAcomp ? (string) ($acomp['nombres'] ?? '') : null;
            $doc->acompanante_email = $esAcomp ? (string) ($acomp['email'] ?? '') : null;
            $doc->acompanante_direccion = $esAcomp ? (string) ($acomp['direccion'] ?? '') : null;
            $doc->acompanante_telefono = $esAcomp ? (string) ($acomp['telefono'] ?? '') : null;
            $doc->estado = 'PENDIENTE_PACIENTE';
            $doc->save();

            ConsentimientoDocumentoToken::create([
                'documento_id' => $doc->id,
                'token_hash' => $tokenHash,
                'usos_maximos' => ! empty($data['usos_maximos']) ? (int) $data['usos_maximos'] : 1,
                'usos_actuales' => 0,
                'expires_at' => $expiresAt,
                'ip_generacion' => $ip,
                'activo' => true,
                'generado_por_id' => (int) $user->id,
            ]);

            $this->auditar($doc->id, 'generar_token', 'Token de firma generado', $user->id, $ip);

            return $this->success([
                'ok' => true,
                'consentimiento_id' => $doc->id,
                'url' => url("/firma/consentimiento/{$token}"),
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function infoPorToken(string $token): array
    {
        [$tokenRow, $doc, $err] = $this->resolverToken($token);
        if ($err) {
            return $err;
        }

        $html = $this->renderHtml($doc);
        $html = str_replace(
            ['${firma_paciente}', '${firma_acompanante}', '${firma_acompañante}', '${firma_medico}'],
            '<div style="border-bottom:1px solid #999; height:50px;"></div>',
            $html
        );

        return $this->success([
            'ok' => true,
            'consentimiento_id' => $doc->id,
            'firmante_tipo' => $doc->firmante_tipo,
            'producto' => [
                'id' => $doc->producto?->id,
                'nombre' => $doc->producto?->nom_produc,
                'cod_cups' => $doc->producto?->cod_cups,
            ],
            'paciente' => [
                'num_docu' => $doc->num_docu,
                'nombres' => trim($doc->nombres.' '.$doc->apellidos),
            ],
            'html' => $html,
            'expires_at' => $tokenRow->expires_at,
        ]);
    }

    public function firmarPacientePorToken(string $token, array $data, ?string $ip): array
    {
        [$tokenRow, $doc, $err] = $this->resolverToken($token);
        if ($err) {
            return $err;
        }

        if ($doc->estado !== 'PENDIENTE_PACIENTE') {
            return $this->validationError(null, 'El consentimiento no está pendiente de firma de paciente');
        }

        $decoded = $this->decodePngDataUrl($data['firma_png'] ?? '');
        if (is_array($decoded)) {
            return $decoded; // error de validación
        }

        $prefix = $doc->firmante_tipo === 'ACOMPANANTE' ? 'Acompanante_' : 'Paciente_';
        $filename = $prefix.Str::random(40).'.png.enc';

        $stored = Storage::disk(self::FIRMAS_DISK)->put($filename, encrypt($decoded));
        if (! $stored) {
            return $this->error('No fue posible guardar la firma', 500);
        }

        return $this->transaction(function () use ($doc, $tokenRow, $filename, $ip) {
            $doc->firma_paciente_path = self::FIRMAS_DISK.'/'.$filename;
            $doc->firma_paciente_at = now();
            $doc->ip_firma_paciente = $ip;
            $doc->estado = $doc->requiere_firma_medico ? 'PENDIENTE_MEDICO' : 'COMPLETO';
            $doc->save();

            $tokenRow->usos_actuales = $tokenRow->usos_actuales + 1;
            $tokenRow->used_at = now();
            $tokenRow->ip_uso = $ip;
            if ($tokenRow->usos_actuales >= $tokenRow->usos_maximos) {
                $tokenRow->activo = false;
            }
            $tokenRow->save();

            $this->auditar($doc->id, 'firma_paciente', 'Firma del paciente/acompañante registrada', null, $ip);

            return $this->success(['ok' => true, 'message' => 'Firma registrada correctamente']);
        });
    }

    public function firmarMedico(array $data, User $user, ?string $ip): array
    {
        if (! $user->firma) {
            return $this->validationError(null, 'Debe cargar firma en su perfil');
        }

        $doc = ConsentimientoDocumento::query()
            ->where('id', (int) $data['documento_id'])
            ->where('vigente', true)
            ->firstOrFail();

        if ($doc->medico_id !== null && (int) $doc->medico_id !== (int) $user->id) {
            return $this->unauthorized('Este consentimiento está asignado a otro médico');
        }

        if ($doc->estado !== 'PENDIENTE_MEDICO') {
            return $this->validationError(null, 'El consentimiento no está pendiente de firma de médico');
        }

        $res = $this->transaction(function () use ($doc, $user, $ip) {
            $doc->medico_id = (int) $user->id;
            $doc->firma_medico_path = (string) $user->firma;
            $doc->firma_medico_at = now();
            $doc->ip_firma_medico = $ip;
            $doc->estado = 'COMPLETO';
            $doc->save();

            $this->auditar($doc->id, 'firma_medico', 'Firma del médico registrada', $user->id, $ip);

            return $this->success(['ok' => true]);
        });

        if ($res['status'] !== 200) {
            return $res;
        }

        // Genera el PDF final tras completar (fuera de la transacción, ya comprometida)
        $this->generarPdf($doc->id);

        return $res;
    }

    public function anular(int $id, array $data, User $user, ?string $ip): array
    {
        $doc = ConsentimientoDocumento::query()->where('id', $id)->where('vigente', true)->firstOrFail();

        if ($doc->estado === 'ANULADO') {
            return $this->validationError(null, 'El consentimiento ya está anulado');
        }

        $doc->estado = 'ANULADO';
        $doc->anulada_por_id = (int) $user->id;
        $doc->anulada_at = now();
        $doc->anulacion_observacion = (string) $data['observacion'];
        $doc->vigente = false;
        $doc->vigente_key = null;
        $doc->save();

        $this->auditar($doc->id, 'anular', (string) $data['observacion'], $user->id, $ip);

        return $this->success(['ok' => true]);
    }

    public function generarPdf(int $id): array
    {
        $doc = ConsentimientoDocumento::query()
            ->with(['producto', 'medico', 'sede'])
            ->where('id', $id)
            ->where('vigente', true)
            ->firstOrFail();

        if ($doc->estado !== 'COMPLETO') {
            return $this->validationError(null, 'Consentimiento incompleto');
        }
        if (! $doc->firma_paciente_path) {
            return $this->validationError(null, 'Falta la firma del paciente para generar PDF');
        }
        if ($doc->requiere_firma_medico && ! $doc->firma_medico_path) {
            return $this->validationError(null, 'Falta la firma del médico para generar PDF');
        }

        $html = $this->renderHtmlForPdf($doc);
        $output = $this->renderPdfBinary($html);

        $numDoc = (string) ($doc->num_docu ?: 'NA');
        $cod = (string) ($doc->producto?->cod_cups ?? 'NA');
        $filename = "Consentimiento_{$numDoc}_{$cod}_".time().'_'.uniqid().'.pdf.enc';

        Storage::disk(self::PDF_DISK)->put($filename, encrypt($output));

        $doc->pdf_path = self::PDF_DISK.'/'.$filename;
        $doc->pdf_generated_at = now();
        $doc->save();

        $this->auditar($doc->id, 'exportar', 'PDF generado', Auth::id(), null);

        return $this->success(['ok' => true, 'pdf_path' => $doc->pdf_path]);
    }

    public function descargarPdf(int $id): array
    {
        $doc = ConsentimientoDocumento::query()->where('id', $id)->where('vigente', true)->firstOrFail();

        if (! $doc->pdf_path) {
            return $this->notFound('PDF no generado');
        }

        $prefix = self::PDF_DISK.'/';
        $rel = str_starts_with($doc->pdf_path, $prefix) ? substr($doc->pdf_path, strlen($prefix)) : $doc->pdf_path;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::PDF_DISK);
        if (! $disk->exists($rel)) {
            return $this->notFound('PDF no encontrado');
        }

        // Los PDF están cifrados en disco: se descifran a un temporal para servir
        $plain = decrypt($disk->get($rel));
        $tmp = tempnam(sys_get_temp_dir(), 'consent_').'.pdf';
        file_put_contents($tmp, $plain);

        return $this->success(['tmp_path' => $tmp, 'download_name' => 'Consentimiento_'.$doc->num_docu.'.pdf']);
    }

    // ───────────────────────── helpers ─────────────────────────

    /**
     * Resuelve la plantilla aplicable para (producto, sede).
     * Prioriza la asignación N:M; cae a la plantilla vigente del producto.
     *
     * @return array{0: ?ConsentimientoPlantilla, 1: bool}
     */
    private function resolverPlantilla(int $productoId, ?int $sedeId): array
    {
        $asignacion = ConsentimientoPlantillaProducto::query()
            ->where('producto_id', $productoId)
            ->when($sedeId, fn ($q) => $q->where(fn ($w) => $w->where('sede_id', $sedeId)->orWhereNull('sede_id')))
            ->orderByRaw('sede_id IS NULL') // prioriza coincidencia exacta de sede
            ->first();

        if ($asignacion) {
            $plantilla = ConsentimientoPlantilla::query()
                ->where('id', $asignacion->plantilla_id)
                ->where('vigente', true)
                ->first();
            if ($plantilla) {
                return [$plantilla, (bool) $asignacion->requiere_firma_medico];
            }
        }

        $plantilla = ConsentimientoPlantilla::query()
            ->where('producto_id', $productoId)
            ->where('vigente', true)
            ->first();

        return [$plantilla, true];
    }

    /**
     * @return array{0: ?ConsentimientoDocumentoToken, 1: ?ConsentimientoDocumento, 2: ?array}
     */
    private function resolverToken(string $token): array
    {
        $tokenHash = hash('sha256', $token);

        $tokenRow = ConsentimientoDocumentoToken::query()
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $tokenRow) {
            return [null, null, $this->notFound('Token inválido o expirado')];
        }
        if (! $tokenRow->activo || $tokenRow->usos_actuales >= $tokenRow->usos_maximos) {
            return [null, null, $this->error('Este enlace ya fue usado', 410)];
        }
        if (! $tokenRow->expires_at || now()->greaterThan($tokenRow->expires_at)) {
            return [null, null, $this->error('Token expirado', 410)];
        }

        $doc = ConsentimientoDocumento::query()
            ->with(['producto'])
            ->where('id', $tokenRow->documento_id)
            ->where('vigente', true)
            ->first();

        if (! $doc) {
            return [null, null, $this->notFound('Consentimiento no encontrado')];
        }
        if ($this->estaExpirado($doc)) {
            $this->marcarExpirado($doc);

            return [null, null, $this->error('El consentimiento expiró', 410)];
        }

        return [$tokenRow, $doc, null];
    }

    private function decodePngDataUrl(string $dataUrl): string|array
    {
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
            if ($img) {
                imagedestroy($img);
            }

            return $this->validationError(null, 'Dimensiones excedidas (max 2048x2048).');
        }
        imagedestroy($img);

        return $decoded;
    }

    private function estaExpirado(ConsentimientoDocumento $doc): bool
    {
        return $doc->fecha_expiracion
            && now()->greaterThan($doc->fecha_expiracion)
            && ! in_array($doc->estado, ['COMPLETO', 'ANULADO'], true);
    }

    private function marcarExpirado(ConsentimientoDocumento $doc): void
    {
        $doc->estado = 'EXPIRADO';
        $doc->save();
        $this->auditar($doc->id, 'restaurar', 'Documento marcado como expirado', null, null);
    }

    private function auditar(int $documentoId, string $accion, ?string $descripcion, ?int $usuarioId, ?string $ip): void
    {
        ConsentimientoDocumentoAuditoria::create([
            'documento_id' => $documentoId,
            'usuario_id' => $usuarioId,
            'accion' => $accion,
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'descripcion' => $descripcion,
            'fecha_accion' => now(),
        ]);
    }

    private function renderHtml(ConsentimientoDocumento $doc): string
    {
        $html = (string) $doc->plantilla_html;
        $producto = $doc->producto ?? ConfigProducto::find($doc->producto_id);
        $medico = $doc->medico;

        $repl = [
            '${num_docu}' => (string) $doc->num_docu,
            '${nombres}' => (string) $doc->nombres,
            '${apellidos}' => (string) $doc->apellidos,
            '${servicio}' => (string) ($producto?->nom_produc ?? ''),
            '${producto}' => (string) ($producto?->nom_produc ?? ''),
            '${cod_producto}' => (string) ($producto?->cod_cups ?? ''),
            '${fecha}' => now()->format('Y-m-d'),
            '${hora}' => now()->format('H:i'),
            '${nombre_medico}' => (string) ($medico?->name ?? ''),
            '${observaciones}' => '',
        ];

        return str_replace(array_keys($repl), array_values($repl), $html);
    }

    /**
     * Genera el binario PDF. Usa el wrapper de barryvdh/laravel-dompdf si está
     * disponible (paridad con el resto del proyecto); si no, cae al Dompdf nativo
     * (dompdf/dompdf), que es la dependencia declarada directamente.
     */
    private function renderPdfBinary(string $html): string
    {
        if (app()->bound('dompdf.wrapper')) {
            $pdf = app('dompdf.wrapper');
            $pdf->loadHTML($html)->setPaper('a4', 'portrait');

            return $pdf->output();
        }

        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('a4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    private function renderHtmlForPdf(ConsentimientoDocumento $doc): string
    {
        $html = $this->renderHtml($doc);

        $firmaPaciente = $this->dataUriFromStoredPath($doc->firma_paciente_path);
        $firmaMedico = $this->dataUriFromStoredPath($doc->firma_medico_path);

        // Firmas más bajas y separadas del contenido
        if ($firmaPaciente) {
            $img = '<img style="max-height:80px;" src="'.$firmaPaciente.'">';
            $html = str_replace(['${firma_paciente}', '${firma_acompanante}', '${firma_acompañante}'], $img, $html);
        }
        if ($firmaMedico) {
            $html = str_replace('${firma_medico}', '<img style="max-height:80px;" src="'.$firmaMedico.'">', $html);
        }

        $sede = $doc->sede;
        $encabezado = $this->encabezadoSedeHtml($sede);
        $pie = $sede && $sede->pie_pagina
            ? '<div style="margin-top:10px; text-align:center; color:#555; font-size:10px;">'.nl2br(e((string) $sede->pie_pagina)).'</div>'
            : '';

        $genBy = Auth::user()?->name ?: 'Sistema';
        $meta = '<hr><small>Generado: '.now()->format('Y-m-d H:i:s').' · Usuario: '.e($genBy).'</small>';

        return '<div style="font-family: DejaVu Sans, sans-serif; font-size: 12px;">'
            .$encabezado
            .'<div style="margin-top:12px;">'.$html.'</div>'
            .'<div style="margin-top:70px;"></div>' // empuja las firmas/pie más abajo
            .$pie
            .$meta
            .'</div>';
    }

    /**
     * Encabezado del PDF con el logo (public/logos/logo_1) e información de la
     * sede (texto libre `encabezado`). Convención existente del proyecto.
     */
    private function encabezadoSedeHtml(?ConfigSede $sede): string
    {
        if (! $sede) {
            return '';
        }

        $logo = $this->dataUriLogoSede($sede->logo_1);
        $logoCol = $logo
            ? '<td style="width:120px; vertical-align:middle;"><img style="max-height:70px; max-width:110px;" src="'.$logo.'"></td>'
            : '';

        $info = array_filter([
            '<strong style="font-size:14px;">'.e((string) $sede->nom_sede).'</strong>',
            $sede->encabezado ? nl2br(e((string) $sede->encabezado)) : null,
        ]);

        return '<table style="width:100%; border-bottom:2px solid #0d6efd; padding-bottom:6px;"><tr>'
            .$logoCol
            .'<td style="vertical-align:middle; line-height:1.4;">'.implode('<br>', $info).'</td>'
            .'</tr></table>';
    }

    /**
     * Lee un logo de sede (archivo en public/logos/) y lo devuelve como data URI
     * para incrustarlo en el PDF (dompdf).
     */
    private function dataUriLogoSede(?string $logo): ?string
    {
        if (! $logo) {
            return null;
        }

        $ruta = is_file(public_path('logos/'.$logo)) ? public_path('logos/'.$logo)
            : (is_file(public_path($logo)) ? public_path($logo)
            : (is_file($logo) ? $logo : null));

        if (! $ruta) {
            return null;
        }

        $bin = @file_get_contents($ruta);
        if ($bin === false) {
            return null;
        }
        $ext = strtolower(pathinfo($ruta, PATHINFO_EXTENSION) ?: 'png');
        $mime = in_array($ext, ['jpg', 'jpeg'], true) ? 'image/jpeg' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($bin);
    }

    private function dataUriFromStoredPath(?string $storedPath): ?string
    {
        if (! $storedPath) {
            return null;
        }

        if (str_starts_with($storedPath, self::FIRMAS_DISK.'/')) {
            $rel = substr($storedPath, strlen(self::FIRMAS_DISK.'/'));
            if (! Storage::disk(self::FIRMAS_DISK)->exists($rel)) {
                return null;
            }
            $bin = decrypt(Storage::disk(self::FIRMAS_DISK)->get($rel));

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
}
