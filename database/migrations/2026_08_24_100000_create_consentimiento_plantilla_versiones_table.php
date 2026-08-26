<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_plantilla_versiones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas')->onDelete('cascade');

            $table->unsignedInteger('version_numero');
            $table->longText('html');

            $table->unsignedBigInteger('usuario_crea_id');
            $table->foreign('usuario_crea_id')->references('id')->on('users');

            $table->text('cambios')->nullable();
            $table->dateTime('fecha_version');

            $table->timestamps();

            $table->unique(['plantilla_id', 'version_numero'], 'uq_consent_plant_version');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_plantilla_versiones');
    }
};
