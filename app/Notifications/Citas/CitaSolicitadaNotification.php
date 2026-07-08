<?php

namespace App\Notifications\Citas;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CitaSolicitadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly int $citaId,
        public readonly string $pacienteNombre,
        public readonly ?string $servicio,
        public readonly ?string $sede,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'cita_id' => $this->citaId,
            'paciente_nombre' => $this->pacienteNombre,
            'servicio' => $this->servicio,
            'sede' => $this->sede,
            'evento' => 'nueva_cita_whatsapp',
            'mensaje' => "Nueva cita solicitada: {$this->pacienteNombre}",
            'url' => '/citas/revision',
        ];
    }
}
