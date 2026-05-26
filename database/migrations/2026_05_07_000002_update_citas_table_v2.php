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

        if (! in_array('paciente_id', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->unsignedBigInteger('paciente_id')->nullable()->after('id');
                $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('set null');
            });
        }

        if (! in_array('servicio_cita_id', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->unsignedBigInteger('servicio_cita_id')->nullable()->after('paciente_id');
                $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas')->onDelete('set null');
            });
        }

        if (! in_array('producto_id', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->unsignedBigInteger('producto_id')->nullable()->after('servicio_cita_id');
                $table->foreign('producto_id')->references('id')->on('config_productos')->onDelete('set null');
            });
        }

        if (! in_array('sede_id', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->unsignedBigInteger('sede_id')->nullable()->after('producto_id');
                $table->foreign('sede_id')->references('id')->on('config_sedes')->onDelete('set null');
            });
        }

        if (! in_array('fecha_hora_inicio', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->dateTime('fecha_hora_inicio')->nullable()->after('sede_id');
                $table->dateTime('fecha_hora_fin')->nullable()->after('fecha_hora_inicio');
                $table->integer('duracion_minutos')->default(30)->after('fecha_hora_fin');
            });
        }

        if (! in_array('estado', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->enum('estado', ['solicitada', 'asignada', 'confirmada', 'cancelada', 'cumplida', 'no_asistio'])
                    ->default('solicitada')->after('duracion_minutos');
            });
        }

        if (! in_array('numero_orden', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->string('numero_orden', 50)->nullable()->after('estado');
                $table->string('numero_autorizacion', 50)->nullable()->after('numero_orden');
                $table->text('observaciones')->nullable()->after('numero_autorizacion');
            });
        }

        if (! in_array('whatsapp_token', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->string('whatsapp_token', 100)->nullable()->after('observaciones');
                $table->string('whatsapp_mid', 100)->nullable()->after('whatsapp_token');
                $table->enum('whatsapp_estado', ['pending', 'sent', 'delivered', 'read', 'failed'])
                    ->default('pending')->after('whatsapp_mid');
            });
        }

        if (! in_array('quien_confirma_id', $columns)) {
            Schema::table('citas', function (Blueprint $table) {
                $table->unsignedBigInteger('quien_confirma_id')->nullable()->after('whatsapp_estado');
                $table->foreign('quien_confirma_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
            $table->dropForeign(['servicio_cita_id']);
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['sede_id']);
            $table->dropForeign(['quien_confirma_id']);

            $table->dropColumn([
                'paciente_id',
                'servicio_cita_id',
                'producto_id',
                'sede_id',
                'fecha_hora_inicio',
                'fecha_hora_fin',
                'duracion_minutos',
                'estado',
                'numero_orden',
                'numero_autorizacion',
                'observaciones',
                'whatsapp_token',
                'whatsapp_mid',
                'whatsapp_estado',
                'quien_confirma_id',
            ]);
        });
    }
};
