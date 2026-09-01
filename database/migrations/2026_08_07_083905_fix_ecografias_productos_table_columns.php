<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations de forma idempotente (compatibilidad multi-entorno).
     *
     * @author Davide Rivera (Luis Castaño)
     */
    public function up(): void
    {
        if (! Schema::hasColumn('ecografias_productos', 'usua_regis_id')) {
            Schema::table('ecografias_productos', function (Blueprint $table) {
                $table->unsignedBigInteger('usua_regis_id')->after('ecografia_id');
                $table->foreign('usua_regis_id')->references('id')->on('users');
            });
        }

        $type = collect(DB::select("SHOW COLUMNS FROM `ecografias_productos` WHERE Field = 'estado'"))->first()->Type ?? '';
        if (stripos($type, 'enum') === false) {
            Schema::table('ecografias_productos', function (Blueprint $table) {
                $table->enum('estado', ['EN PROCESO', 'ANULADO', 'LEIDO'])->default('EN PROCESO')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias_productos', function (Blueprint $table) {
            $table->dropForeign(['usua_regis_id']);
            $table->dropColumn('usua_regis_id');
            $table->tinyInteger('estado')->default(1)->change();
        });
    }
};
