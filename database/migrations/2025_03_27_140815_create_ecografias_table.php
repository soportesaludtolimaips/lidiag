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
        Schema::create('ecografias', function (Blueprint $table) {
            $table->id();
            $table->string('atencion_id', 20);

            $table->unsignedBigInteger('paciente_id');
            $table->foreign('paciente_id')->references('id')->on('pacientes');

            $table->unsignedBigInteger('usua_regis_id');
            $table->foreign('usua_regis_id')->references('id')->on('users');

            $table->unsignedBigInteger('admin_salud_id');
            $table->foreign('admin_salud_id')->references('id')->on('config_admin_salud');

            $table->date('fec_cita_sahi')->nullable();
            $table->date('fec_regis_cita_sahi')->nullable();

            $table->string('contrato', 100)->nullable();
            $table->string('plan', 100)->nullable();
            $table->string('quien_ordena', 100)->nullable();
            $table->boolean('estado')->dafault(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecografias');
    }
};
