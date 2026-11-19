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
        Schema::create("config_plantillas_oftalmologia", function (Blueprint $table) {
            $table->id();
            $table->foreignId("medico_id")->nullable()->constrained("users")->cascadeOnUpdate()->cascadeOnDelete()->comment("Null para plantillas globales");
            $table->foreignId("sede_id")->nullable()->constrained("config_sedes")->cascadeOnUpdate()->nullOnDelete();

            $table->enum("seccion", ["EXAMEN_FISICO", "CONCEPTO_PLAN", "PROCEDIMIENTOS", "MEDICAMENTOS", "ANAMNESIS"]);
            $table->string("titulo", 150);
            $table->mediumText("contenido");
            $table->boolean("activo")->default(true);

            $table->timestamps();
            $table->index(["medico_id", "seccion"]);
            $table->index(["activo"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("config_plantillas_oftalmologia");
    }
};
