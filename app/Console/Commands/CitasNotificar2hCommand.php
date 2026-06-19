<?php

namespace App\Console\Commands;

use App\Models\Agenda\AgendaCita;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class CitasNotificar2hCommand extends Command
{
    protected $signature = 'citas:notificar-2h';

    protected $description = 'Envía recordatorios de citas 2 horas antes';

    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        parent::__construct();
        $this->whatsAppService = $whatsAppService;
    }

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
