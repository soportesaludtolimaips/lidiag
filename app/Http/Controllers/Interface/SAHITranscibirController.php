<?php

namespace App\Http\Controllers\Interface;

use App\Http\Controllers\Controller;
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
use Carbon\Carbon;
use Illuminate\Http\Request;

class SAHITranscibirController extends Controller
{
    private $idAtencion;
    private $idMedico;
    private $IdUsuario;
    private $IdPersonal;
    private $nomMedico;
    private $reporteEstudio;
    private $codCups;
    private $messageArray = array();

    public function __construct($idAtencion = null, $idMedico = null, $reporteEstudio = null, $codCups = null)
    {
        $this->idAtencion = $idAtencion;
        $this->idMedico = $idMedico;
        $this->reporteEstudio = $reporteEstudio;
        $this->codCups = $codCups;
    }

    //Set log message
    private function logMessage($message)
    {
        $this->messageArray[] = $message;
    }

    //retrieve the message
    public function getMessages()
    {
        return $this->messageArray;
    }


    public function pr($arrData)
    {
        //print_r($arrData);
        return $arrData;
    }

    public function get_idAtencion()
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

    public function set_reporteEstudio($reporteEstudio)
    {
        $this->reporteEstudio = $reporteEstudio;
    }

    public function set_codCups($codCups)
    {
        $this->codCups = $codCups;
    }

