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
        Schema::table('estudios_audios', function (Blueprint $table) {
            $table->longText('transcripcion')
                ->nullable()
                ->after('tamano')
                ->comment('Texto de la transcripcion del audio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estudios_audios', function (Blueprint $table) {
            $table->dropColumn('transcripcion');
        });
    }
};
