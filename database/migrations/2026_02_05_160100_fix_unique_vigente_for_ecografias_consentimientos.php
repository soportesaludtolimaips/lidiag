<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ecografias_consentimientos', function (Blueprint $table) {
            if (! Schema::hasColumn('ecografias_consentimientos', 'vigente_key')) {
                $table->unsignedTinyInteger('vigente_key')->nullable()->after('vigente');
                $table->index(['ecografia_producto_id', 'vigente_key'], 'ix_ecog_consent_vigente_key');
            }
        });

        DB::table('ecografias_consentimientos')->where('vigente', 1)->update(['vigente_key' => 1]);
        DB::table('ecografias_consentimientos')->where('vigente', 0)->update(['vigente_key' => null]);

        Schema::table('ecografias_consentimientos', function (Blueprint $table) {
            try {
                $table->dropUnique('uq_ecog_consent_vigente');
            } catch (Throwable $t) {
            }
            $table->unique(['ecografia_producto_id', 'vigente_key'], 'uq_ecog_consent_vigente_key');
        });
    }

    public function down(): void
    {
        Schema::table('ecografias_consentimientos', function (Blueprint $table) {
            try {
                $table->dropUnique('uq_ecog_consent_vigente_key');
            } catch (Throwable $t) {
            }
            try {
                $table->dropIndex('ix_ecog_consent_vigente_key');
            } catch (Throwable $t) {
            }
            if (Schema::hasColumn('ecografias_consentimientos', 'vigente_key')) {
                $table->dropColumn('vigente_key');
            }

            try {
                $table->unique(['ecografia_producto_id', 'vigente'], 'uq_ecog_consent_vigente');
            } catch (Throwable $t) {
            }
        });
    }
};
