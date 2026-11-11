<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->unsignedBigInteger('servicio_id')->nullable();
            $table->text('mensaje_bienvenida');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('sede_id')->references('id')->on('config_sedes');
            $table->foreign('servicio_id')->references('id')->on('config_servicios');
            $table->unique(['sede_id', 'servicio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_whatsapp');
    }
};
