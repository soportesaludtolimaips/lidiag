<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Documento de consentimiento genérico (no atado a ecografías).
 *
 * Superset del esquema probado en `ecografias_consentimientos`, desacoplado
 * del origen mediante (origen_tipo, origen_id) para poder generarse desde
 * cualquier módulo: ecografías, estudios, atención o manual.
 *
 * El token de firma del paciente vive en `consentimiento_documento_tokens`
 * (soporta QR y usos múltiples). El reloj del documento (24h) es
 * `fecha_expiracion`, distinto de la expiración del token.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_documentos', function (Blueprint $table) {
            $table->id();

            // Origen genérico (desacople del módulo concreto)
            $table->enum('origen_tipo', ['ECOGRAFIA', 'ESTUDIO', 'ATENCION', 'MANUAL'])->default('MANUAL');
            $table->unsignedBigInteger('origen_id')->nullable();

            // Contexto para reportes/filtros
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('sede_id')->nullable();
            $table->foreign('sede_id')->references('id')->on('config_sedes');

            $table->unsignedBigInteger('config_modulo_id');
            $table->foreign('config_modulo_id')->references('id')->on('config_modulos');

            // Plantilla usada + snapshot inmutable de su HTML/versión
            $table->unsignedBigInteger('plantilla_id');
            $table->foreign('plantilla_id')->references('id')->on('consentimiento_plantillas');
            $table->unsignedInteger('plantilla_version')->default(1);
            $table->longText('plantilla_html');
            $table->longText('contenido_renderizado')->nullable();

            // Snapshot de datos del paciente (docs genéricos no siempre traen fila con datos)
            $table->unsignedBigInteger('paciente_id')->nullable();
            $table->string('num_docu', 50)->nullable();
            $table->string('nombres', 255)->nullable();
            $table->string('apellidos', 255)->nullable();

            // Vigencia (histórico con vigente_key nulo — patrón del módulo de ecografías)
            $table->boolean('vigente')->default(true);
            $table->unsignedTinyInteger('vigente_key')->nullable();

            // Máquina de estados
            $table->enum('estado', [
                'GENERADO',
                'PENDIENTE_PACIENTE',
                'PENDIENTE_MEDICO',
                'FIRMADO_PACIENTE',
                'FIRMADO_MEDICO',
                'COMPLETO',
                'ANULADO',
                'EXPIRADO',
            ])->default('GENERADO');

            $table->boolean('requiere_firma_medico')->default(true);

            // Reloj del documento (p.ej. 24h). Distinto de la expiración del token.
            $table->dateTime('fecha_expiracion')->nullable();

            // Firmante: paciente o acompañante
            $table->enum('firmante_tipo', ['PACIENTE', 'ACOMPANANTE'])->default('PACIENTE');
            $table->string('acompanante_cedula', 30)->nullable();
            $table->string('acompanante_nombres', 200)->nullable();
            $table->string('acompanante_email', 150)->nullable();
            $table->string('acompanante_direccion', 255)->nullable();
            $table->string('acompanante_telefono', 50)->nullable();

            // Firma paciente/acompañante (PNG)
            $table->string('firma_paciente_path')->nullable();
            $table->dateTime('firma_paciente_at')->nullable();
            $table->string('ip_firma_paciente', 45)->nullable();

            // Firma médico
            $table->unsignedBigInteger('medico_id')->nullable();
            $table->foreign('medico_id')->references('id')->on('users');
            $table->string('firma_medico_path')->nullable();
            $table->dateTime('firma_medico_at')->nullable();
            $table->string('ip_firma_medico', 45)->nullable();

            // Quién generó el documento
            $table->unsignedBigInteger('generado_por_id');
            $table->foreign('generado_por_id')->references('id')->on('users');
            $table->string('ip_generado', 45)->nullable();

            // Anulación
            $table->unsignedBigInteger('anulada_por_id')->nullable();
            $table->foreign('anulada_por_id')->references('id')->on('users');
            $table->dateTime('anulada_at')->nullable();
            $table->string('anulacion_observacion', 255)->nullable();

            // PDF generado (se persiste, no se regenera al vuelo)
            $table->string('pdf_path')->nullable();
            $table->dateTime('pdf_generated_at')->nullable();

            $table->timestamps();

            // 1 documento vigente por origen (permite históricos con vigente_key nulo)
            $table->unique(['origen_tipo', 'origen_id', 'vigente_key'], 'uq_consent_doc_vigente');
            $table->index(['estado', 'sede_id'], 'ix_consent_doc_estado_sede');
            $table->index(['estado', 'fecha_expiracion'], 'ix_consent_doc_estado_exp');
            $table->index(['medico_id', 'estado'], 'ix_consent_doc_medico_estado');
            $table->index(['num_docu'], 'ix_consent_doc_num_docu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_documentos');
    }
};
