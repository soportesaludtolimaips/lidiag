<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la columna modulo_id a config_productos para vincular
     * los productos asistenciales a sus respectivos módulos globales.
     *
     * Flujo del proceso:
     * 1. Verifica si la tabla config_productos existe.
     * 2. Si no existe la columna modulo_id, la crea como llave foránea nullable a config_modulos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function up(): void
    {
        if (! Schema::hasTable('config_productos')) {
            return;
        }

        Schema::table('config_productos', function (Blueprint $table) {
            if (! Schema::hasColumn('config_productos', 'modulo_id')) {
                $table->foreignId('modulo_id')
                    ->nullable()
                    ->after('nom_produc')
                    ->constrained('config_modulos')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Revierte la adición de la columna modulo_id en config_productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function down(): void
    {
        if (! Schema::hasTable('config_productos')) {
            return;
        }

        Schema::table('config_productos', function (Blueprint $table) {
            if (Schema::hasColumn('config_productos', 'modulo_id')) {
                $table->dropForeign(['modulo_id']);
                $table->dropColumn('modulo_id');
            }
        });
    }
};
