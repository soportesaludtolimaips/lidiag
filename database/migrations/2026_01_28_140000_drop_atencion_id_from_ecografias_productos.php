<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remueve `ecografias_productos.atencion_id` (redundante: la fuente de verdad es `ecografias.atencion_id`).
     */
    public function up(): void
    {
        if (! Schema::hasTable('ecografias_productos')) {
            return;
        }

        if (! Schema::hasColumn('ecografias_productos', 'atencion_id')) {
            return;
        }

        Schema::table('ecografias_productos', function (Blueprint $table) {
            $table->dropColumn('atencion_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ecografias_productos')) {
            return;
        }

        if (Schema::hasColumn('ecografias_productos', 'atencion_id')) {
            return;
        }

        Schema::table('ecografias_productos', function (Blueprint $table) {
            $table->string('atencion_id', 20)->after('ecografia_id');
        });
    }
};
