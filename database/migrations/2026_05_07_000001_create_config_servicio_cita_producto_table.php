<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_servicio_cita_producto', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('servicio_cita_id');
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas')->onDelete('cascade');
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos')->onDelete('cascade');
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_servicio_cita_producto');
    }
};
