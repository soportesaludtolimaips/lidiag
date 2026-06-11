<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_disponibilidad_detalle', function (Blueprint $table) {
            $table->renameColumn('agenda_disponibilida_id', 'agenda_disponibilidad_id');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_disponibilidad_detalle', function (Blueprint $table) {
            $table->renameColumn('agenda_disponibilidad_id', 'agenda_disponibilida_id');
        });
    }
};
