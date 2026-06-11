<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_servicio_citas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('servicio_cita_id');
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas');

            $table->timestamps();

            $table->unique(['user_id', 'servicio_cita_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_servicio_citas');
    }
};
