<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contrato_id')
                ->constrained('contratos');
            $table->foreignId('producto_id')
                ->constrained('config_productos');
            $table->decimal('valor_contrato', 12, 2)->default(0);
            $table->boolean('estado')->default(1);
            $table->timestamps();

            $table->index('contrato_id');
            $table->index('producto_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos_productos');
    }
};
