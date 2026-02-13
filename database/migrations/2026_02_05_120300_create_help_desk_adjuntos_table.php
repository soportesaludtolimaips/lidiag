<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_desk_adjuntos', function (Blueprint $table) {
            $table->id();

            // Adjuntos ligados al historial/evento (mejor trazabilidad)
            $table->unsignedBigInteger('historial_id');
            $table->foreign('historial_id')->references('id')->on('help_desk_ticket_historial')->onDelete('cascade');

            $table->unsignedBigInteger('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('help_desk_tickets')->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            // Metadata
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('path', 500);
            $table->string('mime', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_desk_adjuntos');
    }
};
