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
        Schema::create("oftalmologia_remisiones", function (Blueprint $table) {
            $table->id();
            $table->foreignId("consulta_id")->constrained("oftalmologia_consultas")->cascadeOnUpdate()->cascadeOnDelete();

            $table->string("institucion_receptora", 200)->nullable();
            $table->string("especialidad_solicitada", 150)->nullable();
            $table->string("motivo_remision", 255)->nullable();
            $table->mediumText("resumen_clinico")->nullable();
            $table->text("observaciones")->nullable();

            // Trazabilidad SAHI
            $table->unsignedBigInteger("sahi_remision_id")->nullable();

            $table->timestamps();
            $table->unique(["consulta_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("oftalmologia_remisiones");
    }
};
