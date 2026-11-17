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
     * 1. Agrega las claves foráneas opcionales ecografia_id y ecografia_producto_id a la tabla worklist_ordenes.
     * 2. Permite relacionar órdenes de Worklist con ecografías admitidas y productos ecográficos específicos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function up(): void
    {
        Schema::table('worklist_ordenes', function (Blueprint $table) {
            if (! Schema::hasColumn('worklist_ordenes', 'ecografia_id')) {
                $table->unsignedBigInteger('ecografia_id')->nullable()->after('estudio_id');
                $table->foreign('ecografia_id')->references('id')->on('ecografias')->onDelete('set null');
            }
            if (! Schema::hasColumn('worklist_ordenes', 'ecografia_producto_id')) {
                $table->unsignedBigInteger('ecografia_producto_id')->nullable()->after('ecografia_id');
                $table->foreign('ecografia_producto_id')->references('id')->on('ecografias_productos')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worklist_ordenes', function (Blueprint $table) {
            if (Schema::hasColumn('worklist_ordenes', 'ecografia_producto_id')) {
                $table->dropForeign(['ecografia_producto_id']);
                $table->dropColumn('ecografia_producto_id');
            }
            if (Schema::hasColumn('worklist_ordenes', 'ecografia_id')) {
                $table->dropForeign(['ecografia_id']);
                $table->dropColumn('ecografia_id');
            }
        });
    }
};
