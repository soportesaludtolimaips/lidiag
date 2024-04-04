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
        Schema::create('config_admin_salud', function (Blueprint $table) {
            $table->id();
            $table->string('cod_admin_salud', 10)->nullable();
            $table->string('nit_admin_salud', 15)->nullable();
            $table->string('nom_admin_salud', 150);
            $table->boolean('estado')->default();
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
        Schema::dropIfExists('config_admin_salud');
    }
};
