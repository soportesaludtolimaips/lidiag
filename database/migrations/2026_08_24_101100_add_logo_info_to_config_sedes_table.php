<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Logo e información por sede para el encabezado de los PDF.
 *
 * Alineado con la convención existente (modelo ConfigSede y el reporte
 * resporte-lectura.blade.php): logo_1/logo_2 = archivos en public/logos/,
 * encabezado y pie_pagina = texto libre. Estas columnas estaban en el
 * $fillable del modelo pero nunca se crearon en la tabla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            if (! Schema::hasColumn('config_sedes', 'logo_1')) {
                $table->string('logo_1')->nullable()->after('nom_sede');
            }
            if (! Schema::hasColumn('config_sedes', 'logo_2')) {
                $table->string('logo_2')->nullable()->after('logo_1');
            }
            if (! Schema::hasColumn('config_sedes', 'encabezado')) {
                $table->text('encabezado')->nullable()->after('logo_2');
            }
            if (! Schema::hasColumn('config_sedes', 'pie_pagina')) {
                $table->text('pie_pagina')->nullable()->after('encabezado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            foreach (['logo_1', 'logo_2', 'encabezado', 'pie_pagina'] as $col) {
                if (Schema::hasColumn('config_sedes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
