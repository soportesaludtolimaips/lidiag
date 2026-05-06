<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices para acelerar match SAHI -> LIDiag:
     * - ecografias.atencion_id
     * - ecografias_productos (ecografia_id, producto_id)
     */
    public function up(): void
    {
        if (Schema::hasTable('ecografias') && Schema::hasColumn('ecografias', 'atencion_id')) {
            if (! $this->indexExists('ecografias', 'idx_ecografias_atencion_id')) {
                Schema::table('ecografias', function (Blueprint $table) {
                    $table->index('atencion_id', 'idx_ecografias_atencion_id');
                });
            }
        }

        if (
            Schema::hasTable('ecografias_productos')
            && Schema::hasColumn('ecografias_productos', 'ecografia_id')
            && Schema::hasColumn('ecografias_productos', 'producto_id')
        ) {
            if (! $this->indexExists('ecografias_productos', 'idx_ecoprod_ecografia_producto')) {
                Schema::table('ecografias_productos', function (Blueprint $table) {
                    $table->index(['ecografia_id', 'producto_id'], 'idx_ecoprod_ecografia_producto');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ecografias') && $this->indexExists('ecografias', 'idx_ecografias_atencion_id')) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->dropIndex('idx_ecografias_atencion_id');
            });
        }

        if (Schema::hasTable('ecografias_productos') && $this->indexExists('ecografias_productos', 'idx_ecoprod_ecografia_producto')) {
            Schema::table('ecografias_productos', function (Blueprint $table) {
                $table->dropIndex('idx_ecoprod_ecografia_producto');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $db = DB::connection()->getDatabaseName();
            $rows = DB::select(
                "SELECT 1
                 FROM information_schema.statistics
                 WHERE table_schema = ?
                   AND table_name = ?
                   AND index_name = ?
                 LIMIT 1",
                [$db, $table, $indexName]
            );

            return ! empty($rows);
        } catch (Throwable $e) {
            return false;
        }
    }
};
