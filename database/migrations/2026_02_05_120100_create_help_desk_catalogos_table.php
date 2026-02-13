<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_desk_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('help_desk_prioridades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('help_desk_estados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            // Protege los 4 estados base (no editar/borrar desde UI)
            $table->boolean('es_base')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_desk_estados');
        Schema::dropIfExists('help_desk_prioridades');
        Schema::dropIfExists('help_desk_categorias');
    }
};
