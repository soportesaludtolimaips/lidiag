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
        Schema::create('agenda_disponibilidas_detalle', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agenda_disponibilida_id');
            $table->foreign('agenda_disponibilida_id')->references('id')->on('agenda_disponibilida');
            $table->datetimes('fecha_hora_inicio', 'fecha_hora_fin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_disponibilidas_detalle');
    }
};
