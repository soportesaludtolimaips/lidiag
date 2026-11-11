<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class ResetPermissionCacheSafe extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:cache-reset
                            {--guard= : No-op (compat). Spatie cache es global por guard_name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia el cache de permisos (Spatie) sin fallar si el key no existe.';

    /**
     * Limpia el cache de permisos de Spatie sin fallar si la clave no existe.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function handle(): int
    {
        // Limpieza interna de Spatie (siempre segura)
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $key = (string) config('permission.cache.key', 'spatie.permission.cache');
        $store = (string) config('permission.cache.store', 'default');
        $store = $store === '' ? 'default' : $store;
        $store = $store === 'default' ? (string) config('cache.default', 'file') : $store;

        $cache = Cache::store($store);

        try {
            // Nota: Cache::forget() puede retornar false si el key NO existe (comportamiento típico con file store).
            // Eso NO es un error real; lo único preocupante sería una excepción (permisos/driver roto).
            $forgot = (bool) $cache->forget($key);

            $this->info("OK: cache de permisos reseteado (store={$store}, key={$key}, deleted=".($forgot ? 'true' : 'false').').');

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Error reseteando cache de permisos (store={$store}, key={$key}): ".$e->getMessage());

            return Command::FAILURE;
        }
    }
}
