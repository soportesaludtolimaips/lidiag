<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Flujo del proceso:
     * 1. Verifica si la columna 'region_anatomica' ya existe en medicion_dosis_umbrales_nrd.
     * 2. Si no existe, la añade después de 'tipo_metrica' con su índice para permitir
     *    umbrales clínicos específicos por región (Cráneo, Tórax, Abdomen, etc.) según Res. 482 / ICRP.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-04
     */
    public function up(): void
    {
        if (Schema::hasTable('medicion_dosis_umbrales_nrd') && ! Schema::hasColumn('medicion_dosis_umbrales_nrd', 'region_anatomica')) {
            Schema::table('medicion_dosis_umbrales_nrd', function (Blueprint $table) {
                $table->string('region_anatomica', 50)->nullable()->after('tipo_metrica');
                $table->index('region_anatomica', 'idx_umbrales_region');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('medicion_dosis_umbrales_nrd') && Schema::hasColumn('medicion_dosis_umbrales_nrd', 'region_anatomica')) {
            Schema::table('medicion_dosis_umbrales_nrd', function (Blueprint $table) {
                $table->dropIndex('idx_umbrales_region');
                $table->dropColumn('region_anatomica');
            });
        }
    }
};
