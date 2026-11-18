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
            if (! Schema::hasColumn('ecografias', 'ubicacion')) {
                $table->string('ubicacion', 150)->nullable()->after('quien_ordena');
            }

            if (! Schema::hasColumn('ecografias', 'cama')) {
                $table->string('cama', 50)->nullable()->after('ubicacion');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecografias', function (Blueprint $table) {
            if (Schema::hasColumn('ecografias', 'cama')) {
                $table->dropColumn('cama');
            }

            if (Schema::hasColumn('ecografias', 'ubicacion')) {
                $table->dropColumn('ubicacion');
            }
        });
    }
};
