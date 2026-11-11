<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el índice de vigencia a la tabla contratos.
 *
 * Permite localizar los contratos vigentes de una sede por rango de fechas,
 * que es el patrón de consulta usado para la contabilidad de pagos a médicos.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
return new class extends Migration
{
    /**
     * Crea el índice compuesto por sede y rango de vigencia.
     */
    public function up(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->index(['sede_id', 'fecha_inicio', 'fecha_fin'], 'contratos_sede_vigencia_index');
        });
    }

    /**
     * Elimina el índice compuesto por sede y rango de vigencia.
     */
    public function down(): void
    {
        Schema::table('contratos', function (Blueprint $table) {
            $table->dropIndex('contratos_sede_vigencia_index');
        });
    }
};
