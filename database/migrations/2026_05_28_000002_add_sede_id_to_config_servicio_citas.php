<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('config_servicios');

        if (! in_array('sede_id', $columns)) {
            Schema::table('config_servicios', function (Blueprint $table) {
                $table->foreignId('sede_id')->nullable()->constrained('config_sedes')->nullOnDelete()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('config_servicios', function (Blueprint $table) {
            $table->dropForeign(['sede_id']);
            $table->dropColumn('sede_id');
        });
    }
};
