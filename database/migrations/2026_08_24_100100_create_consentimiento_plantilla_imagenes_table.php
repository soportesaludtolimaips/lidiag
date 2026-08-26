<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_plantilla_imagenes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas')->onDelete('cascade');

            $table->string('archivo');
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);

            // true = imagen con zonas interactivas donde el administrativo marca puntos
            $table->boolean('es_para_marcar')->default(false);

            $table->timestamps();

            $table->index(['plantilla_id', 'orden'], 'ix_consent_plant_img_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_plantilla_imagenes');
    }
};
