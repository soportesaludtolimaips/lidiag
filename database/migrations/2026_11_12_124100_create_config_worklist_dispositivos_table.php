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
     * @date 2026-11-12
     */
    public function up(): void
    {
        Schema::create('config_worklist_dispositivos', function (Blueprint $table) {
            $table->id();

            // Relaciones obligatorias
            $table->unsignedBigInteger('sede_id')->index();
            $table->unsignedBigInteger('modulo_id')->index();

            // Atributos del dispositivo
            $table->string('codigo', 50)->comment('Identificador único o serial del equipo (ej. RX-S01-01)');
            $table->string('nombre', 150)->comment('Nombre descriptivo del dispositivo (ej. Rayos X Sala 1)');
            $table->string('ae_title', 64)->nullable()->comment('Application Entity Title para integración DICOM/Worklist');
            $table->string('modality', 16)->nullable()->comment('Modalidad DICOM: CR, DX, CT, MR, US, MG, etc.');
            $table->string('ip_address', 45)->nullable()->comment('Dirección IP de red local del equipo');
            $table->unsignedInteger('puerto')->nullable()->comment('Puerto de comunicación DICOM (ej. 104, 11112)');
            $table->string('ubicacion', 150)->nullable()->comment('Sala, consultorio o área física en la sede');
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true)->comment('1: Activo, 0: Inactivo');

            $table->timestamps();

            // Foreign Keys
            $table->foreign('sede_id')
                ->references('id')
                ->on('config_sedes')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('modulo_id')
                ->references('id')
                ->on('config_modulos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Restricción única por sede: el código no debe repetirse en la misma sede
            $table->unique(['sede_id', 'codigo'], 'uq_dispositivo_sede_codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-12
     */
    public function down(): void
    {
        Schema::dropIfExists('config_worklist_dispositivos');
    }
};
