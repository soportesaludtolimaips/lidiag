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
        Schema::create('estudios_productos_adendums', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('estudio_producto_id');
            $table->foreign('estudio_producto_id')->references('id')->on('estudios_productos');

            $table->unsignedBigInteger('medico_id');
            $table->foreign('medico_id')->references('id')->on('users');

            $table->unsignedBigInteger('transcriptor_id')->nullable();
            $table->foreign('transcriptor_id')->references('id')->on('users');

            $table->datetime('fechor_lectura')->nullable();
            $table->text('lectura')->nullable();
            $table->datetime('fechor_trascrito')->nullable();
            $table->boolean('activo')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudios_productos_adendums');
    }
};
