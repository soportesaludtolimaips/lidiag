<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrato_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')
                ->constrained('contratos');
            $table->foreignId('medico_id')
                ->constrained('users');
            $table->foreignId('producto_id')
                ->constrained('config_productos');
            $table->decimal('valor_contrato', 12, 2)->default(0);
            $table->decimal('valor_consulta', 12, 2)->default(0);
            $table->decimal('valor_urgencias', 12, 2)->default(0);
            $table->boolean('estado')->default(1);
            $table->timestamps();

            $table->index('contrato_id');
            $table->index('medico_id');
            $table->index('producto_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrato_detalle');
    }
};
