<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->unsignedBigInteger('servicio_id')->nullable()->after('sede_id');
            $table->foreign('servicio_id')->references('id')->on('config_servicios');
            $table->index('servicio_id');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->dropIndex(['servicio_id']);
            $table->dropColumn('servicio_id');
        });
    }
};
