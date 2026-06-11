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
        Schema::create('agenda_citas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quien_registro_id');
            $table->foreign('quien_registro_id')->references('id')->on('users');

            $table->unsignedBigInteger('sede_id');
            $table->foreign('sede_id')->references('id')->on('config_sedes');

            $table->unsignedBigInteger('servicio_cita_id');
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas');

            $table->text('title');
            $table->text('description')->nullable();
            $table->dateTime('fecha_hora_ini');
            $table->dateTime('fecha_hora_fin');
            $table->integer('total_citas')->default(0)->comment('Total de slots disponibles en este bloque');
            $table->integer('dura_cita')->default(30)->comment('Duracion de cada slot en minutos');

            $table->enum('estado', ['Activo', 'Inactivo', 'Completado', 'Pendiente'])->default('Activo')
                ->comment('Estado del bloque de agenda');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_citas');
    }
};
