<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('loggable_type');
            $table->unsignedBigInteger('loggable_id');
            $table->string('event');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['loggable_type', 'loggable_id'], 'idx_activity_loggable');
            $table->index('event', 'idx_activity_event');
            $table->index('created_at', 'idx_activity_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
