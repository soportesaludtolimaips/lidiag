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
            $table->mediumText('lectura')->nullable()->change();
            $table->mediumText('opinion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias_productos', function (Blueprint $table) {
            $table->string('lectura', 255)->nullable()->change();
            $table->string('opinion', 255)->nullable()->change();
        });
    }
};
