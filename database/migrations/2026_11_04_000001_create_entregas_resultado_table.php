<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de cada envío del enlace de resultados al paciente (WhatsApp / correo).
 *
 * Sirve para no enviar dos veces el mismo resultado de forma automática, para mostrar el estado
 * en pantalla y como trazabilidad. Nunca guarda el número ni el correo completos (solo
 * enmascarados) ni el enlace.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('entregas_resultado')) {
            return;
        }

        Schema::create('entregas_resultado', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('estudio_producto_id');
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->unsignedBigInteger('lectura_portal_id')->nullable();

            $table->string('canal', 20);          // whatsapp | correo
            $table->string('destino', 120)->nullable(); // enmascarado
            $table->string('estado', 20);         // enviado | fallido | omitido
            $table->string('detalle', 500)->nullable(); // id del mensaje o motivo

            $table->boolean('automatico')->default(true);
            $table->unsignedBigInteger('usuario_id')->nullable();

            $table->timestamps();

            $table->index(['estudio_producto_id', 'canal', 'estado'], 'entregas_resultado_busqueda');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas_resultado');
    }
};
