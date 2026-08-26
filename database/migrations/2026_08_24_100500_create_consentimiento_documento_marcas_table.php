<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marcas que el administrativo dibuja sobre las imágenes del consentimiento
 * (punto, círculo, flecha, mano alzada) según indica el paciente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consentimiento_documento_marcas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('documento_id');
            $table->foreign('documento_id')->references('id')->on('consentimiento_documentos')->onDelete('cascade');

            $table->unsignedBigInteger('imagen_id')->nullable();
            $table->foreign('imagen_id')->references('id')->on('consentimiento_plantilla_imagenes');

            $table->unsignedBigInteger('usuario_marca_id');
            $table->foreign('usuario_marca_id')->references('id')->on('users');

            $table->string('tipo_marca'); // punto, circulo, flecha, mano_alzada
            $table->float('posicion_x');
            $table->float('posicion_y');
            $table->float('ancho')->nullable();
            $table->float('alto')->nullable();
            $table->text('descripcion')->nullable();
            $table->json('datos_dibujo')->nullable(); // trazo libre (mano alzada)
            $table->dateTime('fecha_marca');

            $table->timestamps();

            $table->index(['documento_id'], 'ix_consent_marca_doc');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento_documento_marcas');
    }
};
