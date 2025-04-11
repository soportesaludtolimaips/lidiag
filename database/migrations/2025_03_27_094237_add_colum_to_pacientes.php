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
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('seg_nom', 70)->nullable()->after('nombres');
            $table->string('pri_ape', 30)->nullable()->after('seg_nom');
            $table->string('seg_ape', 30)->nullable()->after('pri_ape');
            $table->string('cel', 20)->nullable()->after('tel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropColumn('seg_nom');
            $table->dropColumn('pri_ape');
            $table->dropColumn('seg_ape');
            $table->dropColumn('cel');
        });
    }
};
