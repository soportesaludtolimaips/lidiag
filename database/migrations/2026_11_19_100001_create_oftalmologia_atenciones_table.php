<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create("oftalmologia_atenciones", function (Blueprint $table) {
            $table->id();
            $table->string("atencion_id", 30)->comment("Numero de atencion de SAHI o PARTICULAR / CORTESIA");

            $table->foreignId("sede_id")->constrained("config_sedes")->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId("paciente_id")->constrained("pacientes")->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId("medico_id")->nullable()->constrained("users")->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId("usua_regis_id")->constrained("users")->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId("admin_salud_id")->nullable()->constrained("config_admin_salud")->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId("tipo_recaudo_id")->nullable()->constrained("config_tipos_recaudo")->cascadeOnUpdate()->nullOnDelete();

            $table->string("contrato", 150)->nullable();
            $table->string("plan", 150)->nullable();
            $table->string("ubicacion", 150)->nullable();
            $table->string("cama", 80)->nullable();
            $table->string("quien_ordena", 150)->nullable();

            $table->date("fec_cita_sahi")->nullable();
            $table->dateTime("fec_ingreso")->nullable();

            $table->enum("estado", ["PENDIENTE", "EN_CONSULTA", "ATENDIDO", "ANULADO"])->default("PENDIENTE");

            // Auditoria de anulacion
            $table->foreignId("usua_anula_id")->nullable()->constrained("users")->cascadeOnUpdate()->nullOnDelete();
            $table->dateTime("fechor_anula")->nullable();
            $table->string("motivo_anula", 255)->nullable();

            // Estado de sincronizacion con SAHI
            $table->enum("estado_sahi", ["NO_APLICA", "PENDIENTE", "ENVIANDO", "ENVIADO", "ERROR", "OMITIDO"])->default("NO_APLICA");
            $table->dateTime("fechor_envio_sahi")->nullable();
            $table->text("error_envio_sahi")->nullable();

            $table->timestamps();

            $table->index(["sede_id", "estado"]);
            $table->index(["paciente_id"]);
            $table->index(["atencion_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("oftalmologia_atenciones");
    }
};
