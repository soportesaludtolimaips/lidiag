<?php

namespace App\Services\Consentimientos;

use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\Consentimientos\ConsentimientoDocumentoAuditoria;
use App\Models\Consentimientos\ConsentimientoDocumentoMarca;
use App\Models\User;
use App\Services\BaseService;

class ConsentimientoMarcaService extends BaseService
{
    public function index(int $documentoId): array
    {
        ConsentimientoDocumento::query()->findOrFail($documentoId);

        $marcas = ConsentimientoDocumentoMarca::query()
            ->where('documento_id', $documentoId)
            ->with('usuarioMarca:id,name')
            ->orderBy('id')
            ->get();

        return $this->success($marcas);
    }

    public function store(int $documentoId, array $data, User $user, ?string $ip): array
    {
        $doc = ConsentimientoDocumento::query()->where('vigente', true)->findOrFail($documentoId);

        if (in_array($doc->estado, ['COMPLETO', 'ANULADO', 'EXPIRADO'], true)) {
            return $this->validationError(null, 'El consentimiento no admite nuevas marcas');
        }

        $marca = ConsentimientoDocumentoMarca::create([
            'documento_id' => $doc->id,
            'imagen_id' => ! empty($data['imagen_id']) ? (int) $data['imagen_id'] : null,
            'usuario_marca_id' => (int) $user->id,
            'tipo_marca' => (string) $data['tipo_marca'],
            'posicion_x' => (float) $data['posicion_x'],
            'posicion_y' => (float) $data['posicion_y'],
            'ancho' => isset($data['ancho']) ? (float) $data['ancho'] : null,
            'alto' => isset($data['alto']) ? (float) $data['alto'] : null,
            'descripcion' => $data['descripcion'] ?? null,
            'datos_dibujo' => $data['datos_dibujo'] ?? null,
            'fecha_marca' => now(),
        ]);

        $this->auditar($doc->id, $user->id, $ip, 'Marca agregada ('.$marca->tipo_marca.')');

        return $this->created($marca, 'Marca agregada');
    }

    public function update(int $id, array $data, User $user, ?string $ip): array
    {
        $marca = ConsentimientoDocumentoMarca::query()->findOrFail($id);

        $marca->fill([
            'tipo_marca' => $data['tipo_marca'] ?? $marca->tipo_marca,
            'posicion_x' => isset($data['posicion_x']) ? (float) $data['posicion_x'] : $marca->posicion_x,
            'posicion_y' => isset($data['posicion_y']) ? (float) $data['posicion_y'] : $marca->posicion_y,
            'ancho' => array_key_exists('ancho', $data) ? ($data['ancho'] !== null ? (float) $data['ancho'] : null) : $marca->ancho,
            'alto' => array_key_exists('alto', $data) ? ($data['alto'] !== null ? (float) $data['alto'] : null) : $marca->alto,
            'descripcion' => array_key_exists('descripcion', $data) ? $data['descripcion'] : $marca->descripcion,
            'datos_dibujo' => array_key_exists('datos_dibujo', $data) ? $data['datos_dibujo'] : $marca->datos_dibujo,
        ]);
        $marca->save();

        $this->auditar((int) $marca->documento_id, $user->id, $ip, 'Marca editada');

        return $this->updated($marca);
    }

    public function destroy(int $id, User $user, ?string $ip): array
    {
        $marca = ConsentimientoDocumentoMarca::query()->findOrFail($id);
        $documentoId = (int) $marca->documento_id;
        $marca->delete();

        $this->auditar($documentoId, $user->id, $ip, 'Marca eliminada');

        return $this->deleted();
    }

    private function auditar(int $documentoId, int $usuarioId, ?string $ip, string $descripcion): void
    {
        ConsentimientoDocumentoAuditoria::create([
            'documento_id' => $documentoId,
            'usuario_id' => $usuarioId,
            'accion' => 'marcar',
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'descripcion' => $descripcion,
            'fecha_accion' => now(),
        ]);
    }
}
