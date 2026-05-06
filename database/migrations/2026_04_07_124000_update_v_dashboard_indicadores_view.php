<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista indicadores con filtro de fecha dinámico
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_indicadores AS
            SELECT 
                e.id,
                e.created_at as fecha_creacion,
                ep.fechor_trascrito,
                ep.fechor_lectura,
                CASE WHEN ep.fechor_trascrito IS NOT NULL THEN 1 ELSE 0 END as esta_leido,
                CASE WHEN ep.fechor_trascrito IS NULL THEN 1 ELSE 0 END as esta_pendiente_transcripcion,
                CASE WHEN ep.fechor_lectura IS NULL THEN 1 ELSE 0 END as esta_pendiente_lectura
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_dashboard_indicadores");
    }
};
