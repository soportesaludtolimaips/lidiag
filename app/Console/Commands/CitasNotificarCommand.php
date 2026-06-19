<?php

namespace App\Console\Commands;

use App\Models\Agenda\AgendaCita;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class CitasNotificarCommand extends Command
{
    protected $signature = 'citas:notificar';

    protected $description = 'Envía recordatorios de citas para el día siguiente';

    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        parent::__construct();
        $this->whatsAppService = $whatsAppService;
    }

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
