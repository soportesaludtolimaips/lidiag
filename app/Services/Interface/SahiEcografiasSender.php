<?php

namespace App\Services\Interface;

use App\Models\Ecografias\EcografiaProducto;
use App\Models\Interface\Sahi\ASI_USUA;
use App\Models\Interface\Sahi\Atencion;
use App\Models\Interface\Sahi\HceEsquema;
use App\Models\Interface\Sahi\hceEsquemaDetMemo;
use App\Models\Interface\Sahi\hceEsquemaDetProc;
use App\Models\Interface\Sahi\hceEsquemaEncabeza;
use App\Models\Interface\Sahi\hceEsquemasdeAte;
use App\Models\Interface\Sahi\ProProducto;
use App\Models\Interface\Sahi\sisTabla;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SahiEcografiasSender
{
    /**
     * Envía un `ecografias_productos.id` a SAHI con idempotencia local.
     *
     * @return array{ok:bool,status:int,message:string,data?:array}
     */
    public function send(int $ecografiaProductoId): array
    {
        /** @var EcografiaProducto|null $ecoProd */
        $ecoProd = DB::transaction(function () use ($ecografiaProductoId) {
            return EcografiaProducto::where('id', $ecografiaProductoId)
                ->lockForUpdate()
                ->with(['ecografia.medico'])
                ->first();
        });

        if (!$ecoProd) {
            return ['ok' => false, 'status' => 404, 'message' => 'No se encontró el producto de ecografía en LIDiag.'];
        }

        // Idempotencia local
        if ($ecoProd->estado_envio_sahi === 'ENVIADO') {
            return [
                'ok' => true,
                'status' => 200,
                'message' => 'La ecografía ya fue enviada a SAHI.',
                'data' => [
                    'ecografia_producto_id' => $ecoProd->id,
                    'sahi_esquema_149' => $ecoProd->sahi_esquema_149,
                    'sahi_esquema_150' => $ecoProd->sahi_esquema_150,
                    'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
                ],
            ];
        }

        // Marcar ENVIANDO (si no lo está)
        DB::transaction(function () use ($ecoProd) {
            EcografiaProducto::where('id', $ecoProd->id)->update([
                'estado_envio_sahi' => 'ENVIANDO',
                'error_envio_sahi' => null,
            ]);
        });

        $ecografia = $ecoProd->ecografia;
        if (!$ecografia) {
            $this->markEnvioError($ecoProd->id, 'No se pudo resolver la ecografía asociada al producto.');
            return ['ok' => false, 'status' => 422, 'message' => 'No se pudo resolver la ecografía asociada al producto.'];
        }

        $atencionId = $ecografia->atencion_id;
        if (!$atencionId) {
            $this->markEnvioError($ecoProd->id, 'La ecografía no tiene atencion_id para enviar a SAHI.');
            return ['ok' => false, 'status' => 422, 'message' => 'La ecografía no tiene atencion_id para enviar a SAHI.'];
        }

        $medico = $ecografia->medico;
        if (!$medico || !$medico->interface_id) {
            $this->markEnvioError($ecoProd->id, 'El médico no tiene interface_id (SAHI) configurado. Verifique el usuario.');
            return ['ok' => false, 'status' => 422, 'message' => 'El médico no tiene interface_id (SAHI) configurado. Verifique el usuario.'];
        }

        $productoId = $ecoProd->producto_id;
        if (!$productoId) {
            $this->markEnvioError($ecoProd->id, 'El producto de ecografía no tiene producto_id (IdProducto SAHI) para enviar.');
            return ['ok' => false, 'status' => 422, 'message' => 'El producto de ecografía no tiene producto_id (IdProducto SAHI) para enviar.'];
        }

        $fecRegistro = Carbon::now()->format('Y-m-d H:i:s');

        $lecturaPlano = $this->normalizeHtmlToText($ecoProd->lectura ?? '');
        $opinionPlano = $this->normalizeHtmlToText($ecoProd->opinion ?? '');
        $memo = trim($lecturaPlano);
        if ($opinionPlano !== '') {
            $memo .= "\n\nOpinion:\n" . trim($opinionPlano);
        }

        try {
            $result = DB::connection('sqlsrv')->transaction(function () use ($medico, $atencionId, $productoId, $fecRegistro, $memo) {
                // Bloqueo por clave (SQL Server) para evitar duplicados por carreras entre instancias/procesos.
                // LockOwner=Transaction asegura liberación automática al commit/rollback.
                $lockKey = "LIDiag:SAHI:Atencion:{$atencionId}:Producto:{$productoId}";
                $lockRow = DB::connection('sqlsrv')->selectOne(
                    "EXEC sp_getapplock @Resource = ?, @LockMode = 'Exclusive', @LockOwner = 'Transaction', @LockTimeout = 10000;",
                    [$lockKey]
                );
                $lockResult = null;
                if (is_object($lockRow)) {
                    // El SP devuelve una columna (a veces sin nombre). Tomamos el primer valor.
                    $vals = array_values(get_object_vars($lockRow));
                    $lockResult = $vals[0] ?? null;
                }
                if ($lockResult === null || (int) $lockResult < 0) {
                    return [
                        'ok' => false,
                        'status' => 409,
                        'message' => 'No fue posible obtener lock para el envío a SAHI (posible envío concurrente). Reintente.',
                    ];
                }

                // Idempotencia "real" contra SAHI:
                // si ya existe el esquema 150 (producto) para la misma atención, no reinsertamos.
                $existing = $this->findExistingEsquema150ForAtencionProducto($atencionId, $productoId);
                if ($existing) {
                    return [
                        'ok' => true,
                        'status' => 200,
                        'message' => 'Ya existía un registro en SAHI para esta atención/producto (no se reinsertó).',
                        'data' => [
                            'IdEsquemaEncabezado149' => $existing['IdEsquemaEncabezado149'],
                            'IdEsquemaEncabezado150' => $existing['IdEsquemaEncabezado150'],
                            'IdAtencion' => $atencionId,
                            'IdProducto' => $productoId,
                            'already_exists' => true,
                        ],
                    ];
                }

                $usuarioSAHI = ASI_USUA::select('ASI_USUA.IdUsuario', 'hcePersonal.IdPersonal', 'hcePersonal.Nompersonal', 'hcePersonal.ApePersonal')
                    ->join('hcePersonal', 'ASI_USUA.IdUsuario', 'hcePersonal.IdUsuario')
                    ->where('ASI_USUA.IdUsuario', $medico->interface_id)
                    ->first();

                if (!$usuarioSAHI) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'El médico no se encuentra registrado en SAHI. Verifique que el usuario exista y tenga hcePersonal asociado.',
                    ];
                }

                $idUsuario = (int) $usuarioSAHI->IdUsuario;
                $idPersonal = (int) $usuarioSAHI->IdPersonal;
                $nomMedico = trim(($usuarioSAHI->Nompersonal ?? '') . ' ' . ($usuarioSAHI->ApePersonal ?? ''));

                $ubicacion = Atencion::select('IdUbicacionActual')
                    ->where('idAtencion', $atencionId)
                    ->first();

                if (!$ubicacion || !$ubicacion->IdUbicacionActual) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'No se pudo obtener la ubicación actual (IdUbicacionActual) de la atención en SAHI.',
                    ];
                }
                $idUbicacionActual = (int) $ubicacion->IdUbicacionActual;

                $consec1 = $this->incrementSisTablaConsecutivo('hceEsquemaEncabeza');
                $this->incrementHceEsquemaConsecutivo(149);

                $this->insertEncabezadoEsquema([
                    'IdEsquemaEncabezado' => $consec1,
                    'IdEsquema' => 149,
                    'idEncabezadoPadre' => $consec1,
                    'IdUbicacion' => $idUbicacionActual,
                    'idAtencion' => $atencionId,
                    'IdUsuarioR' => $idUsuario,
                    'FecRegistro' => $fecRegistro,
                ]);

                $this->insertEsquemaAtencion([
                    'IdEsquemadeAtencion' => $consec1,
                    'IdEsquema' => 149,
                    'IdAtencion' => $atencionId,
                    'IdUbicacion' => $idUbicacionActual,
                    'IdMedico' => $idPersonal,
                    'FecEsquema' => $fecRegistro,
                    'FecCerrado' => $fecRegistro,
                    'IdUsuarioR' => $idUsuario,
                ]);

                $this->insertMemo([
                    'IdMemo' => 1,
                    'IdEsquemaEncabezado' => $consec1,
                    'DesMemo' => $memo,
                ]);

                $this->insertDetalleMedico([
                    'IdEsquemaEncabezado' => $consec1,
                    'FecRegistro' => $fecRegistro,
                    'IdUsuarioR' => $idUsuario,
                    'NomMedico' => $nomMedico,
                ]);

                $consec2 = $this->incrementSisTablaConsecutivo('hceEsquemaEncabeza');

                $this->insertEncabezadoEsquema([
                    'IdEsquemaEncabezado' => $consec2,
                    'IdEsquema' => 150,
                    'idEncabezadoPadre' => $consec1,
                    'IdUbicacion' => $idUbicacionActual,
                    'idAtencion' => $atencionId,
                    'IdUsuarioR' => $idUsuario,
                    'FecRegistro' => $fecRegistro,
                ]);

                $productoSAHI = ProProducto::select('IdProducto', 'NomProducto', 'CodLegal')
                    ->where('IdProducto', $productoId)
                    ->first();

                if (!$productoSAHI) {
                    return [
                        'ok' => false,
                        'status' => 422,
                        'message' => 'El producto no se encontró en SAHI (proProducto). Verifique IdProducto.',
                    ];
                }

                $this->insertDetalleProducto([
                    'IdEsquemaEncabezado' => $consec2,
                    'IdUsuarioR' => $idUsuario,
                    'FecRegistro' => $fecRegistro,
                    'IdProducto' => $productoSAHI->IdProducto,
                    'NomProducto' => $productoSAHI->NomProducto,
                    'CodLegal' => $productoSAHI->CodLegal,
                ]);

                return [
                    'ok' => true,
                    'status' => 200,
                    'message' => 'Ecografía enviada a SAHI correctamente.',
                    'data' => [
                        'IdEsquemaEncabezado149' => $consec1,
                        'IdEsquemaEncabezado150' => $consec2,
                        'IdAtencion' => $atencionId,
                        'IdProducto' => $productoId,
                        'already_exists' => false,
                    ],
                ];
            });

            if (!$result['ok']) {
                $this->markEnvioError($ecoProd->id, $result['message']);
                return ['ok' => false, 'status' => $result['status'], 'message' => $result['message']];
            }

            DB::transaction(function () use ($ecoProd, $result) {
                EcografiaProducto::where('id', $ecoProd->id)->update([
                    'estado_envio_sahi' => 'ENVIADO',
                    'fechor_envio_sahi' => now(),
                    'sahi_esquema_149' => $result['data']['IdEsquemaEncabezado149'] ?? null,
                    'sahi_esquema_150' => $result['data']['IdEsquemaEncabezado150'] ?? null,
                    'error_envio_sahi' => null,
                ]);
            });

            return ['ok' => true, 'status' => 200, 'message' => $result['message'], 'data' => $result['data']];
        } catch (\Throwable $e) {
            $this->markEnvioError($ecoProd->id, $e->getMessage());
            return ['ok' => false, 'status' => 500, 'message' => 'Error enviando ecografía a SAHI.', 'data' => ['error' => $e->getMessage()]];
        }
    }

    private function markEnvioError(int $ecografiaProductoId, string $error): void
    {
        try {
            DB::transaction(function () use ($ecografiaProductoId, $error) {
                EcografiaProducto::where('id', $ecografiaProductoId)->update([
                    'estado_envio_sahi' => 'ERROR',
                    'fechor_envio_sahi' => now(),
                    'error_envio_sahi' => $error,
                ]);
            });
        } catch (\Throwable $e) {
            // no-op
        }
    }

    /**
     * Busca si ya existe en SAHI el esquema 150 para la misma (atención, producto).
     * Si existe, retorna IDs de esquema 149/150 para marcar idempotencia.
     *
     * @return array{IdEsquemaEncabezado149:int,IdEsquemaEncabezado150:int}|null
     */
    private function findExistingEsquema150ForAtencionProducto(int $atencionId, int $productoId): ?array
    {
        $row = DB::connection('sqlsrv')
            ->table('hceEsquemaEncabeza as e150')
            ->join('hceEsquemaDetProc as d', 'd.IdEsquemaEncabezado', '=', 'e150.IdEsquemaEncabezado')
            ->where('e150.IdEsquema', 150)
            ->where('e150.idAtencion', $atencionId)
            ->where('d.IdDetalleValor', 1)
            ->where('d.ValNroRegistro', 1)
            ->where('d.ValVarChar1', (string) $productoId)
            ->orderByDesc('e150.IdEsquemaEncabezado')
            ->first([
                'e150.IdEsquemaEncabezado as IdEsquemaEncabezado150',
                'e150.idEncabezadoPadre as IdEsquemaEncabezado149',
            ]);

        if (!$row) return null;

        $id149 = (int) ($row->IdEsquemaEncabezado149 ?? 0);
        $id150 = (int) ($row->IdEsquemaEncabezado150 ?? 0);
        if ($id149 <= 0 || $id150 <= 0) return null;

        // Verificación básica: el padre realmente sea un 149.
        $parentOk = DB::connection('sqlsrv')
            ->table('hceEsquemaEncabeza')
            ->where('IdEsquemaEncabezado', $id149)
            ->where('IdEsquema', 149)
            ->exists();

        if (!$parentOk) return null;

        return [
            'IdEsquemaEncabezado149' => $id149,
            'IdEsquemaEncabezado150' => $id150,
        ];
    }

    private function incrementSisTablaConsecutivo(string $nomTabla): int
    {
        $row = sisTabla::where('NomTabla', $nomTabla)
            ->where('IdTabla', '>', 0)
            ->firstOrFail();
        $row->Consecutivo = (int) $row->Consecutivo + 1;
        $row->save();
        return (int) $row->Consecutivo;
    }

    private function incrementHceEsquemaConsecutivo(int $idEsquema): void
    {
        $encabezadoPadre = HceEsquema::where('IdEsquema', $idEsquema)->firstOrFail();
        $encabezadoPadre->IdConsecutivoEsq = (int) $encabezadoPadre->IdConsecutivoEsq + 1;
        $encabezadoPadre->save();
    }

    private function insertEncabezadoEsquema(array $data): hceEsquemaEncabeza
    {
        $row = new hceEsquemaEncabeza();
        $row->IdEsquemaEncabezado = $data['IdEsquemaEncabezado'];
        $row->IdEsquema = $data['IdEsquema'];
        $row->idEncabezadoPadre = $data['idEncabezadoPadre'];
        $row->ValEsquemaConsecutivo = 1;
        $row->valDetConsecutivo = 0;
        $row->IdUbicacion = $data['IdUbicacion'];
        $row->idAtencion = $data['idAtencion'];
        $row->ValUltimoNroRegistro = 1;
        $row->IndActivado = 1;
        $row->IndHabilitado = 1;
        $row->IdUsuarioR = $data['IdUsuarioR'];
        $row->FecRegistro = $data['FecRegistro'];
        $row->IdProductoR = 0;
        $row->NomProducto = '';
        $row->CodProducto = '';
        $row->IdEstudio = '';
        $row->save();
        return $row;
    }

    private function insertEsquemaAtencion(array $data): hceEsquemasdeAte
    {
        $row = new hceEsquemasdeAte();
        $row->IdEsquemadeAtencion = $data['IdEsquemadeAtencion'];
        $row->IdEsquema = $data['IdEsquema'];
        $row->IdAtencion = $data['IdAtencion'];
        $row->IdUbicacion = $data['IdUbicacion'];
        $row->idTraslado = 1;
        $row->IdMedico = $data['IdMedico'];
        $row->FecEsquema = $data['FecEsquema'];
        $row->IndHabilitado = 1;
        $row->IndActivado = 1;
        $row->FecCerrado = $data['FecCerrado'];
        $row->IdUsuarioR = $data['IdUsuarioR'];
        $row->save();
        return $row;
    }

    private function insertMemo(array $data): hceEsquemaDetMemo
    {
        $row = new hceEsquemaDetMemo();
        $row->IdMemo = $data['IdMemo'];
        $row->IdEsquemaEncabezado = $data['IdEsquemaEncabezado'];
        $row->DesMemo = $data['DesMemo'];
        $row->save();
        return $row;
    }

    private function insertDetalleMedico(array $data): hceEsquemaDetProc
    {
        $row = new hceEsquemaDetProc();
        $row->IdDetalleValor = 1;
        $row->IdEsquemaEncabezado = $data['IdEsquemaEncabezado'];
        $row->ValNroRegistro = 1;
        $row->ValVarChar1 = $data['FecRegistro'];
        $row->ValVarChar2 = $data['FecRegistro'];
        $row->ValVarChar3 = '';
        $row->ValVarChar4 = 'MEMO1';
        $row->ValVarChar5 = '';
        $row->ValVarChar6 = '';
        $row->ValVarChar7 = '0';
        $row->ValVarChar8 = '1551';
        $row->ValVarChar9 = $data['NomMedico'];
        $row->ValVarChar10 = '';
        $row->ValVarChar11 = '';
        $row->ValVarChar12 = '';
        $row->ValVarChar13 = '';
        $row->ValVarChar14 = '';
        $row->ValVarChar15 = '';
        $row->ValVarChar16 = '';
        $row->ValVarChar17 = '';
        $row->ValVarChar18 = '';
        $row->ValVarChar19 = '';
        $row->ValVarChar20 = '';
        $row->IdUsuarioR = $data['IdUsuarioR'];
        $row->FecRegistro = $data['FecRegistro'];
        $row->save();
        return $row;
    }

    private function insertDetalleProducto(array $data): hceEsquemaDetProc
    {
        $row = new hceEsquemaDetProc();
        $row->IdDetalleValor = 1;
        $row->IdEsquemaEncabezado = $data['IdEsquemaEncabezado'];
        $row->ValNroRegistro = 1;
        $row->ValVarChar1 = (string) $data['IdProducto'];
        $row->ValVarChar2 = (string) $data['NomProducto'];
        $row->ValVarChar3 = (string) $data['CodLegal'];
        $row->ValVarChar4 = '';
        $row->ValVarChar5 = '';
        $row->ValVarChar6 = '';
        $row->ValVarChar7 = '';
        $row->ValVarChar8 = '';
        $row->ValVarChar9 = '';
        $row->ValVarChar10 = '';
        $row->ValVarChar11 = '';
        $row->ValVarChar12 = '';
        $row->ValVarChar13 = '';
        $row->ValVarChar14 = '';
        $row->ValVarChar15 = '';
        $row->ValVarChar16 = '';
        $row->ValVarChar17 = '';
        $row->ValVarChar18 = '';
        $row->ValVarChar19 = '';
        $row->ValVarChar20 = '';
        $row->IdUsuarioR = $data['IdUsuarioR'];
        $row->FecRegistro = $data['FecRegistro'];
        $row->save();
        return $row;
    }

    private function normalizeHtmlToText(string $value): string
    {
        $v = trim($value);
        if ($v === '') return '';

        $v = preg_replace('/<\s*br\s*\/?>/i', "\n", $v);
        $v = preg_replace('/<\/\s*div\s*>/i', "\n", $v);
        $v = preg_replace('/<\/\s*h[1-6]\s*>/i', "\n", $v);
        $v = strip_tags($v);
        $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $v = preg_replace("/\n{3,}/", "\n\n", $v);
        return trim($v);
    }
}
