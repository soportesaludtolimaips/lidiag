<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
use App\Models\Interface\Sahi\ASI_USUA;
use App\Models\Interface\Sahi\Atencion;
use App\Models\Interface\Sahi\hceEsquemaEncabeza;
use App\Models\Interface\Sahi\sisTabla;
use App\Models\User;
use Carbon\Carbon;

class SAHITranscibirController extends Controller
{
    private $idAtencion;
    private $idMedico;

    public function __construct($idAtencion = null, $idMedico = null)
    {
        $this->idAtencion = $idAtencion;
        $this->idMedico = $idMedico;
    }

    /* public function get_idAtencion()
    {
        return $this->idAtencion;
    }

    public function get_idMedico()
    {
        return $this->idMedico;
    }

    public function set_idAtencion($idAtencion)
    {
        $this->idAtencion = $idAtencion;
    }

    public function set_idMedico($idMedico)
    {
        $this->idMedico = $idMedico;
    }
*/
    public function enviarEstudio($idAtencion = null, $idMedico = null)
    {
        $this->idAtencion = $idAtencion;
        $this->idMedico = $idMedico;

        /**
         * Establecer los datos del medico por medio del ID del usuario
         */
        $medico = $this->getMedico();
        if (!$medico) {
            return response()->json([
                'message' => 'El médico no se encuentra registrado en SAHI, por favor consulte con el administrador del sistema.',
                'status' => 307,
                'statusText' => "NMédico no encontrado en SAHI"
            ]);
        }

        /**
         * Busco la ubicación actual de la atención
         */
        $ubucacionActual = $this->getUbicacionActual();

        /**
         * GENERO EL CONSECUTIVO DEL ENCABEZADO DEL ESQUEMA
         */
        $esquema = $this->generarConsecutivoEsquema();
        return $esquema;

        /**
         * GENERO EL CONSECUTIVO DEL ENCABEZADO PADRE
         */
        $conseEsquema = $this->generarConsecutivoEncavezadoPadre();

        /**
         * INSERTO EL ENCABEZADO DEL ESQUEMA
         */
        //$esquemaEncabeza = $this->storeEsquemaEncabeza($esquema, $conseEsquema, $ubucacionActual, $this->idAtencion, $this->idMedico);
    }

    private function generarConsecutivoEsquema()
    {
        $esquema = sisTabla::select('Consecutivo')
            ->where('IdTabla', '>', 0)
            ->where('NomTabla', 'hceEsquemaEncabeza')
            ->first();

        $esquema->Consecutivo = $esquema->Consecutivo + 1;
        $esquema->save();


        return $esquema->toSql();
    }

    private function generarConsecutivoEncavezadoPadre()
    {
        $encavezadoPadre = sisTabla::select('IdConsecutivoEsq')->where('IdEsquema', '>', 149)
            ->increment('IdConsecutivoEsq');

        return $encavezadoPadre;
    }

    private function storeEsquemaEncabeza($conseEsquema, $conseEsquemaPadre, $ubucacionActual, $idAtencion, $idMedicoSAHI)
    {
        $esquemaEncavezado = new hceEsquemaEncabeza();
        $esquemaEncavezado->IdEsquemaEncabezado = $conseEsquema;
        $esquemaEncavezado->IdEsquema = '149';
        $esquemaEncavezado->idEncabezadoPadre = $conseEsquemaPadre;
        $esquemaEncavezado->ValEsquemaConsecutivo = '1';
        $esquemaEncavezado->valDetConsecutivo = '0';
        $esquemaEncavezado->IdUbicacion = $ubucacionActual;
        $esquemaEncavezado->idAtencion = $idAtencion;
        $esquemaEncavezado->ValUltimoNroRegistro = '1';
        $esquemaEncavezado->IndActivado = '1';
        $esquemaEncavezado->IndHabilitado = '1';
        $esquemaEncavezado->IdUsuarioR = $idMedicoSAHI;
        $esquemaEncavezado->FecRegistro = Carbon::now()->toTimeString();
        $esquemaEncavezado->IdProductoR = '0';
        $esquemaEncavezado->NomProducto = '';
        $esquemaEncavezado->CodProducto = '';
        $esquemaEncavezado->IdEstudi = '';
        $esquemaEncavezado->save();
    }

    private function getMedico()
    {
        /**
         * Establecer los datos del medico por medio del ID del usuario de Lidiag
         */
        $medicoLidiag = User::find($this->idMedico);
        if ($medicoLidiag->interface_id == null) {
            return response()->json([
                'message' => 'Hace falta  configurar el Id médico que se encuentra registrado en SAHI, por favor consulte con el administrador del sistema.',
                'status' => 307,
                'statusText' => "Médico si Id"
            ]);
        }

        $medico = ASI_USUA::select('ASI_USUA.IdUsuario', 'hcePersonal.IdPersonal')
            ->join('hcePersonal', 'ASI_USUA.IdUsuario', 'hcePersonal.IdUsuario')
            ->where('ASI_USUA.IdUsuario', $medicoLidiag->interface_id)
            ->get();

        return $medico;
    }

    private function getUbicacionActual()
    {
        $ubicacion = Atencion::select("IdUbicacionActual")
            ->where('idAtencion', $this->idAtencion)
            ->get();

        return $ubicacion;
    }
}
