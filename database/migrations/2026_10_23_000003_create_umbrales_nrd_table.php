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
     * 1. Crea la tabla umbrales_nrd para almacenar los Niveles de Referencia para Diagnóstico (Resolución 482 de 2018).
     * 2. Permite parametrizar umbrales por modalidad y tipo de métrica, con soporte opcional para umbral específico por sede.
     * 3. Registra valor de advertencia (alerta preventiva) y valor máximo regulatorio NRD.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function up(): void
    {
        Schema::create('umbrales_nrd', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->enum('modalidad', ['CT', 'MG', 'DX', 'CR'])->index();
            $table->enum('tipo_metrica', ['CTDIvol', 'DLP', 'DGM', 'DAP'])->index();
            $table->decimal('valor_alerta', 12, 4)->comment('Valor preventivo (ej. 80% del NRD)');
            $table->decimal('valor_limite_nrd', 12, 4)->comment('Nivel de Referencia para Diagnóstico regulatorio');
            $table->string('unidad_medida', 20)->default('mGy');
            $table->string('descripcion', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('sede_id')
                ->references('id')
                ->on('config_sedes')
                ->onDelete('cascade');

            $table->index(['modalidad', 'tipo_metrica', 'estado'], 'idx_umbrales_modalidad_metrica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('umbrales_nrd');
    }
};
