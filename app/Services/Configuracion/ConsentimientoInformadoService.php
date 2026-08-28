<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConsentimientoInformado;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;

class ConsentimientoInformadoService extends BaseService
{
    /**
     * Constructor del servicio que inicializa el servicio de archivos.
     *
     * @param  FileServiceInterface  $fileService  Servicio encargado de gestionar el almacenamiento de archivos.
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function __construct(
        private readonly FileServiceInterface $fileService,
    ) {}

    /**
     * Obtiene la lista de consentimientos informados con sus relaciones.
     *
     * @returns array Arreglo con la lista de consentimientos y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(
            ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])->get()
        );
    }

    /**
     * Busca un consentimiento informado por su identificador.
     *
     * @param  int  $id  Identificador del consentimiento informado.
     *
     * @returns array Arreglo con el consentimiento encontrado o un mensaje de error.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function find(int $id): array
    {
        $item = ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])->find($id);

        if (! $item) {
            return $this->notFound('Consentimiento informado no encontrado');
        }

        return $this->success($item);
    }

    /**
     * Lista los consentimientos informados que se encuentran activos.
     *
     * @returns array Arreglo con los consentimientos activos y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivos(): array
    {
        return $this->success(
            ConsentimientoInformado::with(['producto', 'sede', 'usuarioCrea'])
                ->where('estado', true)
                ->get()
        );
    }

    /**
     * Crea un consentimiento informado y almacena su archivo si es proporcionado.
     *
     * @param  array  $data  Datos validados para la creación del consentimiento.
     * @param  UploadedFile|null  $archivo  Archivo adjunto opcional del consentimiento.
     *
     * @returns array Arreglo con el consentimiento creado y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Actualiza un consentimiento informado y reemplaza su archivo si es proporcionado.
     *
     * @param  int  $id  Identificador del consentimiento informado a actualizar.
     * @param  array  $data  Datos validados para la actualización del consentimiento.
     * @param  UploadedFile|null  $archivo  Archivo adjunto opcional a reemplazar.
     *
     * @returns array Arreglo con el consentimiento actualizado y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Elimina un consentimiento informado y su archivo asociado si existe.
     *
     * @param  int  $id  Identificador del consentimiento informado a eliminar.
     *
     * @returns array Arreglo con el resultado de la eliminación y su estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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
