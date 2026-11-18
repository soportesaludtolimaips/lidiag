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
     * 1. Agrega las columnas opcionales ubicacion y cama a la tabla estudios para homologación con ecografías.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-18
     */
    public function up(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            if (! Schema::hasColumn('estudios', 'ubicacion')) {
                $table->string('ubicacion', 150)->nullable()->after('servicio');
            }

            if (! Schema::hasColumn('estudios', 'cama')) {
                $table->string('cama', 50)->nullable()->after('ubicacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            if (Schema::hasColumn('estudios', 'cama')) {
                $table->dropColumn('cama');
            }

            if (Schema::hasColumn('estudios', 'ubicacion')) {
                $table->dropColumn('ubicacion');
            }
        });
    }
};
