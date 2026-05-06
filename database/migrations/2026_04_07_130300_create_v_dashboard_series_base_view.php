<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_series_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
                e.created_at,
                ep.fechor_lectura,
                ep.fechor_trascrito,
                ep.activo,
                cp.requi_lectura,
                cp.estado as cp_estado,
                e.estado as e_estado
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
        DB::statement("DROP VIEW IF EXISTS v_dashboard_series_base");
    }
};
