<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dateTime('fecha_hora_ini')->nullable()->change();
            $table->dateTime('fecha_hora_fin')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dateTime('fecha_hora_ini')->nullable(false)->change();
            $table->dateTime('fecha_hora_fin')->nullable(false)->change();
        });
    }
};
