<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la creación de la tabla admin_admisiones para trazabilidad clínica y workflow DICOM.
     *
     * Flujo del proceso:
     * 1. Define claves foráneas hacia pacientes, config_sedes, worklist_ordenes, estudios, config_productos y users.
     * 2. Registra identificadores de interoperabilidad DICOM (accession_no, study_iuid, study_id, study_pk, modality).
     * 3. Registra marcas de tiempo de admisión, toma radiológica y asignación a lectura, junto con el estado del flujo.
     * 4. Almacena contexto asistencial/contractual (atención, EPS, contrato, plan, servicio, ubicación, cama, médico ordenante).
     * 5. Configura índices compuestos para consultas operativas por sede y estado, timestamps y soft deletes.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function up(): void
    {
        Schema::create("admin_admisiones", function (Blueprint $table) {
            $table->id();

            $table->foreignId("paciente_id")
                ->constrained("pacientes")
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId("sede_id")
                ->constrained("config_sedes")
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId("worklist_orden_id")
                ->nullable()
                ->constrained("worklist_ordenes")
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId("estudio_id")
                ->nullable()
                ->constrained("estudios")
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId("producto_id")
                ->nullable()
                ->constrained("config_productos")
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId("usuario_admision_id")
                ->nullable()
                ->constrained("users")
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId("usuario_asignacion_id")
                ->nullable()
                ->constrained("users")
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string("accession_no", 50)->nullable();
            $table->string("study_iuid", 128)->nullable();
            $table->string("study_id", 50)->nullable();
            $table->unsignedBigInteger("study_pk")->nullable();
            $table->string("modality", 15)->nullable();
            $table->string("study_desc", 250)->nullable();

            $table->dateTime("fecha_admision");
            $table->dateTime("fecha_toma")->nullable();
            $table->dateTime("fecha_asignacion")->nullable();

            $table->string("estado", 40)->default("ADMISIONADO");

            $table->string("atencion", 100)->nullable();
            $table->string("eps", 150)->nullable();
            $table->string("contrato", 150)->nullable();
            $table->string("plan", 150)->nullable();
            $table->string("servicio", 150)->nullable();
            $table->string("ubicacion", 150)->nullable();
            $table->string("cama", 50)->nullable();
            $table->string("medico_ordena", 150)->nullable();
            $table->text("observaciones")->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(["sede_id", "estado", "fecha_admision"], "idx_admisiones_sede_estado_fecha");
            $table->index("accession_no", "idx_admisiones_accession_no");
            $table->index("study_iuid", "idx_admisiones_study_iuid");
            $table->index("study_id", "idx_admisiones_study_id");
            $table->index(["paciente_id", "estado"], "idx_admisiones_paciente_estado");
        });
    }

    /**
     * Revierte la creación de la tabla admin_admisiones.
     *
     * Flujo del proceso:
     * 1. Elimina la tabla admin_admisiones si existe en el esquema.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-27
     */
    public function down(): void
    {
        Schema::dropIfExists("admin_admisiones");
    }
};
