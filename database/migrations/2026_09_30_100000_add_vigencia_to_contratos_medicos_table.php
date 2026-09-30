<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega la vigencia por médico a la tabla contratos_medicos.
 *
 * Añade las columnas nullable `fecha_ingreso` y `fecha_retiro` para poder
 * registrar el periodo de vigencia individual de cada médico dentro de un
 * contrato, en lugar de heredar siempre las fechas del contrato padre.
 *
 * Ambas columnas se crean sin backfill porque la tabla está vacía.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
return new class extends Migration
{
    /**
     * Crea las columnas de vigencia por médico y el índice de consulta por contrato y estado.
     */
    public function up(): void
    {
        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->date('fecha_ingreso')->nullable()->after('valor_urgencias');
            $table->date('fecha_retiro')->nullable()->after('fecha_ingreso');
            $table->index(['contrato_id', 'estado'], 'contratos_medicos_contrato_estado_index');
        });
    }

    /**
     * Elimina el índice por contrato y estado y las columnas de vigencia por médico.
     */
    public function down(): void
    {
        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->dropIndex('contratos_medicos_contrato_estado_index');
            $table->dropColumn(['fecha_ingreso', 'fecha_retiro']);
        });
    }
};
