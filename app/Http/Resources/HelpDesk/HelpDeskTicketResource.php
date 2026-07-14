<?php

namespace App\Http\Resources\HelpDesk;

use Illuminate\Http\Resources\Json\JsonResource;

class HelpDeskTicketResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'categoria_id' => $this->categoria_id,
            'prioridad_id' => $this->prioridad_id,
            'estado_id' => $this->estado_id,
            'quien_registra_id' => $this->quien_registra_id,
            'sedes_id' => $this->sedes_id,
            'quien_atiende_id' => $this->quien_atiende_id,
            'quien_soluciona_id' => $this->quien_soluciona_id,
            'descripcion_solucion' => $this->descripcion_solucion,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'asignado_visto_at' => $this->asignado_visto_at,
            'pendiente_de' => $this->pendiente_de,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
