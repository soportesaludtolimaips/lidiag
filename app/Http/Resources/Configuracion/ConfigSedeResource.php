<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigSedeResource extends JsonResource
{
    /**
     * Datos de la sede de configuración en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'nom_sede' => $this->nom_sede,
            'email_repor' => $this->email_repor,
            'ip_dcm' => $this->ip_dcm,
            'bd_dcm' => $this->bd_dcm,
            'puerto_dcm' => $this->puerto_dcm,
            'usuario_oviyam' => $this->usuario_oviyam,
            'url_oviyam' => $this->url_oviyam,
            'tap_oviyam' => $this->tap_oviyam,
            'encabezado' => $this->encabezado,
            'pie_pagina' => $this->pie_pagina,
            'logo_1' => $this->logo_1,
            'logo_2' => $this->logo_2,
            'estado' => $this->estado,
            'interface_software' => $this->interface_software,
            'interface_driver' => $this->interface_driver,
            'interface_host' => $this->interface_host,
            'interface_port' => $this->interface_port,
            'interface_database' => $this->interface_database,
            'interface_username' => $this->interface_username,
            'plantilla_rx' => $this->plantilla_rx,
            'plantilla_ecografia' => $this->plantilla_ecografia,
            'servicios' => ConfigServicioResource::collection($this->whenLoaded('servicios')),
            'whatsappNumbers' => ConfigSedeWhatsappNumberResource::collection($this->whenLoaded('whatsappNumbers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
