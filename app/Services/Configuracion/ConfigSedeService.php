<?php

namespace App\Services\Configuracion;

use App\Models\Configuracion\ConfigSede;
use App\Services\BaseService;
use App\Services\Contracts\FileServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConfigSedeService extends BaseService
{
    /**
     * Constructor del servicio de sedes.
     *
     * Inicializa el servicio de archivos para la gestión de plantillas.
     *
     * @param  FileServiceInterface  $fileService  Servicio de archivos inyectado.
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
     * Obtiene todas las sedes con sus servicios y números de WhatsApp asociados.
     *
     * @returns array Respuesta con la lista de sedes y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function all(): array
    {
        return $this->success(
            ConfigSede::with(['servicios', 'whatsappNumbers'])->get()
        );
    }

    /**
     * Busca una sede por su identificador validando el ID y capturando errores.
     *
     * @param  int  $id  Identificador único de la sede.
     *
     * @returns array Respuesta con los datos de la sede o un error si no existe o el ID es inválido.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function buscarPorId(int $id): array
    {
        if ($id <= 0) {
            return $this->error('ID de sede inválido.', 400);
        }

        try {
            $item = ConfigSede::query()
                ->select([
                    'id',
                    'nom_sede',
                    'email_repor',
                    'estado',
                    'plantilla_rx',
                    'plantilla_ecografia',
                    'url_oviyam',
                    'tap_oviyam',
                    'usuario_oviyam',
                    'password_oviyam',
                    'interface_software',
                    'interface_driver',
                    'interface_host',
                    'interface_port',
                    'interface_database',
                    'interface_username',
                    'interface_password',
                    'ip_dcm',
                    'bd_dcm',
                    'puerto_dcm',
                    'usuario_dcm',
                    'password_dcm',
                ])
                ->find($id);

            if (! $item) {
                return $this->notFound('Sede no encontrada');
            }

            return $this->success($item);
        } catch (\Exception $e) {
            Log::error('Error al buscar sede por ID: '.$e->getMessage());

            return $this->error('Error al buscar la sede.', 500);
        }
    }

    /**
     * Lista únicamente las sedes que se encuentran activas.
     *
     * @returns array Respuesta con la lista de sedes activas y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function listarActivas(): array
    {
        return $this->success(ConfigSede::where('estado', 1)->get());
    }

    /**
     * Crea una nueva sede validando los datos y almacenando las plantillas de Rx si se envían.
     *
     * @param  array  $data  Datos validados de la sede.
     * @param  Illuminate\Http\UploadedFile|null  $plantillaRx  Archivo de plantilla Rx (opcional).
     * @param  Illuminate\Http\UploadedFile|null  $plantillaEcografia  Archivo de plantilla de ecografía (opcional).
     *
     * @returns array Respuesta con la sede creada y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function create(array $data, ?UploadedFile $plantillaRx = null, ?UploadedFile $plantillaEcografia = null): array
    {
        $validated = validator($data, [
            'nom_sede' => 'required|string|max:255',
            'email_repor' => 'nullable|string|max:255',
            'ip_dcm' => 'nullable|string|max:255',
            'bd_dcm' => 'nullable|string|max:255',
            'puerto_dcm' => 'nullable|string|max:255',
            'usuario_dcm' => 'nullable|string|max:255',
            'password_dcm' => 'nullable|string|max:255',
            'usuario_oviyam' => 'nullable|string|max:255',
            'password_oviyam' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:255',
            'tap_oviyam' => 'nullable|string|max:255',
            'encabezado' => 'nullable|string',
            'pie_pagina' => 'nullable|string',
            'logo_1' => 'nullable|string|max:255',
            'logo_2' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean',
            'interface_software' => 'nullable|string|max:255',
            'interface_driver' => 'nullable|string|max:255',
            'interface_host' => 'nullable|string|max:255',
            'interface_port' => 'nullable|string|max:255',
            'interface_database' => 'nullable|string|max:255',
            'interface_username' => 'nullable|string|max:255',
            'interface_password' => 'nullable|string|max:255',
        ], [
            'nom_sede.required' => 'El nombre de la sede es obligatorio.',
        ]);

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        $item = new ConfigSede;
        $this->fillSedeData($item, $validated->validated());

        if ($plantillaRx) {
            $info = $this->fileService->store($plantillaRx, 'plantillas_lecturas', 'plantillas_lecturas');
            $item->plantilla_rx = $info['path'];
        }

        if ($plantillaEcografia) {
            $info = $this->fileService->store($plantillaEcografia, 'plantillas_lecturas', 'plantillas_lecturas');
            $item->plantilla_ecografia = $info['path'];
        }

        $item->save();

        return $this->created($item);
    }

    /**
     * Actualiza una sede existente, reemplazando las plantillas si se envían nuevos archivos.
     *
     * @param  array  $data  Datos validados de la sede (debe incluir el id).
     * @param  Illuminate\Http\UploadedFile|null  $plantillaRx  Archivo de plantilla Rx (opcional).
     * @param  Illuminate\Http\UploadedFile|null  $plantillaEcografia  Archivo de plantilla de ecografía (opcional).
     *
     * @returns array Respuesta con la sede actualizada y el estado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function update(array $data, ?UploadedFile $plantillaRx = null, ?UploadedFile $plantillaEcografia = null): array
    {
        $id = $data['id'] ?? null;
        if (! $id) {
            return $this->error('El ID de la sede es obligatorio.', 400);
        }

        $validated = validator($data, [
            'nom_sede' => 'required|string|max:255',
            'email_repor' => 'nullable|string|max:255',
            'ip_dcm' => 'nullable|string|max:255',
            'bd_dcm' => 'nullable|string|max:255',
            'puerto_dcm' => 'nullable|string|max:255',
            'usuario_dcm' => 'nullable|string|max:255',
            'password_dcm' => 'nullable|string|max:255',
            'usuario_oviyam' => 'nullable|string|max:255',
            'password_oviyam' => 'nullable|string|max:255',
            'url_oviyam' => 'nullable|string|max:255',
            'tap_oviyam' => 'nullable|string|max:255',
            'encabezado' => 'nullable|string',
            'pie_pagina' => 'nullable|string',
            'logo_1' => 'nullable|string|max:255',
            'logo_2' => 'nullable|string|max:255',
            'estado' => 'nullable|boolean',
            'interface_software' => 'nullable|string|max:255',
            'interface_driver' => 'nullable|string|max:255',
            'interface_host' => 'nullable|string|max:255',
            'interface_port' => 'nullable|string|max:255',
            'interface_database' => 'nullable|string|max:255',
            'interface_username' => 'nullable|string|max:255',
            'interface_password' => 'nullable|string|max:255',
        ], [
            'nom_sede.required' => 'El nombre de la sede es obligatorio.',
        ]);

        if ($validated->fails()) {
            return $this->validationError($validated->errors());
        }

        $item = ConfigSede::findOrFail($id);
        $this->fillSedeData($item, $validated->validated());

        if ($plantillaRx) {
            $info = $this->fileService->replace('plantillas_lecturas', $item->plantilla_rx, $plantillaRx, 'plantillas_lecturas');
            $item->plantilla_rx = $info['path'];
        }

        if ($plantillaEcografia) {
            $info = $this->fileService->replace('plantillas_lecturas', $item->plantilla_ecografia, $plantillaEcografia, 'plantillas_lecturas');
            $item->plantilla_ecografia = $info['path'];
        }

        $item->save();

        return $this->updated($item);
    }

    /**
     * Elimina una sede existente y sus archivos de plantillas asociados.
     *
     * @param  int  $id  Identificador único de la sede a eliminar.
     *
     * @returns array Respuesta con el estado de la operación de eliminación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function delete(int $id): array
    {
        $item = ConfigSede::findOrFail($id);

        if ($item->plantilla_rx) {
            $this->fileService->delete($item->plantilla_rx, 'plantillas_lecturas');
        }

        if ($item->plantilla_ecografia) {
            $this->fileService->delete($item->plantilla_ecografia, 'plantillas_lecturas');
        }

        $item->delete();

        return $this->deleted();
    }

    /**
     * Actualiza la marca institucional de la sede (logo, encabezado y pie de página)
     * usada en los encabezados de los PDF. El logo se guarda en public/logos/.
     *
     * @param  int  $id  Identificador de la sede.
     * @param  array  $data  Datos con encabezado y/o pie_pagina.
     * @param  UploadedFile|null  $logo  Archivo de logo opcional.
     *
     * @returns array Envelope BaseService con la sede actualizada.
     *
     * @author Davide Rivera (Luis Castaño)
     *
     * @date 2026-08-29
     */
    public function actualizarBranding(int $id, array $data, ?UploadedFile $logo = null): array
    {
        $sede = ConfigSede::find($id);
        if (! $sede) {
            return $this->notFound('Sede no encontrada');
        }

        if ($logo) {
            $dir = public_path('logos');
            if (! is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $ext = strtolower($logo->getClientOriginalExtension() ?: 'png');
            $filename = 'sede_'.$sede->id.'_'.Str::random(10).'.'.$ext;
            $logo->move($dir, $filename);

            // Borra el logo anterior si existía y era propio de esta sede
            if ($sede->logo_1 && str_starts_with((string) $sede->logo_1, 'sede_'.$sede->id.'_') && is_file($dir.'/'.$sede->logo_1)) {
                @unlink($dir.'/'.$sede->logo_1);
            }
            $sede->logo_1 = $filename;
        }

        if (array_key_exists('encabezado', $data)) {
            $sede->encabezado = $data['encabezado'];
        }
        if (array_key_exists('pie_pagina', $data)) {
            $sede->pie_pagina = $data['pie_pagina'];
        }

        $sede->save();

        return $this->updated([
            'id' => $sede->id,
            'logo_1' => $sede->logo_1,
            'encabezado' => $sede->encabezado,
            'pie_pagina' => $sede->pie_pagina,
        ], 'Branding de la sede actualizado');
    }

    private function fillSedeData(ConfigSede $item, array $data): void
    {
        $fields = [
            'nom_sede',
            'email_repor',
            'ip_dcm',
            'bd_dcm',
            'puerto_dcm',
            'usuario_dcm',
            'password_dcm',
            'usuario_oviyam',
            'password_oviyam',
            'url_oviyam',
            'tap_oviyam',
            'encabezado',
            'pie_pagina',
            'logo_1',
            'logo_2',
            'estado',
            'interface_software',
            'interface_driver',
            'interface_host',
            'interface_port',
            'interface_database',
            'interface_username',
            'interface_password',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                if (in_array($field, ['estado'], true)) {
                    $item->$field = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
                } else {
                    $item->$field = $data[$field];
                }
            }
        }
    }
}
