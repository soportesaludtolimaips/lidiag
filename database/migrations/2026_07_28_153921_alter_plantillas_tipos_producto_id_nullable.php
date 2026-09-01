<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Ejecuta la migración de forma idempotente: si la columna producto_id no
     * existe en esta base (se creó comentada en el origen), la agrega como
     * nullable; si ya existe, solo la vuelve nullable.
     *
     * @author Davide Rivera (Luis Castaño)
     *
     * @date 2026-08-31
     */
    public function up(): void
    {
        Schema::table('config_plantillas_tipos', function (Blueprint $table) {
            if (Schema::hasColumn('config_plantillas_tipos', 'producto_id')) {
                $table->unsignedBigInteger('producto_id')->nullable()->change();
            } else {
                $table->unsignedBigInteger('producto_id')->nullable()->after('id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_plantillas_tipos', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable(false)->change();
        });
    }
};
