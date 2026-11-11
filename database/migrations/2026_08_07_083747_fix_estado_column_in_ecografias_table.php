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
            $table->enum('estado', ['EN PROCESO', 'ANULADO', 'LEIDO'])->default('EN PROCESO')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            $table->tinyInteger('estado')->default(0)->change();
        });
    }
};
