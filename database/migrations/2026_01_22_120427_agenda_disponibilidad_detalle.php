<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agenda_disponibilidad_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agenda_disponibilida_id');
            // $table->foreign('agenda_disponibilida_id')->references('id')->on('agenda_disponibilidad');
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin');
            $table->enum('estado', ['Disponible', 'Reservado', 'Cancelado'])->default('Disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_disponibilidad_detalle');
    }
};
