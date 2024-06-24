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
        Schema::table('config_sedes', function (Blueprint $table) {
            $table->string('plantilla', 150)->nullable()->after('interface_password')->comment('Nombre de la plantilla para las lecturas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            $table->dropColumn('plantilla');
        });
    }
};
