<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            // Único "vigente" real: usamos NULL para no-vigentes (MySQL permite múltiples NULL en unique)
            if (! Schema::hasColumn('consentimiento_plantillas', 'vigente_key')) {
                $table->unsignedTinyInteger('vigente_key')->nullable()->after('vigente');
                $table->index(['config_modulo_id', 'producto_id', 'vigente_key'], 'ix_consent_plantilla_vigente_key');
            }
        });

        // Poblar vigente_key
        DB::table('consentimiento_plantillas')->where('vigente', 1)->update(['vigente_key' => 1]);
        DB::table('consentimiento_plantillas')->where('vigente', 0)->update(['vigente_key' => null]);

        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            // Quitar unique anterior (bloqueaba múltiples históricos)
            try {
                $table->dropUnique('uq_consent_plantilla_vigente');
            } catch (\Throwable $t) {
                // ignore
            }
            $table->unique(['config_modulo_id', 'producto_id', 'vigente_key'], 'uq_consent_plantilla_vigente_key');
        });
    }

    public function down(): void
    {
        Schema::table('consentimiento_plantillas', function (Blueprint $table) {
            try {
                $table->dropUnique('uq_consent_plantilla_vigente_key');
            } catch (\Throwable $t) {
            }
            try {
                $table->dropIndex('ix_consent_plantilla_vigente_key');
            } catch (\Throwable $t) {
            }
            if (Schema::hasColumn('consentimiento_plantillas', 'vigente_key')) {
                $table->dropColumn('vigente_key');
            }

            // Restaurar el unique anterior (no recomendado, pero para rollback)
            try {
                $table->unique(['config_modulo_id', 'producto_id', 'vigente'], 'uq_consent_plantilla_vigente');
            } catch (\Throwable $t) {
            }
        });
    }
};
