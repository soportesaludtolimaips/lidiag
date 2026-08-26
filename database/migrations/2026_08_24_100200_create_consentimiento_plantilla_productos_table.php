<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asignación N:M de una plantilla de consentimiento a productos y sedes.
 *
 * Generaliza el binding rígido (config_modulo_id, producto_id) de
 * `consentimiento_plantillas`: una misma plantilla puede aplicar a varios
 * productos y varias sedes, con la opción de exigir (o no) firma médica.
 * (Idea adoptada del RIS Django: ConsentType.procedure_types / headquarters.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_plantilla_productos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas')->onDelete('cascade');

            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('sede_id')->nullable();
            $table->foreign('sede_id')->references('id')->on('config_sedes');

            $table->boolean('requiere_firma_medico')->default(true);

            $table->timestamps();

            $table->unique(['plantilla_id', 'producto_id', 'sede_id'], 'uq_consent_plant_prod_sede');
            $table->index(['producto_id', 'sede_id'], 'ix_consent_plant_prod_sede');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_plantilla_productos');
    }
};
