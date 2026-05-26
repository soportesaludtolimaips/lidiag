<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('telefono', 20)->unique();
            $table->unsignedBigInteger('cita_id')->nullable();
            $table->string('paso', 50)->default('inicio');
            $table->json('datos')->nullable();
            $table->timestamp('ultimo_mensaje')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
