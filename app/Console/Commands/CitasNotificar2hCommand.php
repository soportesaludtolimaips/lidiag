<?php

namespace App\Console\Commands;

use App\Models\Agenda\AgendaCita;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

/**
 * Comando Artisan que envía recordatorios de citas por WhatsApp 2 horas antes de su inicio.
 *
 * Consulta las citas asignadas o confirmadas cuya fecha de inicio esté dentro de las
 * próximas 2 horas y que no hayan recibido el recordatorio. Utiliza WhatsAppService
 * para enviar el mensaje y actualiza el estado de la cita según el resultado.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-05-26
 */
class CitasNotificar2hCommand extends Command
{
    protected $signature = 'citas:notificar-2h';

    protected $description = 'Envía recordatorios de citas 2 horas antes';

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
     * Ejecuta el comando para enviar recordatorios de citas 2 horas antes.
     *
     * Consulta las citas con estado 'asignada' o 'confirmada' cuya fecha de inicio
     * esté entre ahora y las próximas 2 horas, y que no hayan enviado el recordatorio
     * de 2 horas. Para cada cita, envía el recordatorio vía WhatsApp y actualiza
     * el estado de envío en la base de datos.
     *
     * @return int 0 si todas las notificaciones se enviaron correctamente, 1 si hubo fallos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-05-26
     */
    public function handle()
    {
        $this->info('Iniciando envío de recordatorios 2 horas antes...');

        $citas = AgendaCita::whereIn('estado', ['asignada', 'confirmada'])
            ->whereBetween('fecha_hora_inicio', [now(), now()->addHours(2)])
            ->where('recordatorio_2h_enviado', false)
            ->with(['paciente', 'sede'])
            ->get();

        if ($citas->isEmpty()) {
            $this->info('No hay citas próximas pendientes de notificar.');

            return 0;
        }

        $this->info("Encontradas {$citas->count()} citas próximas.");

        $enviados = 0;
        $fallidos = 0;

        foreach ($citas as $cita) {
            try {
                $resultado = $this->whatsAppService->enviarRecordatorio2h($cita);

                if ($resultado['success']) {
                    $cita->update([
                        'recordatorio_2h_enviado' => true,
                        'whatsapp_mid' => $resultado['message_id'],
                    ]);
                    $this->info("Recordatorio 2h enviado a cita #{$cita->id}");
                    $enviados++;
                } else {
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
