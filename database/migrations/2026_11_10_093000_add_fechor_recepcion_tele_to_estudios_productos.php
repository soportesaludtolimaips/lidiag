<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna fechor_recepcion_tele en estudios_productos para registrar
     * la fecha y hora exacta en la que LIDiag recibe el informe desde tele-radiología.
     *
     * Flujo del proceso:
     * 1. Verifica si la tabla estudios_productos existe en la base de datos.
     * 2. Comprueba de forma idempotente si la columna fechor_recepcion_tele ya existe.
     * 3. Si no existe, agrega la columna fechor_recepcion_tele como dateTime nullable después de fechor_lectura.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function up(): void
    {
        if (! Schema::hasTable('estudios_productos')) {
            return;
        }

        if (Schema::hasColumn('estudios_productos', 'fechor_recepcion_tele')) {
            return;
        }

        Schema::table('estudios_productos', function (Blueprint $table) {
            $table->dateTime('fechor_recepcion_tele')->nullable()->after('fechor_lectura');
        });
    }

    /**
     * Revierte la adición de la columna fechor_recepcion_tele.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-10
     */
    public function down(): void
    {
        if (! Schema::hasTable('estudios_productos')) {
            return;
        }

        if (Schema::hasColumn('estudios_productos', 'fechor_recepcion_tele')) {
            Schema::table('estudios_productos', function (Blueprint $table) {
                $table->dropColumn('fechor_recepcion_tele');
            });
        }
    }
};
