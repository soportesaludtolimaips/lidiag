<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->boolean('recordatorio_24h_enviado')->default(false);
            $table->boolean('recordatorio_2h_enviado')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropColumn(['recordatorio_24h_enviado', 'recordatorio_2h_enviado']);
        });
    }
};
