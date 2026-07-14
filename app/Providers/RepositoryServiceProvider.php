<?php

namespace App\Providers;

use App\Services\Contracts\FileServiceInterface;
use App\Services\FileService;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FileServiceInterface::class, FileService::class);
    }

    public function boot(): void
    {
        //
    }
}
