<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoDocumentoMarca;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\Consentimientos\ConsentimientoPlantillaImagen;
use App\Services\BaseService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConsentimientoPlantillaImagenService extends BaseService
{
    private const DISK = 'imagenes_consentimientos';

    public function index(int $plantillaId): array
    {
        ConsentimientoPlantilla::query()->findOrFail($plantillaId);

        $imagenes = ConsentimientoPlantillaImagen::query()
            ->where('plantilla_id', $plantillaId)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return $this->success($imagenes);
    }

    public function store(int $plantillaId, UploadedFile $file, array $data): array
    {
        ConsentimientoPlantilla::query()->findOrFail($plantillaId);

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $filename = 'plantilla_'.$plantillaId.'_'.Str::random(24).'.'.$ext;
        $stored = Storage::disk(self::DISK)->put($filename, $file->get());
        if (! $stored) {
            return $this->error('No fue posible guardar la imagen', 500);
        }

        $imagen = ConsentimientoPlantillaImagen::create([
            'plantilla_id' => $plantillaId,
            'archivo' => self::DISK.'/'.$filename,
            'nombre' => (string) ($data['nombre'] ?? $file->getClientOriginalName()),
            'orden' => (int) ($data['orden'] ?? 0),
            'es_para_marcar' => (bool) ($data['es_para_marcar'] ?? false),
        ]);

        return $this->created($imagen);
    }

    /**
     * Devuelve la ruta relativa y disco para servir la imagen (stream).
     */
    public function localizar(int $id): array
    {
        $imagen = ConsentimientoPlantillaImagen::query()->findOrFail($id);

        $prefix = self::DISK.'/';
        $rel = str_starts_with($imagen->archivo, $prefix) ? substr($imagen->archivo, strlen($prefix)) : $imagen->archivo;

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk(self::DISK);
        if (! $disk->exists($rel)) {
            return $this->notFound('Imagen no encontrada');
        }

        $mime = str_ends_with(strtolower($rel), '.jpg') || str_ends_with(strtolower($rel), '.jpeg')
            ? 'image/jpeg'
            : 'image/png';

        return $this->success(['rel' => $rel, 'disk' => self::DISK, 'mime' => $mime]);
    }

    public function destroy(int $id): array
    {
        $imagen = ConsentimientoPlantillaImagen::query()->findOrFail($id);

        // No se puede eliminar si tiene marcas de consentimientos que la referencian
        $enUso = ConsentimientoDocumentoMarca::query()
            ->where('imagen_id', $id)
            ->exists();
        if ($enUso) {
            return $this->validationError(null, 'La imagen está en uso por marcas de consentimientos y no puede eliminarse');
        }

        $prefix = self::DISK.'/';
        $rel = str_starts_with($imagen->archivo, $prefix) ? substr($imagen->archivo, strlen($prefix)) : $imagen->archivo;
        try {
            Storage::disk(self::DISK)->delete($rel);
        } catch (\Throwable $e) {
            // continúa con el borrado lógico aunque el archivo no exista
        }

        $imagen->delete();

        return $this->deleted();
    }
}
