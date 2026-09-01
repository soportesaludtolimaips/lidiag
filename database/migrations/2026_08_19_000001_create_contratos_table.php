<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')
                ->constrained('config_sedes');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->text('observaciones')->nullable();
            $table->boolean('estado')->default(1);
            $table->timestamps();

            $table->index('sede_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
