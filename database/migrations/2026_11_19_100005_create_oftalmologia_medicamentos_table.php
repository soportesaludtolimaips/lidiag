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
        Schema::create("oftalmologia_medicamentos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("consulta_id")->constrained("oftalmologia_consultas")->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("producto_id")->constrained("config_productos")->cascadeOnUpdate()->restrictOnDelete();

            $table->string("codigo_medicamento", 50)->nullable();
            $table->string("nombre_medicamento", 255);
            $table->string("forma_farmaceutica", 100)->nullable()->comment("Gotas, Colirio, Unguento, Tableta");
            $table->string("dosis", 100)->nullable()->comment("Ej. 1 gota, 500 mg");
            $table->string("via_administracion", 100)->default("Oftalmica");
            $table->string("frecuencia_horario", 150)->nullable()->comment("Ej. Cada 8 horas");
            $table->string("duracion_tratamiento", 100)->nullable()->comment("Ej. 15 dias");
            $table->unsignedSmallInteger("cantidad_entregar")->default(1);
            $table->text("indicaciones")->nullable();

            // Trazabilidad SAHI
            $table->unsignedBigInteger("sahi_formula_id")->nullable();
            $table->unsignedBigInteger("sahi_formula_det_id")->nullable();

            $table->timestamps();
            $table->index(["consulta_id"]);
            $table->index(["producto_id"]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("oftalmologia_medicamentos");
    }
};
