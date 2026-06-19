<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->unsignedBigInteger('agenda_disponibilidad_detalle_id')->nullable()->after('rechazo_observacion');
            $table->foreign('agenda_disponibilidad_detalle_id')
                ->references('id')->on('agenda_disponibilidad_detalle')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropForeign(['agenda_disponibilidad_detalle_id']);
            $table->dropColumn('agenda_disponibilidad_detalle_id');
        });
    }
};
