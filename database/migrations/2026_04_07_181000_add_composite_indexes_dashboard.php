<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Índices compuestos para optimizar consultas del dashboard
        Schema::table('estudios_productos', function (Blueprint $table) {
            $table->index(['estudio_id', 'fechor_lectura'], 'idx_ep_estudio_fecha_lectura');
            $table->index(['estudio_id', 'fechor_trascrito'], 'idx_ep_estudio_fecha_trascrito');
            $table->index(['estudio_id', 'activo'], 'idx_ep_estudio_activo');
            $table->index(['estudio_id', 'fechor_lectura', 'fechor_trascrito'], 'idx_ep_estudio_fechas');
        });

        Schema::table('estudios', function (Blueprint $table) {
            $table->index(['sede_id', 'created_at'], 'idx_estudios_sede_fecha');
            $table->index(['medico_id', 'created_at'], 'idx_estudios_medico_fecha');
            $table->index(['sede_id', 'medico_id', 'created_at'], 'idx_estudios_sede_medico_fecha');
            $table->index(['estado', 'created_at'], 'idx_estudios_estado_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('estudios_productos', function (Blueprint $table) {
            $table->dropIndex('idx_ep_estudio_fecha_lectura');
            $table->dropIndex('idx_ep_estudio_fecha_trascrito');
            $table->dropIndex('idx_ep_estudio_activo');
            $table->dropIndex('idx_ep_estudio_fechas');
        });

        Schema::table('estudios', function (Blueprint $table) {
            $table->dropIndex('idx_estudios_sede_fecha');
            $table->dropIndex('idx_estudios_medico_fecha');
            $table->dropIndex('idx_estudios_sede_medico_fecha');
            $table->dropIndex('idx_estudios_estado_fecha');
        });
    }
};