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
        Schema::create("oftalmologia_consultas", function (Blueprint $table) {
            $table->id();
            $table->foreignId("atencion_id")->constrained("oftalmologia_atenciones")->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("medico_id")->constrained("users")->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId("sede_id")->constrained("config_sedes")->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId("paciente_id")->constrained("pacientes")->cascadeOnUpdate()->restrictOnDelete();

            // Anamnesis
            $table->text("motivo_consulta")->nullable();
            $table->text("enfermedad_actual")->nullable();
            $table->text("antecedentes_medicos")->nullable();
            $table->text("antecedentes_quirurgicos_oft")->nullable();
            $table->text("antecedentes_alergicos")->nullable();
            $table->text("antecedentes_otros")->nullable();
            $table->text("revision_sistemas")->nullable();

            // Examen Fisico Oftalmologico estructurado
            $table->string("av_sc_od", 50)->nullable()->comment("Agudeza Visual sin correccion Ojo Derecho");
            $table->string("av_sc_oi", 50)->nullable()->comment("Agudeza Visual sin correccion Ojo Izquierdo");
            $table->string("av_cc_od", 50)->nullable()->comment("Agudeza Visual con correccion Ojo Derecho");
            $table->string("av_cc_oi", 50)->nullable()->comment("Agudeza Visual con correccion Ojo Izquierdo");
            $table->string("av_ph_od", 50)->nullable()->comment("Agudeza Visual pinhole OD");
            $table->string("av_ph_oi", 50)->nullable()->comment("Agudeza Visual pinhole OI");

            $table->text("motilidad_ocular")->nullable()->comment("MOE: Ortoforia, Ducciones y Versiones");
            $table->text("pupilas")->nullable()->comment("Reflejos pupilares");
            $table->text("biomicroscopia_od")->nullable()->comment("Segmento anterior Ojo Derecho");
            $table->text("biomicroscopia_oi")->nullable()->comment("Segmento anterior Ojo Izquierdo");
            $table->text("presion_intraocular_od")->nullable()->comment("PIO OD");
            $table->text("presion_intraocular_oi")->nullable()->comment("PIO OI");
            $table->text("fondo_ojo_od")->nullable()->comment("Fondo de ojo OD");
            $table->text("fondo_ojo_oi")->nullable()->comment("Fondo de ojo OI");
            $table->mediumText("examen_fisico_general")->nullable()->comment("Inspeccion general");

            // Signos vitales opcionales
            $table->string("signos_fc", 20)->nullable();
            $table->string("signos_pas", 20)->nullable();
            $table->string("signos_pad", 20)->nullable();
            $table->string("signos_fr", 20)->nullable();
            $table->string("signos_spo2", 20)->nullable();
            $table->decimal("signos_peso", 5, 2)->nullable();
            $table->decimal("signos_talla", 5, 2)->nullable();
            $table->decimal("signos_imc", 5, 2)->nullable();

            // Concepto y Plan
            $table->mediumText("concepto_plan")->nullable();
            $table->string("destino_paciente", 100)->default("Ambulatorio");
            $table->enum("estado_consulta", ["BORRADOR", "FINALIZADA"])->default("BORRADOR");
            $table->dateTime("fechor_finalizacion")->nullable();

            // Trazabilidad SAHI
            $table->unsignedBigInteger("sahi_consulta_id")->nullable()->comment("IdConsulta en hceConsulta de SAHI");
            $table->timestamps();

            $table->index(["atencion_id"]);
            $table->index(["medico_id"]);
            $table->index(["estado_consulta"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("oftalmologia_consultas");
    }
};
