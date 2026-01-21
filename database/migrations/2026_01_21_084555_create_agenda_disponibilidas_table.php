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
        Schema::create('agenda_disponibilida', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quien_registro_id');
            $table->foreignId('quien_registro_id')->references('id')->on('users');

            $table->unsignedBigInteger('servicio_id');
            $table->foreign('servicio_id')->references('id')->on('config_modulos');

            $table->datetimes('fecha_hora_inicio', 'fecha_hora_fin');
            $table->integer('total_citas')->default(0)->comment('Total de citas disponibles');
            $table->integer('dura_cita')->default(0)->comment('Duracion de la prestacion en minutos');

            $table->enum('estado', ['Activo', 'Inactivo', 'Completado', 'Pendiente'])->default('Activo')->comment('Estado de la disponibilidad: activo/inactivo/completado/pendiente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_disponibilida');
    }
};
