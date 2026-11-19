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
        Schema::create("oftalmologia_procedimientos", function (Blueprint $table) {
            $table->id();
            $table->foreignId("consulta_id")->constrained("oftalmologia_consultas")->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId("producto_id")->constrained("config_productos")->cascadeOnUpdate()->restrictOnDelete();

            $table->string("cod_cups", 30);
            $table->string("nombre_procedimiento", 255);
            $table->unsignedSmallInteger("cantidad")->default(1);
            $table->text("justificacion_clinica")->nullable();
            $table->text("observaciones")->nullable();

            // Trazabilidad SAHI
            $table->unsignedBigInteger("sahi_proce_orden_id")->nullable();
            $table->unsignedBigInteger("sahi_proce_detalle_id")->nullable();

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
        Schema::dropIfExists("oftalmologia_procedimientos");
    }
};
