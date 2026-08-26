<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documentos adicionales adjuntos a un consentimiento
 * (documento de identidad, orden médica, etc.).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_documento_adjuntos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('documento_id');
            $table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');

            $table->unsignedBigInteger('usuario_sube_id')->nullable();
            $table->foreign('usuario_sube_id')->references('id')->on('users');

            $table->string('nombre_original');
            $table->string('archivo');
            $table->string('tipo_mime');
            $table->unsignedBigInteger('tamano'); // bytes
            $table->string('categoria'); // documento_identidad, orden_medica, historia_clinica, laboratorio, imagenes, otro

            $table->timestamps();

            $table->index(['documento_id'], 'ix_consent_adjunto_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_documento_adjuntos');
    }
};
