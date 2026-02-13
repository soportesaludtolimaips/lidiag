<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_desk_tickets', function (Blueprint $table) {
            // Identificación legible
            $table->string('folio', 40)->nullable()->unique()->after('id');

            // Datos del ticket
            $table->string('titulo', 255)->nullable()->after('folio');
            $table->longText('descripcion')->nullable()->after('titulo'); // HTML/WYSIWYG (sanitizado en backend)

            // Catálogos (FK)
            $table->unsignedBigInteger('categoria_id')->nullable()->after('descripcion');
            $table->unsignedBigInteger('prioridad_id')->nullable()->after('categoria_id');
            $table->unsignedBigInteger('estado_id')->nullable()->after('prioridad_id');

            // Indicadores/fechas de flujo
            $table->timestamp('resolved_at')->nullable()->after('fechor_sol_quien_soluciona');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            $table->timestamp('asignado_visto_at')->nullable()->after('closed_at');

            // Pendiente de (Soporte / Solicitante)
            $table->enum('pendiente_de', ['SOPORTE', 'SOLICITANTE'])->nullable()->after('asignado_visto_at');

            // Soft delete
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('help_desk_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'folio',
                'titulo',
                'descripcion',
                'categoria_id',
                'prioridad_id',
                'estado_id',
                'resolved_at',
                'closed_at',
                'asignado_visto_at',
                'pendiente_de',
                'deleted_at',
            ]);
        });
    }
};
