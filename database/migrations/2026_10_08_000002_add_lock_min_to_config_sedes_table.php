<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el timeout configurable de locks de lectura/asignación por sede.
     *
     * NULL = se usa el default del sistema (2 minutos).
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function up(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            $table->unsignedSmallInteger('lock_min')->nullable()->after('password_oviyam');
        });
    }

    /**
     * Elimina la columna lock_min de config_sedes.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-08
     */
    public function down(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            $table->dropColumn('lock_min');
        });
    }
};
