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
        Schema::table('ecografias_productos', function (Blueprint $table) {
            $table->unsignedBigInteger('usua_regis_id')->after('ecografia_id');
            $table->foreign('usua_regis_id')->references('id')->on('users');
            $table->enum('estado', ['EN PROCESO', 'ANULADO', 'LEIDO'])->default('EN PROCESO')->change();
        });
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
