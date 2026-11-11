<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Elimina las columnas porcen y val_produc en config_productos debido a que
     * las tarifas ahora se gestionan en contratos_productos según cada entidad.
     *
     * Flujo del proceso:
     * 1. Verifica si la tabla config_productos existe en la base de datos.
     * 2. Identifica de forma idempotente las columnas presentes (porcen, val_produc).
     * 3. Elimina las columnas identificadas de la tabla config_productos.
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
            $columnsToDrop = [];

            if (Schema::hasColumn('config_productos', 'porcen')) {
                $columnsToDrop[] = 'porcen';
            }

            if (Schema::hasColumn('config_productos', 'val_produc')) {
                $columnsToDrop[] = 'val_produc';
            }

            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Revierte la eliminación de las columnas porcen y val_produc en config_productos.
     *
     * Flujo del proceso:
     * 1. Verifica si la tabla config_productos existe en la base de datos.
     * 2. Restaura la columna porcen como string nullable si no existe.
     * 3. Restaura la columna val_produc como float nullable si no existe.
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
            if (! Schema::hasColumn('config_productos', 'porcen')) {
                $table->string('porcen')->default('0')->nullable()->after('nom_produc');
            }

            if (! Schema::hasColumn('config_productos', 'val_produc')) {
                $table->float('val_produc')->default(0.0)->nullable()->after('porcen');
            }
        });
    }
};
