<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Flujo del proceso:
     * 1. Actualiza o crea la vista view_reporte_ecografias incluyendo e.ubicacion y e.cama.
     * 2. Corrige e.atencion_id (proveniente de la tabla ecografias).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-18
     */
    public function up(): void
    {
        DB::statement("
            CREATE OR REPLACE VIEW view_reporte_ecografias AS
            SELECT
                ep.id AS id,
                e.id AS ecografia_id,
                e.fec_cita_sahi,
                e.fec_regis_cita_sahi,
                ep.created_at AS fecha_creacion_producto,
                ep.fechor_lectura,
                e.atencion_id,
                e.paciente_id,
                e.contrato,
                ep.lectura,
                ep.opinion,
                ep.estado AS estado_producto,
                u_reg.id AS usua_regis_id,
                u_reg.name AS quien_registro,
                u_med.id as medico_id,
                u_med.name AS medico,
                p.num_docu AS documento_paciente,
                p.nombres AS nombre_paciente,
                cp.cod_cups,
                cp.nom_produc,
                cas.nom_admin_salud AS eps,
                e.plan,
                e.estado AS estado_ecografia,
                e.quien_ordena,
                e.ubicacion,
                e.cama
            FROM ecografias_productos ep
            JOIN ecografias e ON ep.ecografia_id = e.id
            JOIN pacientes p ON e.paciente_id = p.id
            JOIN users u_reg ON e.usua_regis_id = u_reg.id
            LEFT JOIN users u_med ON e.medico_id = u_med.id
            LEFT JOIN config_admin_salud cas ON e.admin_salud_id = cas.id
            JOIN config_productos cp ON ep.producto_id = cp.id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS view_reporte_ecografias");
    }
};
