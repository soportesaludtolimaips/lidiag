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
     * 1. Crea la tabla equipos_emisores para el inventario de generadores de radiación ionizante por sede.
     * 2. Registra modalidad (CT, MG, DX, CR), marca, modelo y AET Title del equipo.
     * 3. Establece clave foránea vinculada a config_sedes (sede_id) e índices de búsqueda.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-23
     */
    public function up(): void
    {
        Schema::create('equipos_emisores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sede_id');
            $table->string('nombre', 150);
            $table->enum('modalidad', ['CT', 'MG', 'DX', 'CR'])->index();
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('aet_title', 50)->nullable()->index();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->foreign('sede_id')
                ->references('id')
                ->on('config_sedes')
                ->onDelete('cascade');

            $table->index(['sede_id', 'modalidad', 'estado'], 'idx_equipos_sede_modalidad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipos_emisores');
    }
};
