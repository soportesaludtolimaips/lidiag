<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_desk_ticket_historial', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ticket_id');
            $table->foreign('ticket_id')->references('id')->on('help_desk_tickets')->onDelete('cascade');

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->enum('tipo', [
                'CREADO',
                'COMENTARIO',
                'CAMBIO_ESTADO',
                'REASIGNADO',
                'ADJUNTO_AGREGADO',
                'ADJUNTO_ELIMINADO',
            ]);

            // Para trazabilidad (opcional según evento)
            $table->unsignedBigInteger('estado_anterior_id')->nullable();
            $table->unsignedBigInteger('estado_nuevo_id')->nullable();
            $table->unsignedBigInteger('asignado_anterior_id')->nullable();
            $table->unsignedBigInteger('asignado_nuevo_id')->nullable();
            $table->unsignedBigInteger('categoria_anterior_id')->nullable();
            $table->unsignedBigInteger('categoria_nueva_id')->nullable();
            $table->unsignedBigInteger('prioridad_anterior_id')->nullable();
            $table->unsignedBigInteger('prioridad_nueva_id')->nullable();

            $table->longText('comentario')->nullable(); // HTML/WYSIWYG (sanitizado en backend)

            $table->timestamps();

            $table->foreign('estado_anterior_id')->references('id')->on('help_desk_estados');
            $table->foreign('estado_nuevo_id')->references('id')->on('help_desk_estados');
            $table->foreign('asignado_anterior_id')->references('id')->on('users');
            $table->foreign('asignado_nuevo_id')->references('id')->on('users');
            $table->foreign('categoria_anterior_id')->references('id')->on('help_desk_categorias');
            $table->foreign('categoria_nueva_id')->references('id')->on('help_desk_categorias');
            $table->foreign('prioridad_anterior_id')->references('id')->on('help_desk_prioridades');
            $table->foreign('prioridad_nueva_id')->references('id')->on('help_desk_prioridades');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_desk_ticket_historial');
    }
};
