<?php

namespace App\Notifications\Citas;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notificación que se genera cuando un paciente solicita una nueva cita vía WhatsApp.
 *
 * Registra en la base de datos los datos de la solicitud de cita, incluyendo
 * el ID de la cita, el nombre del paciente, el servicio y la sede. Esta notificación
 * se almacena en la tabla de notificaciones para su visualización en el panel de administración.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-07-08
 */
class CitaSolicitadaNotification extends Notification
{
    use Queueable;

    /**
     * Constructor de la notificación.
     *
     * @param  int  $citaId  ID de la cita solicitada.
     * @param  string  $pacienteNombre  Nombre completo del paciente que solicitó la cita.
     * @param  string|null  $servicio  Nombre del servicio solicitado (puede ser nulo).
     * @param  string|null  $sede  Nombre de la sede donde se solicita la cita (puede ser nula).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-08
     */
    public function __construct(
        public readonly int $citaId,
        public readonly string $pacienteNombre,
        public readonly ?string $servicio,
        public readonly ?string $sede,
    ) {}

    /**
     * Define los canales por los que se envía esta notificación.
     *
     * @param  object  $notifiable  Instancia del modelo notificable.
     * @return array<int, string> Lista de canales (solo 'database').
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-08
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Convierte la notificación en un array para almacenamiento en base de datos.
     *
     * @param  object  $notifiable  Instancia del modelo notificable.
     * @return array{ cita_id: int, paciente_nombre: string, servicio: string|null, sede: string|null, evento: string, mensaje: string, url: string } Datos serializados de la notificación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-08
     */
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
