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
            $table->string('nom_sede', 70);
            $table->string('email_repor', 70)->nullable();
            $table->string('ip_dcm', 15)->nullable();
            $table->string('bd_dcm', 50)->nullable();
            $table->string('puerto_dcm', 5)->nullable();
            $table->string('usuario_dcm', 30)->nullable();
            $table->string('password_dcm', 30)->nullable();
            $table->string('usuario_oviyam', 20)->nullable();
            $table->string('password_oviyam', 20)->nullable();
            $table->string('url_oviyam', 100)->nullable();
            $table->string('tap_oviyam', 100)->nullable();
            $table->string('encabezado', 100)->nullable();
            $table->string('pie_pagina', 100)->nullable();
            $table->string('logo_1', 100)->nullable();
            $table->string('logo_2', 100)->nullable();
            $table->string('interface_software', 30)->nullable(); //Interface del software de HC del hospital
            $table->enum('interface_driver', ['mysql', 'pgsql', 'sqlsrv'])->nullable();
            $table->string('interface_host', 15)->nullable();
            $table->integer('interface_port')->nullable();
            $table->string('interface_database', 50)->nullable();
            $table->string('interface_username', 15)->nullable();
            $table->string('interface_password', 15)->nullable();
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
