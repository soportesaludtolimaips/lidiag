<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('citas');

        if (! in_array('ruta_orden_media', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->text('ruta_orden_media')->nullable()->after('numero_autorizacion');
                $table->text('ruta_autorizacion_media')->nullable()->after('ruta_orden_media');
            });
        }
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn(['ruta_orden_media', 'ruta_autorizacion_media']);
        });
    }
};
