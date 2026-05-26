<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('estudios_audios', function (Blueprint $table) {
            $table->enum('tipo', ['grabado', 'cargado'])
                ->default('grabado')
                ->after('audio')
                ->comment('Origen del audio: grabado en vivo o cargado desde archivo');

            $table->string('nombre_original')
                ->nullable()
                ->after('tipo')
                ->comment('Nombre original del archivo cargado');

            $table->string('formato')
                ->nullable()
                ->after('nombre_original')
                ->comment('Formato del archivo: mp3, wav, m4a, webm, ogg');

            $table->integer('duracion')
                ->nullable()
                ->after('formato')
                ->comment('Duracion del audio en segundos');

            $table->integer('tamano')
                ->nullable()
                ->after('duracion')
                ->comment('Tamano del archivo en bytes');

            $table->unsignedBigInteger('usuario_id')
                ->nullable()
                ->after('tamano')
                ->comment('Medico que grabo o cargo el audio');

            $table->foreign('usuario_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudios_audios', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
            $table->dropColumn([
                'tipo',
                'nombre_original',
                'formato',
                'duracion',
                'tamano',
                'usuario_id',
            ]);
        });
    }
};
