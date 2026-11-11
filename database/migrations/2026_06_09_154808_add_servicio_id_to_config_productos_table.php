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
        Schema::table('config_productos', function (Blueprint $table) {
            $table->unsignedBigInteger('servicio_id')->nullable()->after('id');
            $table->foreign('servicio_id')->references('id')->on('config_servicios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('config_productos', function (Blueprint $table) {
            $table->dropForeign(['servicio_id']);
            $table->dropColumn('servicio_id');
        });
    }
};
