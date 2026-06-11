<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->unsignedBigInteger('servicio_cita_id')->nullable()->after('sede_id');
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas');
            $table->index('servicio_cita_id');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->dropForeign(['servicio_cita_id']);
            $table->dropIndex(['servicio_cita_id']);
            $table->dropColumn('servicio_cita_id');
        });
    }
};
