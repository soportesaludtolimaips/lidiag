<?php

namespace App\Http\Resources\Configuracion;

use Illuminate\Http\Resources\Json\JsonResource;

class ConfigAdminSaludResource extends JsonResource
{
    /**
     * Datos de la administradora de salud en formato array.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'cod_admin_salud' => $this->cod_admin_salud,
            'nit_admin_salud' => $this->nit_admin_salud,
            'nom_admin_salud' => $this->nom_admin_salud,
            'estado' => $this->estado,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