    public function enviarEstudio($idAtencion = null, $idMedico = null, $reporteEstudio = null, $codCups = null)
    {

        $this->idAtencion = $idAtencion;
        $this->idMedico = $idMedico;
        $this->reporteEstudio = $reporteEstudio;
        $this->codCups = $codCups;

        /**
         * Establecer los datos del medico por medio del ID del usuario
         */
        $this->IdUsuario = $this->getUsuario()[0]->IdUsuario;
        $this->IdPersonal = $this->getUsuario()[0]->IdPersonal;
        $this->nomMedico = $this->getUsuario()[0]->Nompersonal . " " . $this->getUsuario()[0]->ApePersonal;

        if (!$this->IdUsuario) {
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
        if ($ubucacionActual) {
            return response()->json([
                'message' => 'No se pudo obtener la ubicación actual de la atención.',
                'status' => 205
            ]);
        } else {
            return "okkkkk";
        }

        /**
         * GENERO EL CONSECUTIVO DEL ENCABEZADO DEL ESQUEMA
         */
        $conseEsquemaEncavezado = $this->generarConsecutivoEsquemaEncavezado();

        /**
         * GENERO EL CONSECUTIVO DEL ENCABEZADO PADRE
         */
        $conseEsquemaEncavezadoPadre = $this->generarConsecutivoEsquemaEncavezadoPadre();

        /**
         * INSERTO EL ENCABEZADO DEL ESQUEMA
         */
        $esquemaEncabeza = $this->storeEsquemaEncabeza($conseEsquemaEncavezado, $conseEsquemaEncavezado, $ubucacionActual);

        /**
         * INSERTO EL EL ESQUEMA DE LA ATENCION
         */
        $esquemaEncavezadoAtencion = $this->storeEsquemaAtencion($conseEsquemaEncavezado, $ubucacionActual);

        /**
         * INSERTO EL ESQUEMA MEMO
         */
        $esquemaMemo = $this->storeEsquemaMemo($conseEsquemaEncavezado, $ubucacionActual);

        /**
         * INSERTO EL MEDICO
         */
        $medico = $this->storeMedico($conseEsquemaEncavezado);

        /**
         * GENERO EL CONSECUTIVO DEL SEGUNDO ENCABEZADO DEL ESQUEMA
         */
        $conseEsquemaEncavezado2 = $this->generarConsecutivoEsquemaEncavezado();

        /**
         * INSERTO EL SEGUNDO ESQUEMA ENCABEZADO
         */
        $esquemaEncabeza2 = $this->storeEsquemaEncabeza($conseEsquemaEncavezado, $conseEsquemaEncavezado2, $ubucacionActual);

        /**
         * INSERTO LOS PROCEDIMIENTOS QUE SE LE REALIZARON AL PACIENTE
         */
        $producto = $this->storaProducto($conseEsquemaEncavezado2);
        //return "Producto: " . $producto;
    }

    private function generarConsecutivoEsquemaEncavezado()
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

    private function storeEsquemaEncabeza($conseEsquemaEncavezado1, $conseEsquemaEncavezado2, $ubucacionActual)
    {
        $esquemaEncavezado = new hceEsquemaEncabeza();
        $esquemaEncavezado->IdEsquemaEncabezado = $conseEsquemaEncavezado2;
        $esquemaEncavezado->IdEsquema = '149';
        $esquemaEncavezado->idEncabezadoPadre = $conseEsquemaEncavezado1;
        $esquemaEncavezado->ValEsquemaConsecutivo = '1';
        $esquemaEncavezado->valDetConsecutivo = '0';
        $esquemaEncavezado->IdUbicacion = $ubucacionActual;
        $esquemaEncavezado->idAtencion = $this->idAtencion;
        $esquemaEncavezado->ValUltimoNroRegistro = '1';
        $esquemaEncavezado->IndActivado = '1';
        $esquemaEncavezado->IndHabilitado = '1';
        $esquemaEncavezado->IdUsuarioR = $this->IdUsuario;
        $esquemaEncavezado->FecRegistro = Carbon::now()->toTimeString();
        $esquemaEncavezado->IdProductoR = '0';
        $esquemaEncavezado->NomProducto = '';
        $esquemaEncavezado->CodProducto = '';
        $esquemaEncavezado->IdEstudio = '';
        $esquemaEncavezado->save();

        return $esquemaEncavezado;
    }

    private function storeEsquemaAtencion($conseEsquemaEncavezado, $ubucacionActual)
    {
        $esquemAtenicon = new hceEsquemasdeAte();
        $esquemAtenicon->IdEsquemadeAtencion = $conseEsquemaEncavezado;
        $esquemAtenicon->IdEsquema = 149;
        $esquemAtenicon->IdAtencion = $this->idAtencion;
        $esquemAtenicon->IdUbicacion = $ubucacionActual;
        $esquemAtenicon->idTraslado = 1;
        $esquemAtenicon->IdMedico = $this->IdPersonal;
        $esquemAtenicon->FecEsquema = Carbon::now()->toTimeString();
        $esquemAtenicon->IndHabilitado =  1;
        $esquemAtenicon->IndActivado = 1;
        $esquemAtenicon->FecCerrado = Carbon::now()->toTimeString();
        $esquemAtenicon->IdUsuarioR = $this->IdUsuario;
        $esquemAtenicon->save();

        return $esquemAtenicon;
    }

    private function storeEsquemaMemo($conseEsquemaEncavezado, $ubucacionActual)
    {
        $esquemaMemo = new hceEsquemaDetMemo();
        $esquemaMemo->IdMemo = 1;
        $esquemaMemo->IdEsquemaEncabezado = $conseEsquemaEncavezado;
        $esquemaMemo->DesMemo = $this->reporteEstudio;
        $esquemaMemo->save();

        return $esquemaMemo;
    }

    private function storeMedico($conseEsquemaEncavezado)
    {
        $medico = new hceEsquemaDetProc();
        $medico->IdDetalleValor = 1;
        $medico->IdEsquemaEncabezado = $conseEsquemaEncavezado;
        $medico->ValNroRegistro = 1;
        $medico->ValVarChar1 = Carbon::now()->toTimeString();
        $medico->ValVarChar2 = Carbon::now()->toTimeString();
        $medico->ValVarChar3 = '';
        $medico->ValVarChar4 = 'MEMO1';
        $medico->ValVarChar5 = '';
        $medico->ValVarChar6 = '';
        $medico->ValVarChar7 = '0';
        $medico->ValVarChar8 = '1551';
        $medico->ValVarChar9 = $this->nomMedico;
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
        $medico->IdUsuarioR = $this->IdUsuario;
        $medico->FecRegistro = Carbon::now()->toTimeString();
        $medico->save();
    }

    private function storaProducto($conseEsquemaEncavezado)
    {
        /**
         * BUSCO EL PRODUCTO EN SAHI
         */
        $productoSAHI = ProProducto::where('CodProducto', $this->codCups)
            ->firstOrFail();

        if ($productoSAHI) {
            $producto = new hceEsquemaDetProc();
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
            $producto->IdUsuarioR = $this->IdUsuario;
            $producto->FecRegistro = Carbon::now()->toTimeString();
            $producto->save();

            return true;
        } else {
            $this->logMessage('El producto no se encontró en SAHI');
            return false;
        }
    }

    private function getUsuario()
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

        $usuarioSAHI = ASI_USUA::select('ASI_USUA.IdUsuario', 'hcePersonal.IdPersonal', 'hcePersonal.Nompersonal', 'hcePersonal.ApePersonal')
            ->join('hcePersonal', 'ASI_USUA.IdUsuario', 'hcePersonal.IdUsuario')
            ->where('ASI_USUA.IdUsuario', $medicoLidiag->interface_id)
            ->get();

        return $usuarioSAHI;
    }

    private function getUbicacionActual()
    {
        $ubicacion = Atencion::select("IdUbicacionActual")
            ->where('idAtencion', $this->idAtencion)
            ->get();

        if (!$ubicacion) {
            return false;
        } else {
            return $ubicacion[0]->IdUbicacionActual;
        }
    }
}
