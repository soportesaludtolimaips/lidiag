<?php

namespace App\Providers;

use App\Services\Contracts\FileServiceInterface;
use App\Services\FileService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Vincula las interfaces de servicios con sus implementaciones.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function register(): void
    {
        $this->app->bind(FileServiceInterface::class, FileService::class);
    }

    /**
     * Tareas de arranque del proveedor de servicios (sin implementación).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    public function boot(): void
    {
        //
    }
}
