<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estudios_productos', function (Blueprint $table) {
            $table->index('fechor_lectura', 'idx_estudios_productos_fecha_lectura');
            $table->index('fechor_trascrito', 'idx_estudios_productos_fecha_trascrito');
            $table->index('activo', 'idx_estudios_productos_activo');
        });

        Schema::table('estudios', function (Blueprint $table) {
            $table->index('created_at', 'idx_estudios_created_at');
            $table->index('estado', 'idx_estudios_estado');
            $table->index('medico_id', 'idx_estudios_medico_id');
            $table->index('sede_id', 'idx_estudios_sede_id');
            $table->index('prioridad_id', 'idx_estudios_prioridad_id');
        });
    }

    public function down(): void
    {
        Schema::table('estudios_productos', function (Blueprint $table) {
            $table->dropIndex('idx_estudios_productos_fecha_lectura');
            $table->dropIndex('idx_estudios_productos_fecha_trascrito');
            $table->dropIndex('idx_estudios_productos_activo');
        });

        Schema::table('estudios', function (Blueprint $table) {
            $table->dropIndex('idx_estudios_created_at');
            $table->dropIndex('idx_estudios_estado');
            $table->dropIndex('idx_estudios_medico_id');
            $table->dropIndex('idx_estudios_sede_id');
            $table->dropIndex('idx_estudios_prioridad_id');
        });
    }
};
