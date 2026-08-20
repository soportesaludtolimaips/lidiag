<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = DB::getSchemaBuilder()->getColumnListing('agenda_citas');

        $toAdd = [
            'paciente_id' => fn ($t) => $t->unsignedBigInteger('paciente_id')->nullable()->after('quien_registro_id'),
            'producto_id' => fn ($t) => $t->unsignedBigInteger('producto_id')->nullable()->after('servicio_cita_id'),
            'quien_confirma_id' => fn ($t) => $t->unsignedBigInteger('quien_confirma_id')->nullable()->after('quien_registro_id'),
            'fecha_hora_inicio' => fn ($t) => $t->dateTime('fecha_hora_inicio')->nullable()->after('servicio_cita_id'),
            'duracion_minutos' => fn ($t) => $t->integer('duracion_minutos')->default(30)->nullable()->after('fecha_hora_fin'),
            'numero_orden' => fn ($t) => $t->string('numero_orden', 50)->nullable()->after('estado'),
            'numero_autorizacion' => fn ($t) => $t->string('numero_autorizacion', 50)->nullable()->after('numero_orden'),
            'ruta_orden_media' => fn ($t) => $t->text('ruta_orden_media')->nullable()->after('numero_autorizacion'),
            'ruta_cedula_frente_media' => fn ($t) => $t->text('ruta_cedula_frente_media')->nullable()->after('ruta_orden_media'),
            'ruta_cedula_dorso_media' => fn ($t) => $t->text('ruta_cedula_dorso_media')->nullable()->after('ruta_cedula_frente_media'),
            'ruta_autorizacion_media' => fn ($t) => $t->text('ruta_autorizacion_media')->nullable()->after('ruta_cedula_dorso_media'),
            'observaciones' => fn ($t) => $t->text('observaciones')->nullable()->after('ruta_autorizacion_media'),
            'whatsapp_token' => fn ($t) => $t->string('whatsapp_token', 100)->nullable()->after('observaciones'),
            'whatsapp_mid' => fn ($t) => $t->string('whatsapp_mid', 100)->nullable()->after('whatsapp_token'),
            'whatsapp_estado' => fn ($t) => $t->enum('whatsapp_estado', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending')->after('whatsapp_mid'),
            'revisada' => fn ($t) => $t->boolean('revisada')->default(false)->after('whatsapp_estado'),
            'rechazo_motivo_id' => fn ($t) => $t->unsignedBigInteger('rechazo_motivo_id')->nullable()->after('revisada'),
            'rechazo_observacion' => fn ($t) => $t->text('rechazo_observacion')->nullable()->after('rechazo_motivo_id'),
        ];

        Schema::table('agenda_citas', function (Blueprint $table) use ($columns, $toAdd) {
            foreach ($toAdd as $col => $addFn) {
                if (! in_array($col, $columns)) {
                    $addFn($table);
                }
            }
        });

        // Cambiar el enum de estado para incluir los valores de citas
        $estadoCol = collect(DB::select("SHOW COLUMNS FROM agenda_citas WHERE Field = 'estado'"))->first();
        if ($estadoCol && ! str_contains($estadoCol->Type, "'solicitada'")) {
            DB::statement("ALTER TABLE agenda_citas MODIFY COLUMN estado ENUM('solicitada','asignada','confirmada','cancelada','cumplida','no_asistio','Activo','Inactivo','Completado','Pendiente') NOT NULL DEFAULT 'solicitada'");
        }

        // Foreign keys (idempotente)
        $fks = collect(DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'agenda_citas'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->pluck('CONSTRAINT_NAME')->toArray();

        if (! in_array('agenda_citas_paciente_id_foreign', $fks)) {
            Schema::table('agenda_citas', function (Blueprint $table) {
                $table->foreign('paciente_id')->references('id')->on('pacientes')->onDelete('set null');
            });
        }
        if (! in_array('agenda_citas_producto_id_foreign', $fks)) {
            Schema::table('agenda_citas', function (Blueprint $table) {
                $table->foreign('producto_id')->references('id')->on('config_productos')->onDelete('set null');
            });
        }
        if (! in_array('agenda_citas_quien_confirma_id_foreign', $fks)) {
            Schema::table('agenda_citas', function (Blueprint $table) {
                $table->foreign('quien_confirma_id')->references('id')->on('users')->onDelete('set null');
            });
        }
        if (! in_array('agenda_citas_rechazo_motivo_id_foreign', $fks)) {
            Schema::table('agenda_citas', function (Blueprint $table) {
                $table->foreign('rechazo_motivo_id')->references('id')->on('config_cita_rechazo_motivos')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->dropForeign(['paciente_id']);
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['quien_confirma_id']);
            $table->dropForeign(['rechazo_motivo_id']);

            $table->dropColumn([
                'paciente_id',
                'producto_id',
                'quien_confirma_id',
                'fecha_hora_inicio',
                'duracion_minutos',
                'numero_orden',
                'numero_autorizacion',
                'ruta_orden_media',
                'ruta_cedula_frente_media',
                'ruta_cedula_dorso_media',
                'ruta_autorizacion_media',
                'observaciones',
                'whatsapp_token',
                'whatsapp_mid',
                'whatsapp_estado',
                'revisada',
                'rechazo_motivo_id',
                'rechazo_observacion',
            ]);
        });
    }
};
