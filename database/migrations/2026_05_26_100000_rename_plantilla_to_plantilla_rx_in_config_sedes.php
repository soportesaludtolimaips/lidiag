<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('config_sedes', 'plantilla') && ! Schema::hasColumn('config_sedes', 'plantilla_rx')) {
            Schema::table('config_sedes', function (Blueprint $table) {
                $table->renameColumn('plantilla', 'plantilla_rx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('config_sedes', 'plantilla_rx') && ! Schema::hasColumn('config_sedes', 'plantilla')) {
            Schema::table('config_sedes', function (Blueprint $table) {
                $table->renameColumn('plantilla_rx', 'plantilla');
            });
        }
    }
};
