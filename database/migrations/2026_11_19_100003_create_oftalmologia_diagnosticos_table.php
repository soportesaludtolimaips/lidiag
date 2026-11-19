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
        Schema::create("oftalmologia_diagnosticos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("consulta_id")->constrained("oftalmologia_consultas")->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("diagnostico_id")->constrained("config_diagnosticos")->cascadeOnUpdate()->restrictOnDelete();

            $table->string("cod_cie10", 20);
            $table->string("nombre_diagnostico", 255);
            $table->boolean("es_principal")->default(false);
            $table->enum("tipo_diagnostico", ["IMPRESION_DIAGNOSTICA", "CONFIRMADO_NUEVO", "CONFIRMADO_REPETIDO"])->default("CONFIRMADO_NUEVO");
            $table->enum("lateralidad", ["NO_APLICA", "OJO_DERECHO", "OJO_IZQUIERDO", "BILATERAL"])->default("NO_APLICA");
            $table->string("observaciones", 255)->nullable();

            $table->timestamps();
            $table->index(["consulta_id"]);
            $table->index(["diagnostico_id"]);
            $table->index(["cod_cie10"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("oftalmologia_diagnosticos");
    }
};
