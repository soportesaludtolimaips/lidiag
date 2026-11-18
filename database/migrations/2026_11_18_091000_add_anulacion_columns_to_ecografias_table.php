<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            if (! Schema::hasColumn('ecografias', 'usua_anula_id')) {
                $table->unsignedBigInteger('usua_anula_id')->nullable()->after('estado');
                $table->foreign('usua_anula_id', 'fk_ecografias_usua_anula')
                    ->references('id')
                    ->on('users');
            }

            if (! Schema::hasColumn('ecografias', 'fechor_anula')) {
                $table->dateTime('fechor_anula')->nullable()->after('usua_anula_id');
            }

            if (! Schema::hasColumn('ecografias', 'motivo_anula')) {
                $table->string('motivo_anula', 255)->nullable()->after('fechor_anula');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            if (Schema::hasColumn('ecografias', 'usua_anula_id')) {
                $table->dropForeign('fk_ecografias_usua_anula');
                $table->dropColumn('usua_anula_id');
            }

            if (Schema::hasColumn('ecografias', 'fechor_anula')) {
                $table->dropColumn('fechor_anula');
            }

            if (Schema::hasColumn('ecografias', 'motivo_anula')) {
                $table->dropColumn('motivo_anula');
            }
        });
    }
};
