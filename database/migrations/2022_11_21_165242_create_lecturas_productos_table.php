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
        Schema::create('lecturas_productos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lectua_id');
            $table->foreign('lectua_id')->references('id')->on('lecturas');

            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('transcriptor_id');
            $table->foreign('transcriptor_id')->references('id')->on('users');

            $table->string('cod_cups', 20);
            $table->string('nom_produc', 200);
            $table->datetime('fechor_lectura')->nullable();
            $table->text('lectura');
            $table->datetime('fechor_trascrito')->nullable();
            $table->boolean('visible')->default(1);
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
        Schema::dropIfExists('lecturas_productos');
    }
};
