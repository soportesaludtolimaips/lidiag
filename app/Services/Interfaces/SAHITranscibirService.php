<?php

namespace App\Services\Interfaces;

use App\Models\Interface\Sahi\ASI_USUA;
use App\Models\Interface\Sahi\Atencion;
use App\Models\Interface\Sahi\HceEsquema;
use App\Models\Interface\Sahi\hceEsquemaDetMemo;
use App\Models\Interface\Sahi\hceEsquemaDetProc;
use App\Models\Interface\Sahi\hceEsquemaEncabeza;
use App\Models\Interface\Sahi\hceEsquemasdeAte;
use App\Models\Interface\Sahi\ProProducto;
use App\Models\Interface\Sahi\sisTabla;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la transcripción y despacho atómico de estudios radiológicos hacia SAHI (SQL Server).
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-21
 */
class SAHITranscibirService extends BaseService
{
    /**
     * Envía la transcripción de un estudio a SAHI creando esquemas, memo, médico y producto de forma atómica.
     *
     * Flujo del proceso:
     * 1. Valida la existencia del médico asignado y su correlación con la tabla ASI_USUA/hcePersonal de SAHI.
     * 2. Obtiene la ubicación actual de la atención del paciente en SAHI.
     * 3. Inicia una transacción atómica en la conexión 'sqlsrv'.
     * 4. Genera e incrementa los consecutivos de encabezado y esquema padre en SAHI.
     * 5. Persiste secuencialmente: esquema encabezado 149, esquema de atención, memo de lectura, detalle de médico transcriptor.
     * 6. Genera un segundo consecutivo para el esquema de detalle y asocia el producto CUPS.
     * 7. Confirma la transacción en SQL Server y retorna el payload estructurado con todos los identificadores.
     * 8. Si ocurre cualquier excepción en el proceso o caída de red, revierte la transacción completamente y loguea el error.
     *
     * @param  int|null  $idAtencion  Identificador de la atención en SAHI
     * @param  int|null  $idMedico  Identificador del médico en Lidiag
     * @param  string|null  $reporteEstudio  Texto del reporte del estudio a transcribir
     * @param  string|null  $codCups  Código CUPS del producto del estudio
     * @param  string|null  $fecHorLectura  Fecha y hora de lectura del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function enviarEstudio(
        ?int $idAtencion,
        ?int $idMedico,
        ?string $reporteEstudio,
        ?string $codCups,
        ?string $fecHorLectura
    ): array {
        if (! $idMedico) {
            return $this->error(
                'El médico no se encuentra registrado en SAHI, por favor consulte con el administrador del sistema.',
                422
            );
        }

        if (! $idAtencion) {
            return $this->error('El identificador de la atención en SAHI es obligatorio.', 422);
        }

        $usuarioData = $this->getUsuario($idMedico);
        if ($usuarioData === false || isset($usuarioData['status'])) {
            return is_array($usuarioData)
                ? $this->error($usuarioData['message'] ?? 'Error', $usuarioData['status'] ?? 500)
                : $this->error('El médico no se encuentra registrado en SAHI.', 404);
        }

        $idUsuario = (int) $usuarioData[0]['IdUsuario'];
        $idPersonal = (int) $usuarioData[0]['IdPersonal'];
        $nomMedico = $usuarioData[0]['Nompersonal'].' '.$usuarioData[0]['ApePersonal'];

        $ubicacionActual = $this->getUbicacionActual($idAtencion);
        if (! $ubicacionActual) {
            return $this->error('No se pudo obtener la ubicación actual de la atención en SAHI.', 422);
        }

        $fecHorLecturaFmt = str_replace(' ', 'T', (string) $fecHorLectura);

        // Verificación de idempotencia en SAHI: Si el estudio ya fue registrado previamente
        // para la misma atención, CUPS y dentro de la misma ventana temporal (<= 24h),
        // se reutilizan los identificadores sin crear esquemas duplicados en SAHI.
        if ($codCups) {
            $existente = $this->findExistingEsquemaForAtencionCups($idAtencion, $codCups, $fecHorLectura);
            if ($existente) {
                return $this->success([
                    'conseEsquemaEncavezado' => $existente['IdEsquemaEncabezado149'],
                    'conseEsquemaEncavezado2' => $existente['IdEsquemaEncabezado150'],
                    'already_exists' => true,
                ], 'El estudio ya se encontraba registrado en SAHI para esta atención y fecha (no se reinsertó).');
            }
        }

        try {
            return DB::connection('sqlsrv')->transaction(function () use (
                $idAtencion,
                $ubicacionActual,
                $idUsuario,
                $idPersonal,
                $nomMedico,
                $fecHorLecturaFmt,
                $reporteEstudio,
                $codCups
            ) {
                $conseEsquemaEncavezado = $this->generarConsecutivoEsquemaEncavezado();
                $conseEsquemaEncavezadoPadre = $this->generarConsecutivoEsquemaEncavezadoPadre();

                $esquemaEncabeza = $this->storeEsquemaEncabeza($conseEsquemaEncavezadoPadre, $conseEsquemaEncavezado, $ubicacionActual, $idAtencion, $idUsuario, $fecHorLecturaFmt);
                $esquemaEncavezadoAtencion = $this->storeEsquemaAtencion($conseEsquemaEncavezado, $ubicacionActual, $idAtencion, $idPersonal, $idUsuario, $fecHorLecturaFmt);
                $esquemaMemo = $this->storeEsquemaMemo($conseEsquemaEncavezado, (string) $reporteEstudio);
                $medico = $this->storeMedico($conseEsquemaEncavezado, $fecHorLecturaFmt, $nomMedico, $idUsuario);

                $conseEsquemaEncavezado2 = $this->generarConsecutivoEsquemaEncavezado();
                $esquemaEncabeza2 = $this->storeEsquemaEncabeza($conseEsquemaEncavezado, $conseEsquemaEncavezado2, $ubicacionActual, $idAtencion, $idUsuario, $fecHorLecturaFmt);

                $producto = $this->storaProducto($conseEsquemaEncavezado2, (string) $codCups, $idUsuario, $fecHorLecturaFmt);

                return $this->success([
                    'conseEsquemaEncavezado' => $conseEsquemaEncavezado,
                    'conseEsquemaEncavezadoPadre' => $conseEsquemaEncavezadoPadre,
                    'esquemaEncabeza' => $esquemaEncabeza,
                    'esquemaEncavezadoAtencion' => $esquemaEncavezadoAtencion,
                    'esquemaMemo' => $esquemaMemo,
                    'medico' => $medico,
                    'conseEsquemaEncavezado2' => $conseEsquemaEncavezado2,
                    'esquemaEncabeza2' => $esquemaEncabeza2,
                    'producto' => $producto,
                ], 'La transcripción se realizó con éxito.');
            });
        } catch (\Throwable $e) {
            Log::error('Fallo en la transacción de envío a SAHI: '.$e->getMessage(), [
                'idAtencion' => $idAtencion,
                'idMedico' => $idMedico,
                'codCups' => $codCups,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Error al registrar la transcripción en SAHI: '.$e->getMessage(), 500);
        }
    }

    /**
     * Genera y persiste el siguiente consecutivo de esquema encabezado en SAHI.
     *
     * Flujo del proceso:
     * 1. Consulta el registro en sisTabla correspondiente a hceEsquemaEncabeza.
     * 2. Incrementa el contador Consecutivo en 1 y lo persiste.
     * 3. Retorna el nuevo número consecutivo asignado.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function generarConsecutivoEsquemaEncavezado(): int
    {
        $esquema = sisTabla::where('NomTabla', 'hceEsquemaEncabeza')
            ->where('IdTabla', '>', 0)
            ->firstOrFail();
        $esquema->Consecutivo = $esquema->Consecutivo + 1;
        $esquema->save();

        return (int) $esquema->Consecutivo;
    }

    /**
     * Genera y persiste el siguiente consecutivo de esquema encabezado padre en SAHI.
     *
     * Flujo del proceso:
     * 1. Consulta el esquema maestro 149 en HceEsquema.
     * 2. Incrementa IdConsecutivoEsq en 1 y lo persiste.
     * 3. Retorna el nuevo consecutivo padre asignado.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function generarConsecutivoEsquemaEncavezadoPadre(): int
    {
        $encavezadoPadre = HceEsquema::where('IdEsquema', 149)
            ->firstOrFail();
        $encavezadoPadre->IdConsecutivoEsq = (int) $encavezadoPadre->IdConsecutivoEsq + 1;
        $encavezadoPadre->save();

        return (int) $encavezadoPadre->IdConsecutivoEsq;
    }

    /**
     * Crea un registro de esquema encabezado en SAHI para la transcripción del estudio.
     *
     * Flujo del proceso:
     * 1. Instancia el modelo hceEsquemaEncabeza.
     * 2. Asigna identificadores de esquema (149), padre, ubicación, atención y usuario registrador.
     * 3. Guarda el registro en la base de datos de SAHI.
     *
     * @param  int  $conseEsquemaEncavezado1  Consecutivo del encabezado padre
     * @param  int  $conseEsquemaEncavezado2  Consecutivo del encabezado actual
     * @param  int  $ubucacionActual  Identificador de la ubicación actual de la atención
     * @param  int  $idAtencion  Identificador de la atención en SAHI
     * @param  int  $idUsuario  Identificador del usuario en SAHI
     * @param  string  $fecHorLectura  Fecha y hora de lectura del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function storeEsquemaEncabeza(int $conseEsquemaEncavezado1, int $conseEsquemaEncavezado2, int $ubucacionActual, int $idAtencion, int $idUsuario, string $fecHorLectura): hceEsquemaEncabeza
    {
        $esquemaEncavezado = new hceEsquemaEncabeza;
        $esquemaEncavezado->IdEsquemaEncabezado = $conseEsquemaEncavezado2;
        $esquemaEncavezado->IdEsquema = '149';
        $esquemaEncavezado->idEncabezadoPadre = $conseEsquemaEncavezado1;
        $esquemaEncavezado->ValEsquemaConsecutivo = '1';
        $esquemaEncavezado->valDetConsecutivo = '0';
        $esquemaEncavezado->IdUbicacion = $ubucacionActual;
        $esquemaEncavezado->idAtencion = $idAtencion;
        $esquemaEncavezado->ValUltimoNroRegistro = '1';
        $esquemaEncavezado->IndActivado = '1';
        $esquemaEncavezado->IndHabilitado = '1';
        $esquemaEncavezado->IdUsuarioR = $idUsuario;
        $esquemaEncavezado->FecRegistro = $fecHorLectura;
        $esquemaEncavezado->IdProductoR = '0';
        $esquemaEncavezado->NomProducto = '';
        $esquemaEncavezado->CodProducto = '';
        $esquemaEncavezado->IdEstudio = '';
        $esquemaEncavezado->save();

        return $esquemaEncavezado;
    }

    /**
     * Crea el registro de esquema de atención en SAHI que asocia el esquema con la atención.
     *
     * Flujo del proceso:
     * 1. Instancia hceEsquemasdeAte.
     * 2. Asocia la atención, ubicación, médico responsable y fechas de apertura y cierre.
     * 3. Guarda el registro en SAHI.
     *
     * @param  int  $conseEsquemaEncavezado  Consecutivo del encabezado del esquema
     * @param  int  $ubucacionActual  Identificador de la ubicación actual de la atención
     * @param  int  $idAtencion  Identificador de la atención en SAHI
     * @param  int  $idPersonal  Identificador del personal médico en SAHI
     * @param  int  $idUsuario  Identificador del usuario en SAHI
     * @param  string  $fecHorLectura  Fecha y hora de lectura del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function storeEsquemaAtencion(int $conseEsquemaEncavezado, int $ubucacionActual, int $idAtencion, int $idPersonal, int $idUsuario, string $fecHorLectura): hceEsquemasdeAte
    {
        $esquemAtenicon = new hceEsquemasdeAte;
        $esquemAtenicon->IdEsquemadeAtencion = $conseEsquemaEncavezado;
        $esquemAtenicon->IdEsquema = 149;
        $esquemAtenicon->IdAtencion = $idAtencion;
        $esquemAtenicon->IdUbicacion = $ubucacionActual;
        $esquemAtenicon->idTraslado = 1;
        $esquemAtenicon->IdMedico = $idPersonal;
        $esquemAtenicon->FecEsquema = $fecHorLectura;
        $esquemAtenicon->IndHabilitado = 1;
        $esquemAtenicon->IndActivado = 1;
        $esquemAtenicon->FecCerrado = $fecHorLectura;
        $esquemAtenicon->IdUsuarioR = $idUsuario;
        $esquemAtenicon->save();

        return $esquemAtenicon;
    }

    /**
     * Almacena el memo con el texto del reporte del estudio en el esquema de SAHI.
     *
     * Flujo del proceso:
     * 1. Instancia hceEsquemaDetMemo con identificador fijo 1.
     * 2. Vincula el ID de esquema encabezado y el texto del reporte clínico.
     * 3. Guarda el registro en SAHI.
     *
     * @param  int  $conseEsquemaEncavezado  Consecutivo del encabezado del esquema
     * @param  string  $reporteEstudio  Texto del reporte del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function storeEsquemaMemo(int $conseEsquemaEncavezado, string $reporteEstudio): hceEsquemaDetMemo
    {
        $memoLimpio = $this->limpiarTextoPlanoSahi($reporteEstudio);

        $esquemaMemo = new hceEsquemaDetMemo;
        $esquemaMemo->IdMemo = 1;
        $esquemaMemo->IdEsquemaEncabezado = $conseEsquemaEncavezado;
        $esquemaMemo->DesMemo = $memoLimpio;
        $esquemaMemo->save();

        return $esquemaMemo;
    }

    /**
     * Normaliza cualquier entrada de texto a texto plano clínico puro para SAHI (SQL Server).
     *
     * Flujo del proceso:
     * 1. Convierte saltos de línea HTML (<br>, </p>, </div>) a saltos de línea \n.
     * 2. Reemplaza entidades &nbsp; y &#160; por espacios regulares.
     * 3. Remueve etiquetas HTML y decodifica entidades a UTF-8 real.
     * 4. Normaliza espacios de no separación UTF-8 (\u{00A0}) y saltos de línea.
     *
     * @param  string  $texto  Texto del reporte a normalizar
     * @return string Texto plano clínico limpio para SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function limpiarTextoPlanoSahi(string $texto): string
    {
        if (trim($texto) === '') {
            return '';
        }

        $texto = preg_replace('/<\s*br\s*\/?>/i', "\n", $texto);
        $texto = preg_replace('/<\/\s*(?:p|div|h[1-6])\s*>/i', "\n\n", $texto);
        $texto = str_ireplace(['&nbsp;', '&#160;'], ' ', $texto);
        $texto = strip_tags($texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\x{00A0}/u', ' ', $texto);
        $texto = preg_replace("/\r\n|\r/", "\n", $texto);
        $texto = preg_replace("/\n{3,}/", "\n\n", $texto);

        return trim($texto);
    }

    /**
     * Registra el detalle del médico que realizó la transcripción en el esquema de SAHI.
     *
     * Flujo del proceso:
     * 1. Instancia hceEsquemaDetProc con tipo de detalle 1.
     * 2. Setea variables requeridas por SAHI (fecha de lectura, clave MEMO1, código y nombre del profesional).
     * 3. Guarda el registro en SAHI.
     *
     * @param  int  $conseEsquemaEncavezado  Consecutivo del encabezado del esquema
     * @param  string  $fecHorLectura  Fecha y hora de lectura del estudio
     * @param  string  $nomMedico  Nombre completo del médico transcriptor
     * @param  int  $idUsuario  Identificador del usuario en SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function storeMedico(int $conseEsquemaEncavezado, string $fecHorLectura, string $nomMedico, int $idUsuario): hceEsquemaDetProc
    {
        $medico = new hceEsquemaDetProc;
        $medico->IdDetalleValor = 1;
        $medico->IdEsquemaEncabezado = $conseEsquemaEncavezado;
        $medico->ValNroRegistro = 1;
        $medico->ValVarChar1 = str_replace('T', ' ', $fecHorLectura);
        $medico->ValVarChar2 = str_replace('T', ' ', $fecHorLectura);
        $medico->ValVarChar3 = '';
        $medico->ValVarChar4 = 'MEMO1';
        $medico->ValVarChar5 = '';
        $medico->ValVarChar6 = '';
        $medico->ValVarChar7 = '0';
        $medico->ValVarChar8 = '1551';
        $medico->ValVarChar9 = $nomMedico;
        $medico->ValVarChar10 = '';
        $medico->ValVarChar11 = '';
        $medico->ValVarChar12 = '';
        $medico->ValVarChar13 = '';
        $medico->ValVarChar14 = '';
        $medico->ValVarChar15 = '';
        $medico->ValVarChar16 = '';
        $medico->ValVarChar17 = '';
        $medico->ValVarChar18 = '';
        $medico->ValVarChar19 = '';
        $medico->ValVarChar20 = '';
        $medico->IdUsuarioR = $idUsuario;
        $medico->FecRegistro = $fecHorLectura;
        $medico->save();

        return $medico;
    }

    /**
     * Registra el detalle del producto CUPS del estudio en el esquema de SAHI.
     *
     * Flujo del proceso:
     * 1. Consulta el producto en la tabla ProProducto de SAHI por su código CUPS.
     * 2. Instancia hceEsquemaDetProc asociando IdProducto, NomProducto y CodLegal.
     * 3. Guarda el registro en SAHI.
     *
     * @param  int  $conseEsquemaEncavezado  Consecutivo del encabezado del esquema
     * @param  string  $codCups  Código CUPS del producto a registrar
     * @param  int  $idUsuario  Identificador del usuario en SAHI
     * @param  string  $fecHorLectura  Fecha y hora de lectura del estudio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function storaProducto(int $conseEsquemaEncavezado, string $codCups, int $idUsuario, string $fecHorLectura): hceEsquemaDetProc
    {
        $productoSAHI = ProProducto::where('CodProducto', $codCups)
            ->firstOrFail();

        $producto = new hceEsquemaDetProc;
        $producto->IdDetalleValor = 1;
        $producto->IdEsquemaEncabezado = $conseEsquemaEncavezado;
        $producto->ValNroRegistro = 1;
        $producto->ValVarChar1 = (string) $productoSAHI->IdProducto;
        $producto->ValVarChar2 = (string) $productoSAHI->NomProducto;
        $producto->ValVarChar3 = (string) $productoSAHI->CodLegal;
        $producto->ValVarChar4 = '';
        $producto->ValVarChar5 = '';
        $producto->ValVarChar6 = '';
        $producto->ValVarChar7 = '';
        $producto->ValVarChar8 = '';
        $producto->ValVarChar9 = '';
        $producto->ValVarChar10 = '';
        $producto->ValVarChar11 = '';
        $producto->ValVarChar12 = '';
        $producto->ValVarChar13 = '';
        $producto->ValVarChar14 = '';
        $producto->ValVarChar15 = '';
        $producto->ValVarChar16 = '';
        $producto->ValVarChar17 = '';
        $producto->ValVarChar18 = '';
        $producto->ValVarChar19 = '';
        $producto->ValVarChar20 = '';
        $producto->IdUsuarioR = $idUsuario;
        $producto->FecRegistro = $fecHorLectura;
        $producto->save();

        return $producto;
    }

    /**
     * Obtiene los datos del médico en SAHI a partir del identificador de Lidiag.
     *
     * Flujo del proceso:
     * 1. Busca el usuario médico en la base de datos de LIDiag.
     * 2. Valida que posea configurado el atributo interface_id.
     * 3. Consulta en SAHI la tabla ASI_USUA con join a hcePersonal.
     * 4. Captura posibles caídas de conexión a SQL Server retornando array de error descriptivo.
     *
     * @param  int  $idMedico  Identificador del médico en Lidiag
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function getUsuario(int $idMedico): array|false
    {
        $medicoLidiag = User::find($idMedico);
        if (! $medicoLidiag) {
            return [
                'message' => 'El médico no fue encontrado en el sistema.',
                'status' => 404,
            ];
        }

        if (! $medicoLidiag->interface_id) {
            return [
                'message' => 'Hace falta configurar el Id médico que se encuentra registrado en SAHI, por favor consulte con el administrador del sistema.',
                'status' => 422,
            ];
        }

        try {
            $usuarioSAHI = ASI_USUA::select('ASI_USUA.IdUsuario', 'hcePersonal.IdPersonal', 'hcePersonal.Nompersonal', 'hcePersonal.ApePersonal')
                ->join('hcePersonal', 'ASI_USUA.IdUsuario', 'hcePersonal.IdUsuario')
                ->where('ASI_USUA.IdUsuario', $medicoLidiag->interface_id)
                ->get();

            if ($usuarioSAHI->isEmpty()) {
                return false;
            }

            return $usuarioSAHI->toArray();
        } catch (\Throwable $e) {
            Log::error('Error al consultar médico en SAHI: '.$e->getMessage(), ['idMedico' => $idMedico]);

            return [
                'message' => 'No fue posible consultar el médico en SAHI: '.$e->getMessage(),
                'status' => 503,
            ];
        }
    }

    /**
     * Obtiene la ubicación actual de la atención en SAHI para registrar el esquema.
     *
     * Flujo del proceso:
     * 1. Valida el ID de atención.
     * 2. Consulta IdUbicacionActual en la tabla admAtencion de SAHI.
     * 3. Captura posibles caídas de red retornando null defensivo.
     *
     * @param  int|null  $idAtencion  Identificador de la atención en SAHI
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    private function getUbicacionActual(?int $idAtencion): ?int
    {
        if (! $idAtencion) {
            return null;
        }

        try {
            $ubicacion = Atencion::select('IdUbicacionActual')
                ->where('idAtencion', $idAtencion)
                ->first();

            if (! $ubicacion || ! $ubicacion->IdUbicacionActual) {
                return null;
            }

            return (int) $ubicacion->IdUbicacionActual;
        } catch (\Throwable $e) {
            Log::error('Error al consultar ubicación actual en SAHI: '.$e->getMessage(), ['idAtencion' => $idAtencion]);

            return null;
        }
    }

    /**
     * Consulta si ya existe un esquema registrado en SAHI para la misma atención, CUPS y ventana de tiempo (<= 24h).
     *
     * Flujo del proceso:
     * 1. Consulta la tabla hceEsquemaEncabeza con join a hceEsquemaDetProc en SQL Server.
     * 2. Filtra por IdEsquema = 150, IdAtencion y código CUPS (ValVarChar3).
     * 3. Compara que la diferencia temporal entre el estudio y el registro SAHI sea menor o igual a 24 horas,
     *    garantizando que nuevos controles hospitalarios en días posteriores no sean bloqueados erróneamente.
     * 4. Valida que el esquema padre 149 exista en hceEsquemaEncabeza.
     * 5. Retorna los identificadores de encabezado [149, 150] si ya existe, o null si es nuevo.
     *
     * @param  int  $idAtencion  Identificador de la atención en SAHI
     * @param  string  $codCups  Código CUPS del producto
     * @param  string|null  $fecHorLectura  Fecha y hora de la lectura o estudio
     * @return array|null Identificadores [IdEsquemaEncabezado149, IdEsquemaEncabezado150] o null
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function findExistingEsquemaForAtencionCups(int $idAtencion, string $codCups, ?string $fecHorLectura = null): ?array
    {
        try {
            $query = DB::connection('sqlsrv')
                ->table('hceEsquemaEncabeza as e150')
                ->join('hceEsquemaDetProc as d', 'd.IdEsquemaEncabezado', '=', 'e150.IdEsquemaEncabezado')
                ->where('e150.IdEsquema', 150)
                ->where('e150.idAtencion', $idAtencion)
                ->where('d.IdDetalleValor', 1)
                ->where('d.ValNroRegistro', 1)
                ->where('d.ValVarChar3', $codCups);

            if ($fecHorLectura) {
                $fec = Carbon::parse($fecHorLectura)->format('Y-m-d H:i:s');
                $query->whereRaw('ABS(DATEDIFF(hour, d.FecRegistro, ?)) <= 24', [$fec]);
                $query->orderByRaw('ABS(DATEDIFF(hour, d.FecRegistro, ?)) ASC', [$fec]);
            } else {
                $query->orderByDesc('e150.IdEsquemaEncabezado');
            }

            $row = $query->first([
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
        } catch (\Throwable $e) {
            Log::warning('Error consultando existencia previa de esquema en SAHI: '.$e->getMessage(), [
                'idAtencion' => $idAtencion,
                'codCups' => $codCups,
            ]);

            return null;
        }
    }
}
