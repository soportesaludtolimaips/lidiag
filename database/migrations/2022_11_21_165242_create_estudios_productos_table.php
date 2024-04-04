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
        Schema::create('estudios_productos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('estudio_id');
            $table->foreign('estudio_id')->references('id')->on('estudios');

            //$table->unsignedBigInteger('producto_id');
            //$table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('transcriptor_id')->nullable();
            $table->foreign('transcriptor_id')->references('id')->on('users');

            $table->string('cod_cups', 20)->nullable();
            $table->string('nom_produc', 200);
            $table->datetime('fechor_lectura')->nullable();
            $table->text('lectura')->nullable();
            $table->datetime('fechor_trascrito')->nullable();
            $table->string('medico_ordena', 200)->nullable();
            $table->boolean('visible')->default(1);
            $table->boolean('activo')->default(1);
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
        Schema::dropIfExists('estudios_productos');
    }
};
