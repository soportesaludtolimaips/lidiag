<?php

use App\Http\Controllers\FileAccessController;
use App\Http\Middleware\ProtectFiles;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', ProtectFiles::class])
    ->group(function () {
        Route::get('/files/{disk}/{path}', [FileAccessController::class, 'serve'])
            ->where('path', '.*');
    });
