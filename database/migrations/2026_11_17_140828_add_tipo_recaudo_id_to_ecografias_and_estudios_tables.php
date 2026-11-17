<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function up(): void
    {
        if (Schema::hasTable('ecografias') && ! Schema::hasColumn('ecografias', 'tipo_recaudo_id')) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_recaudo_id')->nullable()->after('paciente_id');
                $table->foreign('tipo_recaudo_id')->references('id')->on('config_tipos_recaudo')->onDelete('set null');
            });
        }

        if (Schema::hasTable('estudios') && ! Schema::hasColumn('estudios', 'tipo_recaudo_id')) {
            Schema::table('estudios', function (Blueprint $table) {
                $table->unsignedBigInteger('tipo_recaudo_id')->nullable()->after('paciente_id');
                $table->foreign('tipo_recaudo_id')->references('id')->on('config_tipos_recaudo')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ecografias') && Schema::hasColumn('ecografias', 'tipo_recaudo_id')) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->dropForeign(['tipo_recaudo_id']);
                $table->dropColumn('tipo_recaudo_id');
            });
        }

        if (Schema::hasTable('estudios') && Schema::hasColumn('estudios', 'tipo_recaudo_id')) {
            Schema::table('estudios', function (Blueprint $table) {
                $table->dropForeign(['tipo_recaudo_id']);
                $table->dropColumn('tipo_recaudo_id');
            });
        }
    }
};
