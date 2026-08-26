<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liberaciones temporales del bloqueo de lecturas de un médico.
 *
 * Cuando un médico tiene consentimientos PENDIENTE_MEDICO, sus endpoints de
 * lectura quedan bloqueados. Un administrador puede liberarlo por un periodo
 * acotado (liberado_hasta); al vencer, el bloqueo se restablece solo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_bloqueo_liberaciones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('medico_id');
            $table->foreign('medico_id')->references('id')->on('users');

            $table->unsignedBigInteger('liberado_por_id');
            $table->foreign('liberado_por_id')->references('id')->on('users');

            $table->dateTime('liberado_hasta');
            $table->string('motivo', 255)->nullable();

            $table->timestamps();

            $table->index(['medico_id', 'liberado_hasta'], 'ix_consent_liberacion_medico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_bloqueo_liberaciones');
    }
};
