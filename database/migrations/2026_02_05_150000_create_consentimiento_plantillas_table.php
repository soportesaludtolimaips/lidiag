<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_plantillas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('config_modulo_id');
            $table->foreign('config_modulo_id')->references('id')->on('config_modulos');

            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->unsignedBigInteger('usuario_crea_id');
            $table->foreign('usuario_crea_id')->references('id')->on('users');

            $table->string('nombre', 255);
            $table->longText('html');

            $table->unsignedInteger('version')->default(1);
            $table->boolean('vigente')->default(true);

            $table->timestamps();

            // 1 plantilla vigente por (módulo, producto)
            $table->unique(['config_modulo_id', 'producto_id', 'vigente'], 'uq_consent_plantilla_vigente');
            $table->index(['config_modulo_id', 'producto_id'], 'ix_consent_plantilla_modulo_producto');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_plantillas');
    }
};
