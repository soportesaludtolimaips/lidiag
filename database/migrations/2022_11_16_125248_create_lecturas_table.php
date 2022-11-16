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
        Schema::create('lecturas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('study_pk');

            $table->unsignedBigInteger('medico_id');
            $table->foreign('medico_id')->references('id')->on('users');

            $table->unsignedBigInteger('quien_agendo_id');
            $table->foreign('quien_agendo_id')->references('id')->on('users');

            $table->unsignedBigInteger('sucursal_id');
            $table->foreign('sucursal_id')->references('id')->on('config_sucursales');

            $table->unsignedBigInteger('prioridad_id');
            $table->foreign('prioridad_id')->references('id')->on('config_prioridades');

            $table->text('observaciones');
            $table->string('atencion');

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
        Schema::dropIfExists('lecturas');
    }
};
