<?php

use App\Http\Controllers\Telegram\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::post("/telegram/webhook", [TelegramWebhookController::class, "webhook"])
    ->name("api.telegram.webhook");
