<?php

namespace App\Services\Interfaces;

use App\Helpers\SahiConnectionHelper;
use App\Models\Configuracion\ConfigSede;
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
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SAHIEcografiasEnviarService extends BaseService
{
    /**
     * Retorna el estado de envío a SAHI de un producto de ecografía.
     *
     * @param  int  $ecografiaProductoId  Identificador del producto de ecografía.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-29
     */
    public function estadoPorEcografiaProducto(int $ecografiaProductoId): array
    {
        $ecoProd = EcografiaProducto::select([
            'id',
            'estado_envio_sahi',
            'fechor_envio_sahi',
            'error_envio_sahi',
            'sahi_esquema_149',
            'sahi_esquema_150',
        ])->find($ecografiaProductoId);

        if (! $ecoProd) {
            return $this->notFound('No se encontró el producto de ecografía en LIDiag.');
        }

        return $this->success([
            'ecografia_producto_id' => $ecoProd->id,
            'estado_envio_sahi' => $ecoProd->estado_envio_sahi,
            'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
            'error_envio_sahi' => $ecoProd->error_envio_sahi,
            'sahi_esquema_149' => $ecoProd->sahi_esquema_149,
            'sahi_esquema_150' => $ecoProd->sahi_esquema_150,
        ], 'Estado SAHI consultado.');
    }

    /**
     * Envía (o reenvía) un producto de ecografía al sistema SAHI.
     *
     * @param  int  $ecografiaProductoId  Identificador del producto de ecografía.
     *
     * @returns {array}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-01-29
     */
    public function enviarPorEcografiaProducto(int $ecografiaProductoId): array
    {
        $ecoProd = EcografiaProducto::where('id', $ecografiaProductoId)->first();

        if (! $ecoProd) {
            return $this->notFound('No se encontró el producto de ecografía en LIDiag.');
        }

        if ($ecoProd->estado_envio_sahi === 'ENVIADO') {
            return $this->success([
                'ecografia_producto_id' => $ecoProd->id,
                'sahi_esquema_149' => $ecoProd->sahi_esquema_149,
                'sahi_esquema_150' => $ecoProd->sahi_esquema_150,
                'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
            ], 'La ecografía ya fue enviada a SAHI.');
        }

        if ($ecoProd->estado_envio_sahi === 'ENVIANDO') {
            return $this->error('La ecografía ya está en proceso de envío a SAHI.', 409, [
                'ecografia_producto_id' => $ecoProd->id,
                'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
            ]);
        }

        $result = $this->sendToSahiByEcografiaProductoId((int) $ecoProd->id);

        if (! ($result['ok'] ?? false)) {
            $status = (int) ($result['status'] ?? 500);

            return $this->error(
                $result['message'] ?? 'Error enviando ecografía a SAHI.',
                $status,
                $result['data'] ?? null
            );
        }

        return $this->success($result['data'] ?? null, $result['message'] ?? 'Ecografía enviada a SAHI correctamente.');
    }

    /**
     * Ejecuta el envío completo del producto de ecografía a SAHI, validando, insertando y actualizando estados.
     *
     * @param  int  $ecografiaProductoId  Identificador del producto de ecografía
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function sendToSahiByEcografiaProductoId(int $ecografiaProductoId): array
    {
        $ecoProd = null;
        $ecoProds = collect();

        $txResult = $this->transaction(function () use ($ecografiaProductoId) {
            $row = EcografiaProducto::where('id', $ecografiaProductoId)
                ->lockForUpdate()
                ->with(['ecografia.medico'])
                ->first();

            if (! $row) {
                return [null, collect()];
            }

            $all = EcografiaProducto::where('ecografia_id', $row->ecografia_id)
                ->lockForUpdate()
                ->get();

            return [$row, $all];
        });

        if (isset($txResult['success']) && ! $txResult['success']) {
            return $txResult;
        }

        [$ecoProd, $ecoProds] = $txResult;

        $validated = $this->validateSendPrerequisites($ecoProd, $ecoProds);
        if (! $validated['ok'] || ! empty($validated['already_sent'])) {
            return $validated;
        }

        $ecoProdIds = $validated['eco_prod_ids'];
        $productoIds = $validated['producto_ids'];
        $ecoProd = $validated['eco_prod'];
        $ecografia = $validated['ecografia'];
        $medico = $validated['medico'];
        $atencionId = $validated['atencion_id'];
        $memo = $validated['memo'];
        $fecRegistro = $validated['fec_registro'];

        try {
            $sedeEco = $ecografia->sede_id ? ConfigSede::find($ecografia->sede_id) : null;
            SahiConnectionHelper::configureForSede($sedeEco);

            $result = DB::connection('sqlsrv')->transaction(
                fn () => $this->executeSahiInsert($medico, $atencionId, (int) $ecografia->id, $productoIds, $fecRegistro, $memo)
            );

            if (! ($result['ok'] ?? false)) {
                $status = (int) ($result['status'] ?? 500);
                $message = (string) ($result['message'] ?? 'Error enviando ecografía a SAHI.');
                if ($status !== 409) {
                    $this->markEnvioErrorMany($ecoProdIds, $message);
                }

                return ['ok' => false, 'status' => $status, 'message' => $message];
            }

            $this->updateProductsAfterSahiSend($ecoProdIds, $ecoProds, $result);

            return ['ok' => true, 'status' => 200, 'message' => $result['message'] ?? 'Ecografía enviada a SAHI correctamente.', 'data' => $result['data'] ?? null];
        } catch (\Throwable $e) {
            $this->markEnvioErrorMany($ecoProdIds, $e->getMessage());

            return ['ok' => false, 'status' => 500, 'message' => 'Error enviando ecografía a SAHI.', 'data' => ['error' => $e->getMessage()]];
        }
    }

    /**
     * Valida los prerrequisitos del envío a SAHI y marca los productos como ENVIANDO.
     *
     * @param  mixed  $ecoProd  Producto de ecografía a enviar
     * @param  mixed  $ecoProds  Colección de productos de la misma ecografía
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function validateSendPrerequisites($ecoProd, $ecoProds): array
    {
        if (! $ecoProd) {
            return ['ok' => false, 'status' => 404, 'message' => 'No se encontró el producto de ecografía en LIDiag.'];
        }

        $ecoProdIds = $ecoProds->pluck('id')->filter()->values()->all();
        $productoIds = $ecoProds->pluck('producto_id')->filter()->unique()->values()->all();

        if (empty($ecoProdIds) || empty($productoIds)) {
            $this->markEnvioErrorMany($ecoProdIds ?: [$ecoProd->id], 'La ecografía no tiene productos válidos para enviar a SAHI.');

            return ['ok' => false, 'status' => 422, 'message' => 'La ecografía no tiene productos válidos para enviar a SAHI.'];
        }

        $allSent = $ecoProds->every(function ($p) {
            return (string) ($p->estado_envio_sahi ?? '') === 'ENVIADO';
        });

        if ($allSent) {
            return [
                'ok' => true,
                'already_sent' => true,
                'message' => 'La ecografía ya fue enviada a SAHI.',
                'data' => [
                    'ecografia_producto_id' => $ecoProd->id,
                    'sahi_esquema_149' => $ecoProd->sahi_esquema_149,
                    'sahi_esquema_150' => $ecoProd->sahi_esquema_150,
                    'fechor_envio_sahi' => $ecoProd->fechor_envio_sahi,
                ],
            ];
        }

        $this->transaction(function () use ($ecoProdIds) {
            EcografiaProducto::whereIn('id', $ecoProdIds)
                ->where(function ($q) {
                    $q->whereNull('estado_envio_sahi')
                        ->orWhere('estado_envio_sahi', '!=', 'ENVIADO');
                })
                ->update([
                    'estado_envio_sahi' => 'ENVIANDO',
                    'fechor_envio_sahi' => now(),
                    'error_envio_sahi' => null,
                ]);
        });

        $ecografia = $ecoProd->ecografia;
        if (! $ecografia) {
            $this->markEnvioErrorMany($ecoProdIds, 'No se pudo resolver la ecografía asociada al producto.');

            return ['ok' => false, 'status' => 422, 'message' => 'No se pudo resolver la ecografía asociada al producto.'];
        }

        $atencionId = $ecografia->atencion_id;
        if (! $atencionId) {
            $this->markEnvioErrorMany($ecoProdIds, 'La ecografía no tiene atencion_id para enviar a SAHI.');

            return ['ok' => false, 'status' => 422, 'message' => 'La ecografía no tiene atencion_id para enviar a SAHI.'];
        }

        $medico = $ecografia->medico;
        if (! $medico || ! $medico->interface_id) {
            $this->markEnvioErrorMany($ecoProdIds, 'El médico no tiene interface_id (SAHI) configurado. Verifique el usuario.');

            return ['ok' => false, 'status' => 422, 'message' => 'El médico no tiene interface_id (SAHI) configurado. Verifique el usuario.'];
        }

        $fecRegistro = Carbon::now()->format('Y-m-d H:i:s');

        $lecturaPlano = $this->normalizeHtmlToText($ecoProd->lectura ?? '');
        $opinionPlano = $this->normalizeHtmlToText($ecoProd->opinion ?? '');
        $memo = trim($lecturaPlano)."\n\nOpinion:\n".trim($opinionPlano);

        return [
            'ok' => true,
            'eco_prod_ids' => $ecoProdIds,
            'producto_ids' => $productoIds,
            'eco_prod' => $ecoProd,
            'ecografia' => $ecografia,
            'medico' => $medico,
            'atencion_id' => $atencionId,
            'memo' => $memo,
            'fec_registro' => $fecRegistro,
        ];
    }

    /**
     * Ejecuta las inserciones en SAHI dentro de una transacción con lock de aplicación.
     *
     * @param  mixed  $medico  Modelo del médico con interface_id de SAHI
     * @param  int  $atencionId  Identificador de la atención en SAHI
     * @param  int  $ecografiaId  Identificador de la ecografía en Lidiag
     * @param  array  $productoIds  Identificadores de los productos a insertar
     * @param  string  $fecRegistro  Fecha y hora de registro del envío
     * @param  string  $memo  Texto del reporte consolidado para el memo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function executeSahiInsert($medico, int $atencionId, int $ecografiaId, array $productoIds, string $fecRegistro, string $memo): array
    {
        $lockKey = "LIDiag:SAHI:Atencion:{$atencionId}:Ecografia:{$ecografiaId}";
        $lockRow = DB::connection('sqlsrv')->selectOne(
            "EXEC sp_getapplock @Resource = ?, @LockMode = 'Exclusive', @LockOwner = 'Transaction', @LockTimeout = 10000;",
            [$lockKey]
        );

        $lockResult = null;
        if (is_object($lockRow)) {
            $vals = array_values(get_object_vars($lockRow));
            $lockResult = $vals[0] ?? null;
        }

        if ($lockResult === null || (int) $lockResult < 0) {
            return ['ok' => false, 'status' => 409, 'message' => 'No fue posible obtener lock para el envío a SAHI (posible envío concurrente). Reintente.'];
        }

        $existingMap = [];
        foreach ($productoIds as $pid) {
            $ex = $this->findExistingEsquema150ForAtencionProducto((int) $atencionId, (int) $pid);
            if ($ex) {
                $existingMap[(int) $pid] = $ex;
            }
        }
        if (count($existingMap) === count($productoIds) && count($productoIds) > 0) {
            $first = array_values($existingMap)[0];

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Ya existía un registro en SAHI para esta atención/productos (no se reinsertó).',
                'data' => [
                    'IdEsquemaEncabezado149' => $first['IdEsquemaEncabezado149'],
                    'IdEsquemaEncabezado150' => $first['IdEsquemaEncabezado150'],
                    'IdAtencion' => $atencionId,
                    'IdProductos' => $productoIds,
                    'already_exists' => true,
                    'existing_map' => $existingMap,
                ],
            ];
        }

        $usuarioSAHI = ASI_USUA::select('ASI_USUA.IdUsuario', 'hcePersonal.IdPersonal', 'hcePersonal.Nompersonal', 'hcePersonal.ApePersonal')
            ->join('hcePersonal', 'ASI_USUA.IdUsuario', 'hcePersonal.IdUsuario')
            ->where('ASI_USUA.IdUsuario', $medico->interface_id)
            ->first();
        if (! $usuarioSAHI) {
            return ['ok' => false, 'status' => 422, 'message' => 'El médico no se encuentra registrado en SAHI. Verifique que el usuario exista y tenga hcePersonal asociado.'];
        }

        $idUsuario = (int) $usuarioSAHI->IdUsuario;
        $idPersonal = (int) $usuarioSAHI->IdPersonal;
        $nomMedico = trim(($usuarioSAHI->Nompersonal ?? '').' '.($usuarioSAHI->ApePersonal ?? ''));

        $ubicacion = Atencion::select('IdUbicacionActual')
            ->where('idAtencion', $atencionId)
            ->first();
        if (! $ubicacion || ! $ubicacion->IdUbicacionActual) {
            return ['ok' => false, 'status' => 422, 'message' => 'No se pudo obtener la ubicación actual (IdUbicacionActual) de la atención en SAHI.'];
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

        $productosSAHI = ProProducto::select('IdProducto', 'NomProducto', 'CodLegal')
            ->whereIn('IdProducto', $productoIds)
            ->get()
            ->keyBy('IdProducto');

        $missing = [];
        foreach ($productoIds as $pid) {
            if (! $productosSAHI->has($pid)) {
                $missing[] = (int) $pid;
            }
        }
        if (! empty($missing)) {
            return ['ok' => false, 'status' => 422, 'message' => 'Algunos productos no se encontraron en SAHI (proProducto): '.implode(', ', $missing)];
        }

        $i = 0;
        foreach ($productoIds as $pid) {
            $i++;
            $p = $productosSAHI->get($pid);
            $this->insertDetalleProducto([
                'IdDetalleValor' => $i,
                'IdEsquemaEncabezado' => $consec2,
                'IdUsuarioR' => $idUsuario,
                'FecRegistro' => $fecRegistro,
                'IdProducto' => $p->IdProducto,
                'NomProducto' => $p->NomProducto,
                'CodLegal' => $p->CodLegal,
            ]);
        }

        return [
            'ok' => true,
            'status' => 200,
            'message' => 'Ecografía enviada a SAHI correctamente.',
            'data' => [
                'IdEsquemaEncabezado149' => $consec1,
                'IdEsquemaEncabezado150' => $consec2,
                'IdAtencion' => $atencionId,
                'IdProductos' => $productoIds,
                'ProductosCount' => count($productoIds),
                'already_exists' => false,
            ],
        ];
    }

    /**
     * Actualiza el estado de los productos de ecografía como ENVIADOS tras el éxito en SAHI.
     *
     * @param  array  $ecoProdIds  Identificadores de los productos a actualizar
     * @param  mixed  $ecoProds  Colección de productos de la ecografía
     * @param  array  $result  Resultado del envío con los consecutivos de SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function updateProductsAfterSahiSend(array $ecoProdIds, $ecoProds, array $result): void
    {
        $this->transaction(function () use ($ecoProdIds, $ecoProds, $result) {
            $data = $result['data'] ?? [];
            $id149 = $data['IdEsquemaEncabezado149'] ?? null;
            $id150 = $data['IdEsquemaEncabezado150'] ?? null;
            $existingMap = $data['existing_map'] ?? null;

            if (is_array($existingMap) && ! empty($existingMap)) {
                foreach ($ecoProds as $row) {
                    $pid = (int) ($row->producto_id ?? 0);
                    $ex = $pid && isset($existingMap[$pid]) ? $existingMap[$pid] : null;
                    EcografiaProducto::where('id', $row->id)->update([
                        'estado_envio_sahi' => 'ENVIADO',
                        'fechor_envio_sahi' => now(),
                        'sahi_esquema_149' => $ex['IdEsquemaEncabezado149'] ?? $id149,
                        'sahi_esquema_150' => $ex['IdEsquemaEncabezado150'] ?? $id150,
                        'error_envio_sahi' => null,
                    ]);
                }

                return;
            }

            EcografiaProducto::whereIn('id', $ecoProdIds)->update([
                'estado_envio_sahi' => 'ENVIADO',
                'fechor_envio_sahi' => now(),
                'sahi_esquema_149' => $id149,
                'sahi_esquema_150' => $id150,
                'error_envio_sahi' => null,
            ]);
        });
    }

    /**
     * Marca un conjunto de productos de ecografía como ERROR de envío a SAHI.
     *
     * @param  array  $ecografiaProductoIds  Identificadores de los productos a marcar
     * @param  string  $error  Mensaje de error a registrar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function markEnvioErrorMany(array $ecografiaProductoIds, string $error): void
    {
        $ids = array_values(array_filter(array_map('intval', $ecografiaProductoIds)));
        if (empty($ids)) {
            return;
        }

        try {
            $this->transaction(function () use ($ids, $error) {
                EcografiaProducto::whereIn('id', $ids)
                    ->where(function ($q) {
                        $q->whereNull('estado_envio_sahi')
                            ->orWhere('estado_envio_sahi', '!=', 'ENVIADO');
                    })
                    ->update([
                        'estado_envio_sahi' => 'ERROR',
                        'fechor_envio_sahi' => DB::raw('COALESCE(fechor_envio_sahi, NOW())'),
                        'error_envio_sahi' => $error,
                    ]);
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo marcar ERROR en ecografias_productos (SAHI).', [
                'ids' => $ids,
                'error' => $error,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Incrementa y persiste el consecutivo de una tabla de SAHI para generar nuevos esquemas.
     *
     * @param  string  $nomTabla  Nombre de la tabla SAHI cuyo consecutivo se incrementa
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function incrementSisTablaConsecutivo(string $nomTabla): int
    {
        $row = sisTabla::where('NomTabla', $nomTabla)
            ->where('IdTabla', '>', 0)
            ->firstOrFail();
        $row->Consecutivo = (int) $row->Consecutivo + 1;
        $row->save();

        return (int) $row->Consecutivo;
    }

    /**
     * Incrementa el consecutivo del esquema padre en la tabla HceEsquema de SAHI.
     *
     * @param  int  $idEsquema  Identificador del esquema en SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function incrementHceEsquemaConsecutivo(int $idEsquema): void
    {
        $encabezadoPadre = HceEsquema::where('IdEsquema', $idEsquema)->firstOrFail();
        $encabezadoPadre->IdConsecutivoEsq = (int) $encabezadoPadre->IdConsecutivoEsq + 1;
        $encabezadoPadre->save();
    }

    /**
     * Inserta un registro de encabezado de esquema en la tabla hceEsquemaEncabeza de SAHI.
     *
     * @param  array  $data  Datos del encabezado: IdEsquemaEncabezado, IdEsquema, idEncabezadoPadre, IdUbicacion, idAtencion, IdUsuarioR, FecRegistro
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function insertEncabezadoEsquema(array $data): hceEsquemaEncabeza
    {
        $row = new hceEsquemaEncabeza;
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

    /**
     * Inserta el registro de esquema de atención en la tabla hceEsquemasdeAte de SAHI.
     *
     * @param  array  $data  Datos de la relación: IdEsquemadeAtencion, IdEsquema, IdAtencion, IdUbicacion, IdMedico, FecEsquema, FecCerrado, IdUsuarioR
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function insertEsquemaAtencion(array $data): hceEsquemasdeAte
    {
        $row = new hceEsquemasdeAte;
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

    /**
     * Inserta el memo con el texto del reporte en la tabla hceEsquemaDetMemo de SAHI.
     *
     * @param  array  $data  Datos del memo: IdMemo, IdEsquemaEncabezado, DesMemo
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function insertMemo(array $data): hceEsquemaDetMemo
    {
        $row = new hceEsquemaDetMemo;
        $row->IdMemo = $data['IdMemo'];
        $row->IdEsquemaEncabezado = $data['IdEsquemaEncabezado'];
        $row->DesMemo = $data['DesMemo'];
        $row->save();

        return $row;
    }

    /**
     * Inserta el detalle del médico transcriptor en la tabla hceEsquemaDetProc de SAHI.
     *
     * @param  array  $data  Datos del detalle: IdEsquemaEncabezado, FecRegistro, IdUsuarioR, NomMedico
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function insertDetalleMedico(array $data): hceEsquemaDetProc
    {
        $row = new hceEsquemaDetProc;
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

    /**
     * Inserta el detalle del producto CUPS en la tabla hceEsquemaDetProc de SAHI.
     *
     * @param  array  $data  Datos del producto: IdDetalleValor, IdEsquemaEncabezado, IdUsuarioR, FecRegistro, IdProducto, NomProducto, CodLegal
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function insertDetalleProducto(array $data): hceEsquemaDetProc
    {
        $row = new hceEsquemaDetProc;
        $row->IdDetalleValor = (int) ($data['IdDetalleValor'] ?? 1);
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

    /**
     * Busca si ya existe un esquema 150 en SAHI para la atención y producto indicados.
     *
     * @param  int  $atencionId  Identificador de la atención en SAHI
     * @param  int  $productoId  Identificador del producto a buscar
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
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

        if (! $row) {
            return null;
        }

        $id149 = (int) ($row->IdEsquemaEncabezado149 ?? 0);
        $id150 = (int) ($row->IdEsquemaEncabezado150 ?? 0);
        if ($id149 <= 0 || $id150 <= 0) {
            return null;
        }

        $parentOk = DB::connection('sqlsrv')
            ->table('hceEsquemaEncabeza')
            ->where('IdEsquemaEncabezado', $id149)
            ->where('IdEsquema', 149)
            ->exists();

        if (! $parentOk) {
            return null;
        }

        return [
            'IdEsquemaEncabezado149' => $id149,
            'IdEsquemaEncabezado150' => $id150,
        ];
    }

    /**
     * Convierte HTML simple a texto plano limpiando etiquetas y normalizando saltos de línea.
     *
     * @param  string  $value  Cadena HTML a convertir
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function normalizeHtmlToText(string $value): string
    {
        $v = trim($value);
        if ($v === '') {
            return '';
        }

        $v = preg_replace('/<\s*br\s*\/?>/i', "\n", $v);
        $v = preg_replace('/<\/\s*(?:p|div|h[1-6])\s*>/i', "\n\n", $v);
        $v = str_ireplace(['&nbsp;', '&#160;'], ' ', $v);
        $v = strip_tags($v);
        $v = html_entity_decode($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $v = preg_replace('/\x{00A0}/u', ' ', $v);
        $v = preg_replace("/\r\n|\r/", "\n", $v);
        $v = preg_replace("/\n{3,}/", "\n\n", $v);

        return trim($v);
    }
}
