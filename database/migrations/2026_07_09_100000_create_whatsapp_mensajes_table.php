<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_conversation_id');
            $table->string('role', 10);
            $table->string('tipo', 20);
            $table->text('contenido')->nullable();
            $table->string('media_url', 500)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('whatsapp_conversation_id')
                ->references('id')
                ->on('whatsapp_conversations')
                ->cascadeOnDelete();

            $table->index('whatsapp_conversation_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_mensajes');
    }
};
