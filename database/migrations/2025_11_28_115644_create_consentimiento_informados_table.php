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
        Schema::create('consentimiento_informados', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('sede_id');
            $table->foreign('sede_id')->references('id')->on('config_sedes');

            $table->unsignedBigInteger('usuario_crea_id');
            $table->foreign('usuario_crea_id')->references('id')->on('users');

            $table->string('nombre')->comment('Nombre del consentimiento informado');
            $table->string('archivo')->nullable()->comment('Ruta del archivo');
            $table->boolean('estado')->default(true)->comment('Estado del consentimiento informado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consentimiento_informados');
    }
};
