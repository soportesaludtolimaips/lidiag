<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hace producto_id opcional en consentimiento_plantillas.
 *
 * Permite administrar plantillas de consentimiento de forma independiente de un
 * producto (finalidad del módulo: gestionar las plantillas como están en los
 * documentos). La asignación a productos se hace por la tabla N:M
 * consentimiento_plantilla_productos. No afecta el flujo de ecografías, que
 * conserva su producto_id.
 *
 * @author Davide
 *
 * @date 2026-08-28
 */
return new class extends Migration
{
    /**
     * Aplica la migración: producto_id pasa a ser nullable.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable()->change();
        });
    }

    /**
     * Revierte la migración: producto_id vuelve a ser obligatorio.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            $table->unsignedBigInteger('producto_id')->nullable(false)->change();
        });
    }
};
