<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Flujo del proceso:
     * 1. Crea la tabla worklist_ordenes para trazabilidad de órdenes enviadas a la Worklist DICOM.
     * 2. Registra las claves foráneas a pacientes, sedes, productos y usuarios.
     * 3. Establece índices para consultas rápidas por paciente, sede y estado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-21
     */
    public function up(): void
    {
        Schema::create('worklist_ordenes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('sede_id');
            $table->unsignedBigInteger('producto_id')->nullable();
            $table->string('accession_no', 50)->index();
            $table->string('study_iuid', 128)->index();
            $table->string('modality', 10)->default('CR');
            $table->string('station_aet', 50)->default('RAYOSX');
            $table->unsignedBigInteger('mwl_item_pk')->nullable();
            $table->enum('estado', ['PENDIENTE', 'CANCELADO', 'COMPLETADO'])->default('PENDIENTE')->index();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('cascade');
            $table->foreign('sede_id')->references('id')->on('config_sedes')->onDelete('cascade');
            $table->foreign('producto_id')->references('id')->on('config_productos')->onDelete('set null');
            $table->foreign('usuario_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['sede_id', 'estado', 'created_at'], 'idx_worklist_sede_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('worklist_ordenes');
    }
};
