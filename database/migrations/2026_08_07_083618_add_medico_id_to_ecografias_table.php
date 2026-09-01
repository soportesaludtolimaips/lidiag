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
        // Idempotente: solo agrega la columna si no existe (compatibilidad multi-entorno). @author Davide Rivera (Luis Castaño)
        if (! Schema::hasColumn('ecografias', 'medico_id')) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->unsignedBigInteger('medico_id')->after('atencion_id');
                $table->foreign('medico_id')->references('id')->on('users');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            $table->dropForeign(['medico_id']);
            $table->dropColumn('medico_id');
        });
    }
};
