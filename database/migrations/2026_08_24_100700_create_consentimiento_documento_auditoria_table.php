<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Auditoría append-only del ciclo de vida de cada consentimiento.
 *
 * Registro dedicado (además del trait LogsActivity) por requisito legal/médico:
 * quién hizo qué, cuándo, desde qué IP y navegador. No debe actualizarse ni
 * borrarse por lógica de aplicación (solo INSERT).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_documento_auditoria', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('documento_id');
            $table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');

            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->foreign('usuario_id')->references('id')->on('users');

            // create, view, edit, firma_paciente, firma_medico, marcar,
            // generar_token, usar_token, exportar, anular, restaurar
            $table->string('accion');

            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('descripcion')->nullable();

            $table->dateTime('fecha_accion');

            $table->timestamps();

            $table->index(['documento_id', 'fecha_accion'], 'ix_consent_audit_doc_fecha');
            $table->index(['usuario_id', 'fecha_accion'], 'ix_consent_audit_user_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_documento_auditoria');
    }
};
