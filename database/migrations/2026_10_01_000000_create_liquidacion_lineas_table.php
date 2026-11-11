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
        Schema::create('liquidacion_lineas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('estudio_producto_id')->unique();
            $table->unsignedBigInteger('estudio_id');
            $table->unsignedBigInteger('sede_id');
            $table->unsignedBigInteger('contrato_id')->nullable();
            $table->unsignedBigInteger('medico_id')->nullable();
            $table->unsignedBigInteger('producto_id');
            $table->string('clase', 20)->nullable();
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('exclusion', 30)->nullable();
            $table->date('fecha_lectura');
            $table->char('periodo', 7);
            $table->tinyInteger('estado')->default(1);
            $table->timestamps();

            $table->index(['sede_id', 'periodo']);
            $table->index(['medico_id', 'periodo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidacion_lineas');
    }
};
