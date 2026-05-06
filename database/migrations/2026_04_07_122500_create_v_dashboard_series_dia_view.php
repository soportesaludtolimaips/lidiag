<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_series_dia AS
            SELECT 
                DATE(e.created_at) as fecha,
                COUNT(DISTINCT e.id) as asignados,
                0 as leidos,
                0 as transcritos
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
            GROUP BY DATE(e.created_at)
            UNION ALL
            SELECT 
                DATE(ep.fechor_lectura) as fecha,
                0 as asignados,
                COUNT(DISTINCT e.id) as leidos,
                0 as transcritos
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_lectura IS NOT NULL
            GROUP BY DATE(ep.fechor_lectura)
            UNION ALL
            SELECT 
                DATE(ep.fechor_trascrito) as fecha,
                0 as asignados,
                0 as leidos,
                COUNT(DISTINCT e.id) as transcritos
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_trascrito IS NOT NULL
            GROUP BY DATE(ep.fechor_trascrito)
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_dashboard_series_dia");
    }
};
