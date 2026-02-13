<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecografias_consentimientos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ecografia_producto_id');
            $table->foreign('ecografia_producto_id')->references('id')->on('ecografias_productos');

            // redundante pero útil para reportes/filtros
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('sede_id')->nullable();
            $table->foreign('sede_id')->references('id')->on('config_sedes');

            $table->unsignedBigInteger('config_modulo_id');
            $table->foreign('config_modulo_id')->references('id')->on('config_modulos');

            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas');

            $table->unsignedInteger('plantilla_version')->default(1);
            $table->longText('plantilla_html');

            $table->boolean('vigente')->default(true);

            $table->enum('estado', ['PENDIENTE_PACIENTE', 'PENDIENTE_MEDICO', 'COMPLETO', 'ANULADO'])
                ->default('PENDIENTE_PACIENTE');

            // Solo firma 1 persona: paciente o acompañante
            $table->enum('firmante_tipo', ['PACIENTE', 'ACOMPANANTE'])->default('PACIENTE');

            // Datos acompañante (solo si firmante_tipo=ACOMPANANTE)
            $table->string('acompanante_cedula', 30)->nullable();
            $table->string('acompanante_nombres', 200)->nullable();
            $table->string('acompanante_email', 150)->nullable();
            $table->string('acompanante_direccion', 255)->nullable();
            $table->string('acompanante_telefono', 50)->nullable();

            // Token de firma paciente (sin login)
            $table->string('token_hash', 64)->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->dateTime('token_used_at')->nullable();
            $table->string('ip_token', 45)->nullable();

            // Firma paciente/acompañante (PNG)
            $table->string('firma_paciente_path')->nullable();
            $table->dateTime('firma_paciente_at')->nullable();
            $table->string('ip_firma_paciente', 45)->nullable();

            // Médico que firma (usa firma en users.firma, pero guardamos snapshot del path)
            $table->unsignedBigInteger('medico_id')->nullable();
            $table->foreign('medico_id')->references('id')->on('users');
            $table->string('firma_medico_path')->nullable();
            $table->dateTime('firma_medico_at')->nullable();
            $table->string('ip_firma_medico', 45)->nullable();

            // Auditoría transcriptor
            $table->unsignedBigInteger('transcriptor_id');
            $table->foreign('transcriptor_id')->references('id')->on('users');
            $table->string('ip_transcriptor', 45)->nullable();

            // Anulación
            $table->unsignedBigInteger('anulada_por_id')->nullable();
            $table->foreign('anulada_por_id')->references('id')->on('users');
            $table->dateTime('anulada_at')->nullable();
            $table->string('anulacion_observacion', 255)->nullable();

            // PDF generado (se guarda, no se regenera al vuelo)
            $table->string('pdf_path')->nullable();
            $table->dateTime('pdf_generated_at')->nullable();

            $table->timestamps();

            // 1 consentimiento vigente por ecografia_producto (permite históricos con vigente=0)
            $table->unique(['ecografia_producto_id', 'vigente'], 'uq_ecog_consent_vigente');
            $table->index(['estado', 'sede_id'], 'ix_ecog_consent_estado_sede');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecografias_consentimientos');
    }
};
