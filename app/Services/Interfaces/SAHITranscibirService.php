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

class SAHITranscibirService extends BaseService
{
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
                307
            );
        }

        $usuarioData = $this->getUsuario($idMedico);
        if ($usuarioData === false || is_array($usuarioData) && isset($usuarioData['status'])) {
            return is_array($usuarioData)
                ? $this->error($usuarioData['message'] ?? 'Error', $usuarioData['status'] ?? 500)
                : $this->error('El médico no se encuentra registrado en SAHI.', 500);
        }

        $idUsuario = $usuarioData[0]['IdUsuario'];
        $idPersonal = $usuarioData[0]['IdPersonal'];
        $nomMedico = $usuarioData[0]['Nompersonal'].' '.$usuarioData[0]['ApePersonal'];

        $ubicacionActual = $this->getUbicacionActual($idAtencion);
        if (! $ubicacionActual) {
            return $this->error('No se pudo obtener la ubicación actual de la atención.', 205);
        }

        $fecHorLecturaFmt = str_replace(' ', 'T', $fecHorLectura);

        $conseEsquemaEncavezado = $this->generarConsecutivoEsquemaEncavezado();
        $conseEsquemaEncavezadoPadre = $this->generarConsecutivoEsquemaEncavezadoPadre();

        $esquemaEncabeza = $this->storeEsquemaEncabeza($conseEsquemaEncavezado, $conseEsquemaEncavezado, $ubicacionActual, $idAtencion, $idUsuario, $fecHorLecturaFmt);
        $esquemaEncavezadoAtencion = $this->storeEsquemaAtencion($conseEsquemaEncavezado, $ubicacionActual, $idAtencion, $idPersonal, $idUsuario, $fecHorLecturaFmt);
        $esquemaMemo = $this->storeEsquemaMemo($conseEsquemaEncavezado, $reporteEstudio);
        $medico = $this->storeMedico($conseEsquemaEncavezado, $fecHorLecturaFmt, $nomMedico, $idUsuario);

        $conseEsquemaEncavezado2 = $this->generarConsecutivoEsquemaEncavezado();
        $esquemaEncabeza2 = $this->storeEsquemaEncabeza($conseEsquemaEncavezado, $conseEsquemaEncavezado2, $ubicacionActual, $idAtencion, $idUsuario, $fecHorLecturaFmt);

        $producto = $this->storaProducto($conseEsquemaEncavezado2, $codCups, $idUsuario, $fecHorLecturaFmt);

        return $this->success([
            'conseEsquemaEncavezado' => $conseEsquemaEncavezado,
            'conseEsquemaEncavezadoPadre' => $conseEsquemaEncavezado,
            'esquemaEncabeza' => $esquemaEncabeza,
            'esquemaEncavezadoAtencion' => $esquemaEncavezadoAtencion,
            'esquemaMemo' => $esquemaMemo,
            'medico' => $medico,
            'conseEsquemaEncavezado2' => $conseEsquemaEncavezado2,
            'esquemaEncabeza2' => $esquemaEncabeza2,
            'producto' => $producto,
        ], 'La transcripción se realizó con éxito.');
    }

    private function generarConsecutivoEsquemaEncavezado(): int
    {
        $esquema = sisTabla::where('NomTabla', 'hceEsquemaEncabeza')
            ->where('IdTabla', '>', 0)
            ->firstOrFail();
        $esquema->Consecutivo = $esquema->Consecutivo + 1;
        $esquema->save();

        return $esquema->Consecutivo;
    }

    private function generarConsecutivoEsquemaEncavezadoPadre()
    {
        $encavezadoPadre = HceEsquema::where('IdEsquema', 149)
            ->firstOrFail();
        $encavezadoPadre->IdConsecutivoEsq = $encavezadoPadre->IdConsecutivoEsq + 1;

        return $encavezadoPadre;
    }

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

    private function storeEsquemaMemo(int $conseEsquemaEncavezado, string $reporteEstudio): hceEsquemaDetMemo
    {
        $esquemaMemo = new hceEsquemaDetMemo;
        $esquemaMemo->IdMemo = 1;
        $esquemaMemo->IdEsquemaEncabezado = $conseEsquemaEncavezado;
        $esquemaMemo->DesMemo = $reporteEstudio;
        $esquemaMemo->save();

        return $esquemaMemo;
    }

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

    private function storaProducto(int $conseEsquemaEncavezado, string $codCups, int $idUsuario, string $fecHorLectura)
    {
        $productoSAHI = ProProducto::where('CodProducto', $codCups)
            ->firstOrFail();

        if ($productoSAHI) {
            $producto = new hceEsquemaDetProc;
            $producto->IdDetalleValor = 1;
            $producto->IdEsquemaEncabezado = $conseEsquemaEncavezado;
            $producto->ValNroRegistro = 1;
            $producto->ValVarChar1 = $productoSAHI->IdProducto;
            $producto->ValVarChar2 = $productoSAHI->NomProducto;
            $producto->ValVarChar3 = $productoSAHI->CodLegal;
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

        return false;
    }

    private function getUsuario(int $idMedico)
    {
        $medicoLidiag = User::find($idMedico);
        if ($medicoLidiag->interface_id == null) {
            return [
                'message' => 'Hace falta configurar el Id médico que se encuentra registrado en SAHI, por favor consulte con el administrador del sistema.',
                'status' => 307,
                'statusText' => 'Médico si Id',
            ];
        }

        $usuarioSAHI = ASI_USUA::select('ASI_USUA.IdUsuario', 'hcePersonal.IdPersonal', 'hcePersonal.Nompersonal', 'hcePersonal.ApePersonal')
            ->join('hcePersonal', 'ASI_USUA.IdUsuario', 'hcePersonal.IdUsuario')
            ->where('ASI_USUA.IdUsuario', $medicoLidiag->interface_id)
            ->get();

        if ($usuarioSAHI) {
            return $usuarioSAHI;
        }

        return false;
    }

    private function getUbicacionActual(int $idAtencion)
    {
        $ubicacion = Atencion::select('IdUbicacionActual')
            ->where('idAtencion', $idAtencion)
            ->get();

        if (! $ubicacion) {
            return false;
        }

        return $ubicacion;
    }
}
