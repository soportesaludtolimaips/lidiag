<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_dashboard_operacion_hoy");
    }
};
