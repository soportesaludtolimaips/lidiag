<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tokens de acceso para firma del paciente sin login.
 *
 * Se almacena solo el hash SHA-256 del token (nunca el token en claro),
 * con expiración y control de usos. Soporta código QR para tablet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_documento_tokens', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('documento_id');
            $table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');

            $table->string('token_hash', 64)->unique();
            $table->string('codigo_qr')->nullable();

            $table->unsignedInteger('usos_maximos')->default(1);
            $table->unsignedInteger('usos_actuales')->default(0);

            $table->dateTime('expires_at')->nullable();
            $table->dateTime('used_at')->nullable();

            $table->string('ip_generacion', 45)->nullable();
            $table->string('ip_uso', 45)->nullable();

            $table->boolean('activo')->default(true);

            $table->unsignedBigInteger('generado_por_id')->nullable();
            $table->foreign('generado_por_id')->references('id')->on('users');

            $table->timestamps();

            $table->index(['documento_id', 'activo'], 'ix_consent_token_doc_activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_documento_tokens');
    }
};
