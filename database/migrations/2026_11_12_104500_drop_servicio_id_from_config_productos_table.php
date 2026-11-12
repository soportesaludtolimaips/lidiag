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
            if (Schema::hasColumn('config_productos', 'servicio_id')) {
                $table->dropForeign(['servicio_id']);
                $table->dropColumn('servicio_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_productos', function (Blueprint $table) {
            if (! Schema::hasColumn('config_productos', 'servicio_id')) {
                $table->foreignId('servicio_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('config_servicios')
                    ->nullOnDelete();
            }
        });
    }
};
