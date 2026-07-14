<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConsentimientoInformado;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;

class ConsentimientoInformadoService extends BaseService
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {}

    public function all(): array
    {
        return $this->success(
            ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])->get()
        );
    }

    public function find(int $id): array
    {
        $item = ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])->find($id);

        if (! $item) {
            return $this->notFound('Consentimiento informado no encontrado');
        }

        return $this->success($item);
    }

    public function listarActivos(): array
    {
        return $this->success(
            ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])
                ->where('estado', true)
                ->get()
        );
    }

    public function create(array $data, ?UploadedFile $archivo = null): array
    {
        $item = new ConsentimientoInformado;
        $item->producto_id = $data['producto_id'];
        $item->sede_id = $data['sede_id'];
        $item->usuario_crea_id = $data['usuario_crea_id'];
        $item->nombre = $data['nombre'];
        $item->estado = filter_var($data['estado'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($archivo) {
            $info = $this->fileService->store($archivo, 'public', 'consentimiento_informado');
            $item->archivo = $info['path'];
        }

        $item->save();

        return $this->created($item->load(['producto', 'sede', 'usuarioCrea']));
    }

    public function update(int $id, array $data, ?UploadedFile $archivo = null): array
    {
        $item = ConsentimientoInformado::findOrFail($id);
        $item->producto_id = $data['producto_id'] ?? $item->producto_id;
        $item->sede_id = $data['sede_id'] ?? $item->sede_id;
        $item->usuario_crea_id = $data['usuario_crea_id'] ?? $item->usuario_crea_id;
        $item->nombre = $data['nombre'] ?? $item->nombre;

        if (isset($data['estado'])) {
            $item->estado = filter_var($data['estado'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($archivo) {
            $info = $this->fileService->replace('public', $item->archivo, $archivo, 'consentimiento_informado');
            $item->archivo = $info['path'];
        }

        $item->save();

        return $this->updated($item->load(['producto', 'sede', 'usuarioCrea']));
    }

    public function delete(int $id): array
    {
        $item = ConsentimientoInformado::findOrFail($id);

        if ($item->archivo) {
            $this->fileService->delete($item->archivo, 'public');
        }

        $item->delete();

        return $this->deleted();
    }
}
