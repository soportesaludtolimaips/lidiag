<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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
        DB::statement("DROP VIEW IF EXISTS v_dashboard_pendientes_transcripcion_top");
    }
};
