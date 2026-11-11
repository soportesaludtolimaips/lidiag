<?php

namespace App\Console\Commands;

use App\Models\Agenda\AgendaCita;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Comando Artisan que envía recordatorios de citas por WhatsApp para el día siguiente.
 *
 * Consulta las citas asignadas o confirmadas programadas para mañana que no hayan
 * recibido el recordatorio de 24 horas. Utiliza WhatsAppService para enviar el
 * mensaje y actualiza el estado de la cita según el resultado del envío.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
class CitasNotificarCommand extends Command
{
    protected $signature = 'citas:notificar';

    protected $description = 'Envía recordatorios de citas para el día siguiente';

    protected $whatsAppService;

    /**
     * Constructor del comando.
     *
     * @param  WhatsAppService  $whatsAppService  Servicio para envío de mensajes por WhatsApp.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function __construct(WhatsAppService $whatsAppService)
    {
        parent::__construct();
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Ejecuta el comando para enviar recordatorios de citas del día siguiente.
     *
     * Consulta las citas con estado 'asignada' o 'confirmada' cuya fecha de inicio
     * sea mañana y que no hayan enviado el recordatorio de 24 horas. Para cada cita,
     * envía el recordatorio vía WhatsApp, actualiza el estado de envío en la base de
     * datos y lleva un conteo de éxitos y fallos.
     *
     * @return int 0 si todas las notificaciones se enviaron correctamente, 1 si hubo fallos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function handle()
    {
        $this->info('Iniciando envío de recordatorios de citas...');

        $citas = AgendaCita::whereIn('estado', ['asignada', 'confirmada'])
            ->whereDate('fecha_hora_inicio', now()->addDay()->toDateString())
            ->where('recordatorio_24h_enviado', false)
            ->with(['paciente', 'sede'])
            ->get();

        if ($citas->isEmpty()) {
            $this->info('No hay citas para mañana pendientes de notificar.');

            return 0;
        }

        $this->info("Encontradas {$citas->count()} citas para mañana.");

        $enviados = 0;
        $fallidos = 0;

        foreach ($citas as $cita) {
            try {
                $resultado = $this->whatsAppService->enviarRecordatorio($cita);

                if ($resultado['success']) {
                    $cita->update([
                        'whatsapp_estado' => 'sent',
                        'whatsapp_mid' => $resultado['message_id'],
                        'recordatorio_24h_enviado' => true,
                    ]);
                    $this->info("Recordatorio 24h enviado a cita #{$cita->id}");
                    $enviados++;
                } else {
                    $cita->update(['whatsapp_estado' => 'failed']);
                    $this->error("Error enviando a cita #{$cita->id}: {$resultado['error']}");
                    $fallidos++;
                }
            } catch (\Exception $e) {
                $this->error("Excepción para cita #{$cita->id}: {$e->getMessage()}");
                $fallidos++;
            }
        }

        $this->info("Completado. Enviados: {$enviados}, Fallidos: {$fallidos}");

        return $fallidos > 0 ? 1 : 0;
    }
}
