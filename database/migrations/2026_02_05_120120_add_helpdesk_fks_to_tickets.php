<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_desk_tickets', function (Blueprint $table) {
            $table->foreign('categoria_id')->references('id')->on('help_desk_categorias');
            $table->foreign('prioridad_id')->references('id')->on('help_desk_prioridades');
            $table->foreign('estado_id')->references('id')->on('help_desk_estados');
        });
    }

    public function down(): void
    {
        Schema::table('help_desk_tickets', function (Blueprint $table) {
            $table->dropForeign(['categoria_id']);
            $table->dropForeign(['prioridad_id']);
            $table->dropForeign(['estado_id']);
        });
    }
};
