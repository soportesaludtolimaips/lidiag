<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('config_cita_rechazo_motivos')) {
            Schema::create('config_cita_rechazo_motivos', function (Blueprint $table) {
                $table->id();
                $table->string('motivo', 255);
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        $columns = DB::getSchemaBuilder()->getColumnListing('citas');

        if (! in_array('revisada', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->boolean('revisada')->default(false)->after('whatsapp_estado');
            });
        }

        if (! in_array('rechazo_motivo_id', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->unsignedBigInteger('rechazo_motivo_id')->nullable()->after('revisada');
                $table->foreign('rechazo_motivo_id')->references('id')->on('config_cita_rechazo_motivos')->onDelete('set null');
            });
        }

        if (! in_array('rechazo_observacion', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->text('rechazo_observacion')->nullable()->after('rechazo_motivo_id');
            });
        }

        DB::statement("ALTER TABLE citas MODIFY COLUMN estado ENUM('solicitada', 'asignada', 'confirmada', 'cancelada', 'cumplida', 'no_asistio', 'rechazada') DEFAULT 'solicitada'");
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['rechazo_motivo_id']);
            $table->dropColumn(['rechazo_observacion', 'rechazo_motivo_id', 'revisada']);
        });

        DB::statement("ALTER TABLE citas MODIFY COLUMN estado ENUM('solicitada', 'asignada', 'confirmada', 'cancelada', 'cumplida', 'no_asistio') DEFAULT 'solicitada'");

        Schema::dropIfExists('config_cita_rechazo_motivos');
    }
};
