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
        Schema::table('estudios', function (Blueprint $table) {
            $table->enum('destino_estudio', ['Salud Tolima', 'Tele Radiologia'])->default('Salud Tolima')->nullable()->after('estado')->comment('Indica a que otra plataforma se envio el estudio para lectura externa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudios', function (Blueprint $table) {
            $table->dropColumn('destino_estudio');
        });
    }
};
