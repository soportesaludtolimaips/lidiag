<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vista base para indicadores - datos completos sin filtros
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_indicadores_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
                e.created_at,
                ep.fechor_trascrito,
                ep.fechor_lectura,
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

        // Vista para operación de hoy (sin filtro de fecha - datos completos)
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_operacion_hoy_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
                e.created_at as fecha_creacion,
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

        // Vista base para series por día
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

        // Vista base para aging de lectura
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_aging_lectura_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
                e.created_at,
                ep.fechor_lectura,
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
                AND ep.fechor_lectura IS NULL
        ");

        // Vista base para aging de transcripción
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_aging_transcripcion_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
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
                AND ep.fechor_lectura IS NOT NULL
                AND ep.fechor_trascrito IS NULL
        ");

        // Vista base para pendientes de lectura top
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_pendientes_lectura_top_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
                e.created_at,
                e.study_id,
                e.accession_no,
                e.study_desc,
                e.mods_in_study,
                ep.fechor_lectura,
                ep.activo,
                cp.id as producto_id,
                cp.nom_produc,
                cp.requi_lectura,
                cp.estado as cp_estado,
                e.estado as e_estado,
                p.num_docu,
                p.nombres,
                p.seg_nom,
                p.pri_ape,
                p.seg_ape,
                s.nom_sede
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            INNER JOIN pacientes p ON p.id = e.paciente_id
            INNER JOIN config_sedes s ON s.id = e.sede_id
            WHERE ep.activo = 1 
                AND cp.requi_lectura = 1 
                AND cp.estado = 1
                AND e.estado = 1
                AND ep.fechor_lectura IS NULL
        ");

        // Vista base para pendientes de transcripción top
        DB::statement("
            CREATE OR REPLACE VIEW v_dashboard_pendientes_transcripcion_top_base AS
            SELECT 
                e.id,
                e.medico_id,
                e.sede_id,
                e.created_at,
                e.study_id,
                e.accession_no,
                e.study_desc,
                e.mods_in_study,
                ep.fechor_lectura,
                ep.fechor_trascrito,
                ep.activo,
                cp.id as producto_id,
                cp.nom_produc,
                cp.requi_lectura,
                cp.estado as cp_estado,
                e.estado as e_estado,
                p.num_docu,
                p.nombres,
                p.seg_nom,
                p.pri_ape,
                p.seg_ape,
                s.nom_sede
            FROM estudios e
            INNER JOIN estudios_productos ep ON ep.estudio_id = e.id
            INNER JOIN config_productos cp ON cp.id = ep.producto_id
            INNER JOIN pacientes p ON p.id = e.paciente_id
            INNER JOIN config_sedes s ON s.id = e.sede_id
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
        DB::statement("DROP VIEW IF EXISTS v_dashboard_indicadores_base");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_operacion_hoy_base");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_series_base");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_aging_lectura_base");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_aging_transcripcion_base");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_pendientes_lectura_top_base");
        DB::statement("DROP VIEW IF EXISTS v_dashboard_pendientes_transcripcion_top_base");
    }
};
