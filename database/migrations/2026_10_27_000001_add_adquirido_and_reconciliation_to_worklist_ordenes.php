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
     * Flujo del proceso:
     * 1. Amplía el ENUM de estado en worklist_ordenes agregando 'ADQUIRIDO'.
     * 2. Incorpora study_pk, estudio_id y adquirido_at para auto-reconciliación con Dcm4chee.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function up(): void
    {
        Schema::table('worklist_ordenes', function (Blueprint $table) {
            if (! Schema::hasColumn('worklist_ordenes', 'study_pk')) {
                $table->unsignedBigInteger('study_pk')->nullable()->after('mwl_item_pk');
            }
            if (! Schema::hasColumn('worklist_ordenes', 'estudio_id')) {
                $table->unsignedBigInteger('estudio_id')->nullable()->after('study_pk');
                $table->foreign('estudio_id')->references('id')->on('estudios')->onDelete('set null');
            }
            if (! Schema::hasColumn('worklist_ordenes', 'adquirido_at')) {
                $table->timestamp('adquirido_at')->nullable()->after('estado');
            }
        });

        // Asegurar que el enum incluya ADQUIRIDO
        try {
            DB::statement("ALTER TABLE worklist_ordenes MODIFY COLUMN estado ENUM('PENDIENTE','ADQUIRIDO','COMPLETADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE'");
        } catch (Throwable $e) {
            // Ignorar si el motor ya tiene la definición actualizada
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worklist_ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('worklist_ordenes', 'estudio_id')) {
                $table->dropForeign(['estudio_id']);
                $table->dropColumn('estudio_id');
            }
            if (Schema::hasColumn('worklist_ordenes', 'study_pk')) {
                $table->dropColumn('study_pk');
            }
            if (Schema::hasColumn('worklist_ordenes', 'adquirido_at')) {
                $table->dropColumn('adquirido_at');
            }
        });

        try {
            DB::statement("ALTER TABLE worklist_ordenes MODIFY COLUMN estado ENUM('PENDIENTE','COMPLETADO','CANCELADO') NOT NULL DEFAULT 'PENDIENTE'");
        } catch (Throwable $e) {
            // Ignorar
        }
    }
};
