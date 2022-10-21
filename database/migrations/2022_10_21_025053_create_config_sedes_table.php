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
        Schema::create('config_sedes', function (Blueprint $table) {
            $table->id();
            $table->string('sucursal', 70);
            $table->string('ip_dcm', 15)->nullable();
            $table->string('bd_dcm', 20)->nullable();
            $table->string('usuario_dcm', 20)->nullable();
            $table->string('password_dcm', 20)->nullable();
            $table->string('usuario_oviyam', 20)->nullable();
            $table->string('passwor_oviyam', 20)->nullable();
            $table->string('url_oviyam', 100)->nullable();
            $table->boolean('estado')->default(true)->default(1);
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
        Schema::dropIfExists('config_sedes');
    }
};
