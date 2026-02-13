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
        // Evita fallar si la columna ya existe (entornos con BD ya actualizada manualmente)
        if (! Schema::hasColumn('config_sedes', 'cod_sede')) {
            Schema::table('config_sedes', function (Blueprint $table) {
                $table->string('cod_sede', 10)->nullable()->before('nom_sede');
                $table->unique('cod_sede');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('config_sedes', 'cod_sede')) {
            Schema::table('config_sedes', function (Blueprint $table) {
                // nombre por defecto del índice unique
                $table->dropUnique('config_sedes_cod_sede_unique');
                $table->dropColumn('cod_sede');
            });
        }
    }
};
