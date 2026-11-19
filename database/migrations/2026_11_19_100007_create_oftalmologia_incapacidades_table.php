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
        Schema::create("oftalmologia_incapacidades", function (Blueprint $table) {
            $table->id();
            $table->foreignId("consulta_id")->constrained("oftalmologia_consultas")->cascadeOnUpdate()->cascadeOnDelete();

            $table->date("fec_inicio");
            $table->date("fec_fin");
            $table->unsignedSmallInteger("dias_incapacidad");
            $table->string("tipo_incapacidad", 100)->default("Ambulatoria");
            $table->string("origen_incapacidad", 100)->default("Enfermedad General");
            $table->string("empresa", 150)->nullable();
            $table->string("ocupacion", 150)->nullable();
            $table->string("telefono", 50)->nullable();
            $table->boolean("es_prorroga")->default(false);
            $table->text("observaciones")->nullable();

            // Trazabilidad SAHI
            $table->unsignedBigInteger("sahi_incapacidad_id")->nullable();

            $table->timestamps();
            $table->unique(["consulta_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("oftalmologia_incapacidades");
    }
};
