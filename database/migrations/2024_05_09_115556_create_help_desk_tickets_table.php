<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('help_desk_tickets', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('quien_registra_id');
            $table->foreign('quien_registra_id')->references('id')->on('users');

            $table->unsignedBigInteger('sedes_id')->nullable();
            $table->foreign('sedes_id')->references('id')->on('config_sedes');

            $table->unsignedBigInteger('quien_atiende_id')->nullable()->comment('Usuario al cual fue asignado el teckect');
            $table->foreign('quien_atiende_id')->references('id')->on('users');

            $table->unsignedBigInteger('quien_soluciona_id')->nullable()->comment('Usuario que soluciona el incidente');
            $table->foreign('quien_soluciona_id')->references('id')->on('users');

            $table->text('descripcion_insidencia')->nullable();
            $table->text('descripcion_solucion')->nullable();

            $table->dateTime('fechor_sol_quien_registr')->nullable()->comment('Fecha y hora de la solucion por parte del usuario que reporta el insidente');
            $table->dateTime('fechor_sol_quien_soluciona')->nullable()->comment('Fecha y hora de la solucion por parte del usuario que soluciona el insidente');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('help_desk_tickets');
    }
};
