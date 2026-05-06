<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista para operación de hoy
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_operacion_hoy AS
            SELECT 
                COUNT(DISTINCT CASE WHEN DATE(e.created_at) = CURDATE() THEN e.id END) as asignados_hoy,
                COUNT(DISTINCT CASE WHEN ep.fechor_lectura IS NULL THEN e.id END) as pendientes_lectura,
                COUNT(DISTINCT CASE WHEN ep.fechor_lectura IS NULL AND DATE(e.created_at) = CURDATE() THEN e.id END) as pendientes_lectura_hoy,
                COUNT(DISTINCT CASE WHEN ep.fechor_lectura IS NOT NULL AND DATE(ep.fechor_lectura) = CURDATE() THEN e.id END) as leidos_hoy,
                COUNT(DISTINCT CASE WHEN ep.fechor_trascrito IS NULL THEN e.id END) as pendientes_transcripcion,
                COUNT(DISTINCT CASE WHEN ep.fechor_trascrito IS NOT NULL AND DATE(ep.fechor_trascrito) = CURDATE() THEN e.id END) as transcritos_hoy
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
        ");

        // Vista para series por día (transcritos, asignados, leidos)
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

        // Vista para aging de pendientes de lectura
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_aging_lectura AS
            SELECT 
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) BETWEEN 0 AND 2 THEN e.id END) as '0-2h',
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) BETWEEN 2 AND 6 THEN e.id END) as '2-6h',
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) BETWEEN 6 AND 24 THEN e.id END) as '6-24h',
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, e.created_at, NOW()) > 24 THEN e.id END) as '24h+'
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_lectura IS NULL
        ");

        // Vista para aging de pendientes de transcripción
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_aging_transcripcion AS
            SELECT 
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) BETWEEN 0 AND 2 THEN e.id END) as '0-2h',
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) BETWEEN 2 AND 6 THEN e.id END) as '2-6h',
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) BETWEEN 6 AND 24 THEN e.id END) as '6-24h',
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) > 24 THEN e.id END) as '24h+'
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_lectura IS NOT NULL
                AND ep.fechor_trascrito IS NULL
        ");

        // Vista para top pendientes de lectura por producto
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_pendientes_lectura_top AS
            SELECT 
                cp.nom_produc as producto,
                COUNT(DISTINCT e.id) as total
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_lectura IS NULL
            GROUP BY cp.nom_produc
            ORDER BY total DESC
            LIMIT 10
        ");

        // Vista para top pendientes de transcripción por producto
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_pendientes_transcripcion_top AS
            SELECT 
                cp.nom_produc as producto,
                COUNT(DISTINCT e.id) as total
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_lectura IS NOT NULL
                AND ep.fechor_trascrito IS NULL
            GROUP BY cp.nom_produc
            ORDER BY total DESC
            LIMIT 10
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_dashboard_operacion_hoy");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_series_dia");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_aging_lectura");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_aging_transcripcion");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_pendientes_lectura_top");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_pendientes_transcripcion_top");
    }
};
