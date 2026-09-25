<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Idempotente: una ejecución parcial previa (columna agregada, FK fallida
     * por tabla referenciada ausente) o una BD restaurada no deben fallar.
     */
    public function up(): void
    {
        // El backup de producción no incluye agenda_disponibilidad_detalle aunque
        // 2026_01_22_120427 figura como ejecutada. Si la tabla falta, se reutiliza
        // la definición original para no duplicar DDL.
        if (! Schema::hasTable('agenda_disponibilidad_detalle')) {
            $crearDetalle = require base_path('database/migrations/2026_01_22_120427_agenda_disponibilidad_detalle.php');
            $crearDetalle->up();
        }

        if (! Schema::hasColumn('agenda_citas', 'agenda_disponibilidad_detalle_id')) {
            Schema::table('agenda_citas', function (Blueprint $table) {
                $table->unsignedBigInteger('agenda_disponibilidad_detalle_id')->nullable()->after('rechazo_observacion');
            });
        }

        $fk = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'agenda_citas'
             AND CONSTRAINT_NAME = 'agenda_citas_agenda_disponibilidad_detalle_id_foreign'
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'"
        );
        if (! $fk) {
            Schema::table('agenda_citas', function (Blueprint $table) {
                $table->foreign('agenda_disponibilidad_detalle_id')
                    ->references('id')->on('agenda_disponibilidad_detalle')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropForeign(['agenda_disponibilidad_detalle_id']);
            $table->dropColumn('agenda_disponibilidad_detalle_id');
        });
    }
};
