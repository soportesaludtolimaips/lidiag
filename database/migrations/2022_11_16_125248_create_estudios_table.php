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
        Schema::create('estudios', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('study_pk');
            $table->string('study_iuid', 100);
            $table->string('study_id', 50);
            $table->dateTime('fec_estudio');
            $table->string('accession_no', 20);
            $table->string('study_desc', 250);

            $table->unsignedBigInteger('paciente_id');
            $table->foreign('paciente_id')->references('id')->on('pacientes');

            $table->unsignedBigInteger('medico_id');
            $table->foreign('medico_id')->references('id')->on('users');

            $table->unsignedBigInteger('quien_registro_id');
            $table->foreign('quien_registro_id')->references('id')->on('users');

            $table->unsignedBigInteger('sucursal_id');
            $table->foreign('sucursal_id')->references('id')->on('config_sucursales');

            $table->unsignedBigInteger('prioridad_id');
            $table->foreign('prioridad_id')->references('id')->on('config_prioridades');

            $table->text('observaciones')->nullable();
            $table->string('atencion')->nullable();
            $table->boolean('estado')->default(1);

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
        Schema::dropIfExists('estudios');
    }
};
