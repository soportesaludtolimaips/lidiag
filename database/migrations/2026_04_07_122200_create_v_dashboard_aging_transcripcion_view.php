<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_aging_transcripcion AS
            SELECT 
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) BETWEEN 0 AND 2 THEN e.id END) as rango_0_2h,
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) BETWEEN 2 AND 6 THEN e.id END) as rango_2_6h,
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) BETWEEN 6 AND 24 THEN e.id END) as rango_6_24h,
                COUNT(DISTINCT CASE WHEN TIMESTAMPDIFF(HOUR, ep.fechor_lectura, NOW()) > 24 THEN e.id END) as rango_24h_mas
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
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_dashboard_aging_transcripcion");
    }
};
