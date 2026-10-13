<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Datos del usuario en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'num_docu' => $this->num_docu,
            'reg_med' => $this->reg_med,
            'name' => $this->name,
            'email' => $this->email,
            'tel' => $this->tel,
            'estado' => $this->estado,
            'interface_id' => $this->interface_id,
            'transcrip_remoto' => $this->transcrip_remoto,
            'firma' => $this->firma,
            'imagen_perfil' => $this->imagen_perfil,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'modulos' => ModuleResource::collection($this->whenLoaded('modulos')),
            'sedes' => SedeResource::collection($this->whenLoaded('sedes')),
            'serviciosCita' => ServicioCitaResource::collection($this->whenLoaded('serviciosCita')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
