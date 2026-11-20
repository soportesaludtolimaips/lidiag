<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Flujo del proceso:
     * 1. Agrega la columna sede_id a la tabla ecografias vinculada a config_sedes.
     * 2. Asigna la sede predeterminada (1 = Espinal) a los registros existentes sin sede.
     * 3. Crea índice compuesto para optimizar las búsquedas por sede y estado.
     * 4. Actualiza la vista view_reporte_ecografias para incluir e.sede_id y s.nom_sede.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-20
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ecografias', 'sede_id')) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->unsignedBigInteger('sede_id')->nullable()->after('atencion_id');
                $table->foreign('sede_id')->references('id')->on('config_sedes')->cascadeOnUpdate()->restrictOnDelete();
                $table->index(['sede_id', 'estado'], 'idx_ecografias_sede_estado');
            });

            // Asignar sede 1 a los registros existentes que no tengan sede
            DB::table('ecografias')->whereNull('sede_id')->update(['sede_id' => 1]);
        }

        DB::statement("
            CREATE OR REPLACE VIEW view_reporte_ecografias AS
            SELECT
                ep.id AS id,
                e.id AS ecografia_id,
                e.sede_id,
                s.nom_sede,
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
            LEFT JOIN config_sedes s ON e.sede_id = s.id
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

        if (Schema::hasColumn('ecografias', 'sede_id')) {
            Schema::table('ecografias', function (Blueprint $table) {
                $table->dropForeign(['sede_id']);
                $table->dropIndex('idx_ecografias_sede_estado');
                $table->dropColumn('sede_id');
            });
        }
    }
};
