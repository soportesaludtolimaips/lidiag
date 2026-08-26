<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\Consentimientos\ConsentimientoDocumentoAdjunto;
use App\Models\Consentimientos\ConsentimientoDocumentoAuditoria;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConsentimientoAdjuntoService extends BaseService
{
    private const DISK = 'adjuntos_consentimientos';

    /** Límite total por consentimiento: 50 MB. */
    private const TOTAL_MAX_BYTES = 50 * 1024 * 1024;

    public function index(int $documentoId): array
    {
        ConsentimientoDocumento::query()->findOrFail($documentoId);

        $adjuntos = ConsentimientoDocumentoAdjunto::query()
            ->where('documento_id', $documentoId)
            ->with('usuarioSube:id,name')
            ->orderByDesc('id')
            ->get();

        return $this->success($adjuntos);
    }

    public function store(int $documentoId, UploadedFile $file, array $data, User $user, ?string $ip): array
    {
        $doc = ConsentimientoDocumento::query()->where('vigente', true)->findOrFail($documentoId);

        $usado = (int) ConsentimientoDocumentoAdjunto::query()->where('documento_id', $doc->id)->sum('tamano');
        if ($usado + $file->getSize() > self::TOTAL_MAX_BYTES) {
            return $this->validationError(null, 'Se superó el límite total de adjuntos (50 MB) para este consentimiento');
        }

        $filename = 'adj_'.$doc->id.'_'.Str::random(32).'.'.$file->getClientOriginalExtension().'.enc';
        $stored = Storage::disk(self::DISK)->put($filename, encrypt($file->get()));
        if (! $stored) {
            return $this->error('No fue posible guardar el adjunto', 500);
        }

        $adjunto = ConsentimientoDocumentoAdjunto::create([
            'documento_id' => $doc->id,
            'usuario_sube_id' => (int) $user->id,
            'nombre_original' => (string) $file->getClientOriginalName(),
            'archivo' => self::DISK.'/'.$filename,
            'tipo_mime' => (string) $file->getClientMimeType(),
            'tamano' => (int) $file->getSize(),
            'categoria' => (string) $data['categoria'],
        ]);

        $this->auditar($doc->id, $user->id, $ip, 'Adjunto subido: '.$adjunto->nombre_original);

        return $this->created($adjunto, 'Adjunto subido');
    }

    public function download(int $id): array
    {
        $adjunto = ConsentimientoDocumentoAdjunto::query()->findOrFail($id);

        $prefix = self::DISK.'/';
        $rel = str_starts_with($adjunto->archivo, $prefix) ? substr($adjunto->archivo, strlen($prefix)) : $adjunto->archivo;

        if (! Storage::disk(self::DISK)->exists($rel)) {
            return $this->notFound('Adjunto no encontrado');
        }

        $plain = decrypt(Storage::disk(self::DISK)->get($rel));
        $tmp = tempnam(sys_get_temp_dir(), 'adj_');
        file_put_contents($tmp, $plain);

        return $this->success([
            'tmp_path' => $tmp,
            'download_name' => $adjunto->nombre_original,
            'mime' => $adjunto->tipo_mime,
        ]);
    }

    public function destroy(int $id, User $user, ?string $ip): array
    {
        $adjunto = ConsentimientoDocumentoAdjunto::query()->findOrFail($id);
        $documentoId = (int) $adjunto->documento_id;

        $prefix = self::DISK.'/';
        $rel = str_starts_with($adjunto->archivo, $prefix) ? substr($adjunto->archivo, strlen($prefix)) : $adjunto->archivo;
        try {
            Storage::disk(self::DISK)->delete($rel);
        } catch (\Throwable $e) {
            // El archivo puede no existir; se continúa con el borrado lógico
        }

        $adjunto->delete();

        $this->auditar($documentoId, $user->id, $ip, 'Adjunto eliminado');

        return $this->deleted();
    }

    private function auditar(int $documentoId, int $usuarioId, ?string $ip, string $descripcion): void
    {
        ConsentimientoDocumentoAuditoria::create([
            'documento_id' => $documentoId,
            'usuario_id' => $usuarioId,
            'accion' => 'adjunto',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'descripcion' => $descripcion,
            'fecha_accion' => now(),
        ]);
    }
}
