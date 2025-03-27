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
        Schema::create('ecografias_productos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ecografia_id');
            $table->foreign('ecografia_id')->references('id')->on('ecografias');

            $table->unsignedBigInteger('medico_id');
            $table->foreign('medico_id')->references('id')->on('users');

            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('config_productos');

            $table->string('lectura')->nullable();
            $table->string('opinion')->nullable();
            $table->datetime('fechor_lectura')->nullable();
            $table->boolean('estado')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecografias_productos');
    }
};
