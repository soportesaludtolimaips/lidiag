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
        Schema::create('config_plantillas_detalles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('plantilla_tipo_id');
            $table->foreign('plantilla_tipo_id')->references('id')->on('config_plantillas_tipos');

            $table->unsignedBigInteger('medico_id');
            $table->foreign('medico_id')->references('id')->on('users');

            $table->string('nom_plantilla', 150);
            $table->string('planitalla');
            $table->string('opinion');
            $table->boolean('estado')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_plantillas_detalles');
    }
};
