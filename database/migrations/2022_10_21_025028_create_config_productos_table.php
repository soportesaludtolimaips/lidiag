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
        Schema::create('config_productos', function (Blueprint $table) {
            $table->id();
            $table->string('cod_cups', 20);
            $table->string('nom_produc', 200);
            $table->string('porcen')->default(0)->nullable();
            $table->float('val_produc')->default(0.0)->nullable();
            $table->boolean('requi_lectura')->default(1)->nullable();
            $table->boolean('tipo_oral')->default(0)->nullable();
            $table->boolean('tipo_venoso')->default(0)->nullable();
            $table->boolean('factura')->default(0)->nullable();
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
        Schema::dropIfExists('config_productos');
    }
};
