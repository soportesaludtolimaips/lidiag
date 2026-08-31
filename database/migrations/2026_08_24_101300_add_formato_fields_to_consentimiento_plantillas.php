<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega metadatos de formato institucional a las plantillas de consentimiento
 * (código, versión y año), para reproducir el encabezado de los documentos
 * físicos de cada sede en el PDF generado.
 *
 * @author Davide
 *
 * @date 2026-08-28
 */
return new class extends Migration
{
    /**
     * Aplica la migración: agrega codigo, version_formato y anio_formato.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            if (! Schema::hasColumn('consentimiento_plantillas', 'codigo')) {
                $table->string('codigo', 60)->nullable()->after('nombre');
            }
            if (! Schema::hasColumn('consentimiento_plantillas', 'version_formato')) {
                $table->string('version_formato', 20)->nullable()->after('codigo');
            }
            if (! Schema::hasColumn('consentimiento_plantillas', 'anio_formato')) {
                $table->string('anio_formato', 10)->nullable()->after('version_formato');
            }
        });
    }

    /**
     * Revierte la migración: elimina los campos de formato.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            foreach (['codigo', 'version_formato', 'anio_formato'] as $col) {
                if (Schema::hasColumn('consentimiento_plantillas', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
