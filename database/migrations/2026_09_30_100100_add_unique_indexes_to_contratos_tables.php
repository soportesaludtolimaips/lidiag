<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega índices únicos de integridad a las tablas de contratos.
 *
 * Impide tarifas duplicadas: un producto no puede repetirse dentro del mismo
 * contrato y un médico no puede repetirse para el mismo producto dentro del
 * mismo contrato. La tabla está vacía, por lo que no hay colisiones que resolver.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-09-30
 */
return new class extends Migration
{
    /**
     * Crea las restricciones únicas por contrato, producto y médico.
     */
    public function up(): void
    {
        Schema::table('contratos_productos', function (Blueprint $table) {
            $table->unique(['contrato_id', 'producto_id'], 'contratos_productos_contrato_producto_unique');
        });

        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->unique(
                ['contrato_id', 'medico_id', 'contratos_productos_id'],
                'contratos_medicos_contrato_medico_producto_unique'
            );
        });
    }

    /**
     * Elimina las restricciones únicas de contratos_medicos y contratos_productos.
     */
    public function down(): void
    {
        Schema::table('contratos_medicos', function (Blueprint $table) {
            $table->dropUnique('contratos_medicos_contrato_medico_producto_unique');
        });

        Schema::table('contratos_productos', function (Blueprint $table) {
            $table->dropUnique('contratos_productos_contrato_producto_unique');
        });
    }
};
