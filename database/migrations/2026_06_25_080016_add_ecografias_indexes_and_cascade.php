<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            $table->index('atencion_id', 'ix_ecografias_atencion');
            $table->index('estado', 'ix_ecografias_estado');
        });

        Schema::table('ecografias_productos', function (Blueprint $table) {
            if (Schema::hasColumn('ecografias_productos', 'atencion_id')) {
                $table->index('atencion_id', 'ix_ecog_prod_atencion');
            }
            $table->index('estado', 'ix_ecog_prod_estado');
            $table->index(['ecografia_id', 'estado'], 'ix_ecog_prod_ecografia_estado');
        });

        Schema::table('ecografias_consentimientos', function (Blueprint $table) {
            $table->dropForeign(['ecografia_producto_id']);
            $table->foreign('ecografia_producto_id')
                ->references('id')->on('ecografias_productos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ecografias_consentimientos', function (Blueprint $table) {
            $table->dropForeign(['ecografia_producto_id']);
            $table->foreign('ecografia_producto_id')
                ->references('id')->on('ecografias_productos');
        });

        Schema::table('ecografias_productos', function (Blueprint $table) {
            $table->dropIndex('ix_ecog_prod_ecografia_estado');
            $table->dropIndex('ix_ecog_prod_estado');
            if (Schema::hasColumn('ecografias_productos', 'atencion_id')) {
                $table->dropIndex('ix_ecog_prod_atencion');
            }
        });

        Schema::table('ecografias', function (Blueprint $table) {
            $table->dropIndex('ix_ecografias_estado');
            $table->dropIndex('ix_ecografias_atencion');
        });
    }
};
