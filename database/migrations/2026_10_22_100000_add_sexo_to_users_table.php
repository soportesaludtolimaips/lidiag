<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * users.sexo (M / F, vacío = sin especificar): decide cuál silueta por defecto se muestra cuando el usuario
 * no tiene foto propia (mujer u hombre; sin valor, la silueta neutra de siempre).
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-22
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sexo', 1)->nullable()->after('imagen_perfil');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sexo');
        });
    }
};
