<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_sede_whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('config_sedes')->cascadeOnDelete();
            $table->string('phone_number_id', 50)->unique();
            $table->string('numero', 20);
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_sede_whatsapp_numbers');
    }
};
