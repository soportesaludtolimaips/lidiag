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
     * 1. Agrega índices para optimizar filtros de agendamiento y citas pendientes (estado, fecha_hora_inicio).
     * 2. Agrega índices compuestos para consultas frecuentes por sede y estado.
     * 3. Agrega índices en help_desk_tickets para filtrado por estado y sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->index('estado', 'idx_agenda_citas_estado');
            $table->index('fecha_hora_inicio', 'idx_agenda_citas_fecha_hora_inicio');
            $table->index(['sede_id', 'estado'], 'idx_agenda_citas_sede_estado');
            $table->index(['servicio_id', 'estado'], 'idx_agenda_citas_servicio_estado');
            $table->index('created_at', 'idx_agenda_citas_created_at');
        });

        Schema::table('help_desk_tickets', function (Blueprint $table) {
            $table->index(['sedes_id', 'estado_id'], 'idx_help_desk_tickets_sede_estado');
            $table->index('prioridad_id', 'idx_help_desk_tickets_prioridad_id');
        });

        Schema::table('estudios_audios', function (Blueprint $table) {
            $table->index('estudio_producto_id', 'idx_estudios_audios_producto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropIndex('idx_agenda_citas_estado');
            $table->dropIndex('idx_agenda_citas_fecha_hora_inicio');
            $table->dropIndex('idx_agenda_citas_sede_estado');
            $table->dropIndex('idx_agenda_citas_servicio_estado');
            $table->dropIndex('idx_agenda_citas_created_at');
        });

        Schema::table('help_desk_tickets', function (Blueprint $table) {
            $table->dropIndex('idx_help_desk_tickets_sede_estado');
            $table->dropIndex('idx_help_desk_tickets_prioridad_id');
        });

        Schema::table('estudios_audios', function (Blueprint $table) {
            $table->dropIndex('idx_estudios_audios_producto_id');
        });
    }
};
