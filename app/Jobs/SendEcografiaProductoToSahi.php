<?php

namespace App\Jobs;

use App\Services\Interface\SahiEcografiasSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEcografiaProductoToSahi implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $ecografiaProductoId;

    public int $tries = 3;

    /**
     * Backoff en segundos (1m, 5m, 15m).
     */
    public array $backoff = [60, 300, 900];

    public function __construct(int $ecografiaProductoId)
    {
        $this->ecografiaProductoId = $ecografiaProductoId;
        $this->onQueue('sahi');
    }

    public function handle(SahiEcografiasSender $sender): void
    {
        $result = $sender->send($this->ecografiaProductoId);

        // Reintentar solo errores transitorios (>= 500)
        if (!$result['ok'] && ($result['status'] ?? 500) >= 500) {
            throw new \RuntimeException($result['message'] ?? 'Error transitorio enviando ecografía a SAHI.');
        }
    }
}
