<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-17
     */
    public function up(): void
    {
        if (! Schema::hasTable('config_tipos_recaudo')) {
            Schema::create('config_tipos_recaudo', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 20)->unique();
                $table->string('nombre', 100);
                $table->boolean('aplica_recaudo')->default(true);
                $table->boolean('envia_sahi')->default(false);
                $table->string('descripcion', 255)->nullable();
                $table->boolean('estado')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_tipos_recaudo');
    }
};
