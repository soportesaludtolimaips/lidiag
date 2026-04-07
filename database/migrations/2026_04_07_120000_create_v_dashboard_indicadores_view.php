<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista para indicadores del dashboard - mucho más eficiente que múltiples queries en PHP
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_indicadores AS
            SELECT 
                COUNT(DISTINCT e.id) as estudios_asignados,
                COUNT(DISTINCT CASE WHEN ep.fechor_trascrito IS NOT NULL THEN e.id END) as estudios_leidos,
                COUNT(DISTINCT CASE WHEN ep.fechor_trascrito IS NULL THEN e.id END) as estudios_pendientes_transcripcion,
                COUNT(DISTINCT CASE WHEN ep.fechor_lectura IS NULL THEN e.id END) as estudios_pendientes_lectura
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