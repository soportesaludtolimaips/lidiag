<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_sedes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->unsignedBigInteger('sede_id');
            $table->foreign('sede_id')->references('id')->on('config_sedes');

            $table->timestamps();

            $table->unique(['user_id', 'sede_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_sedes');
    }
};
