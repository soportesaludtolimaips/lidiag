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
     * 1. Crea la tabla registros_dosis_radiacion para la telemetría dosimétrica conforme a Resolución 482 de 2018.
     * 2. Almacena identificador de paciente con hash SHA-256 para anonimización estricta (ISO/IEC 27001).
     * 3. Registra tipo de métrica (CTDIvol, DLP, DGM, DAP), magnitud numérica y metadatos del estudio.
     * 4. Establece índices y clave compuesta para upsert masivo idempotente por study_uid y métrica.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function up(): void
    {
        Schema::create('registros_dosis_radiacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sede_id');
            $table->unsignedBigInteger('equipo_emisor_id')->nullable();
            $table->string('study_uid', 128)->index();
            $table->string('patient_id_hashed', 64)->index();
            $table->enum('tipo_metrica', ['CTDIvol', 'DLP', 'DGM', 'DAP'])->index();
            $table->decimal('valor_dosis', 12, 4);
            $table->string('unidad_medida', 20)->default('mGy');
            $table->dateTime('fecha_estudio')->index();
            $table->string('accession_no', 50)->nullable()->index();
            $table->string('study_desc', 255)->nullable();
            $table->timestamps();

            $table->foreign('sede_id')
                ->references('id')
                ->on('config_sedes')
                ->onDelete('cascade');

            $table->foreign('equipo_emisor_id')
                ->references('id')
                ->on('equipos_emisores')
                ->onDelete('set null');

            $table->unique(['study_uid', 'tipo_metrica'], 'uq_study_tipo_metrica');
            $table->index(['sede_id', 'fecha_estudio', 'tipo_metrica'], 'idx_dosis_sede_fecha_metrica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registros_dosis_radiacion');
    }
};
