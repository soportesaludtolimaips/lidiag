<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombra config_servicio_citas -> config_servicios,
     * las pivots y las columnas servicio_cita_id -> servicio_id.
     *
     * Idempotente: cada drop verifica existencia en INFORMATION_SCHEMA,
     * porque MySQL no usa transacciones en DDL y una ejecución parcial
     * previa no debe tumbar la re-ejecución. Además las uniques de
     * user_servicio_citas y config_whatsapp respaldan las FKs de
     * user_id/sede_id, por lo que esas FKs se sueltan primero.
     */
    public function up(): void
    {
        // Si la BD ya viene con los nombres nuevos (migrate:fresh), no hay nada que transicionar
        if (! Schema::hasTable('config_servicio_citas')) {
            return;
        }

        // 1. Soltar FKs (incluye user_id/sede_id cuyas uniques las respaldan)
        $this->dropForeignIfExists('config_servicio_cita_producto', 'servicio_cita_id');
        $this->dropForeignIfExists('user_servicio_citas', 'servicio_cita_id');
        $this->dropForeignIfExists('user_servicio_citas', 'user_id');
        $this->dropForeignIfExists('citas', 'servicio_cita_id');
        $this->dropForeignIfExists('agenda_citas', 'servicio_cita_id');
        $this->dropForeignIfExists('agenda_disponibilidad', 'servicio_cita_id');
        $this->dropForeignIfExists('config_productos', 'servicio_id');
        $this->dropForeignIfExists('config_whatsapp', 'servicio_cita_id');
        $this->dropForeignIfExists('config_whatsapp', 'sede_id');

        // 2. Soltar uniques e índices que incluyen columnas a renombrar
        $this->dropUniqueIfExists('user_servicio_citas', ['user_id', 'servicio_cita_id']);
        $this->dropUniqueIfExists('config_whatsapp', ['sede_id', 'servicio_cita_id']);
        $this->dropIndexIfExists('agenda_disponibilidad', 'agenda_disponibilidad_servicio_cita_id_index');

        // 3. Soltar índices huérfanos que deja DROP FOREIGN KEY (MySQL no los borra)
        $this->dropIndexIfExists('config_servicio_cita_producto', 'config_servicio_cita_producto_servicio_cita_id_foreign');
        $this->dropIndexIfExists('user_servicio_citas', 'user_servicio_citas_servicio_cita_id_foreign');
        $this->dropIndexIfExists('citas', 'citas_servicio_cita_id_foreign');
        $this->dropIndexIfExists('agenda_citas', 'agenda_citas_servicio_cita_id_foreign');
        $this->dropIndexIfExists('config_productos', 'config_productos_servicio_id_foreign');
        $this->dropIndexIfExists('config_whatsapp', 'config_whatsapp_servicio_cita_id_foreign');

        // 4. Renombrar tablas
        Schema::rename('config_servicio_citas', 'config_servicios');
        Schema::rename('config_servicio_cita_producto', 'config_servicio_producto');
        Schema::rename('user_servicio_citas', 'user_servicios');

        // 5. Renombrar columnas (SQL directo, sin doctrine/dbal)
        DB::statement('ALTER TABLE `config_servicio_producto` RENAME COLUMN `servicio_cita_id` TO `servicio_id`');
        DB::statement('ALTER TABLE `user_servicios` RENAME COLUMN `servicio_cita_id` TO `servicio_id`');
        DB::statement('ALTER TABLE `citas` RENAME COLUMN `servicio_cita_id` TO `servicio_id`');
        DB::statement('ALTER TABLE `agenda_citas` RENAME COLUMN `servicio_cita_id` TO `servicio_id`');
        DB::statement('ALTER TABLE `agenda_disponibilidad` RENAME COLUMN `servicio_cita_id` TO `servicio_id`');
        DB::statement('ALTER TABLE `config_whatsapp` RENAME COLUMN `servicio_cita_id` TO `servicio_id`');

        // 6. Recrear uniques e índice
        Schema::table('user_servicios', function (Blueprint $table) {
            $table->unique(['user_id', 'servicio_id']);
        });
        Schema::table('config_whatsapp', function (Blueprint $table) {
            $table->unique(['sede_id', 'servicio_id']);
        });
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->index('servicio_id');
        });

        // 7. Recrear FKs hacia config_servicios (más las de user_id/sede_id que se soltaron)
        Schema::table('config_servicio_producto', function (Blueprint $table) {
            $table->foreign('servicio_id')->references('id')->on('config_servicios')->onDelete('cascade');
        });
        Schema::table('user_servicios', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('servicio_id')->references('id')->on('config_servicios');
        });
        Schema::table('citas', function (Blueprint $table) {
            $table->foreign('servicio_id')->references('id')->on('config_servicios')->onDelete('set null');
        });
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->foreign('servicio_id')->references('id')->on('config_servicios');
        });
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->foreign('servicio_id')->references('id')->on('config_servicios');
        });
        Schema::table('config_productos', function (Blueprint $table) {
            $table->foreign('servicio_id')->references('id')->on('config_servicios')->onDelete('set null');
        });
        Schema::table('config_whatsapp', function (Blueprint $table) {
            $table->foreign('sede_id')->references('id')->on('config_sedes')->cascadeOnDelete();
            $table->foreign('servicio_id')->references('id')->on('config_servicios')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('config_servicios')) {
            return;
        }

        $this->dropForeignIfExists('config_servicio_producto', 'servicio_id');
        $this->dropForeignIfExists('user_servicios', 'servicio_id');
        $this->dropForeignIfExists('user_servicios', 'user_id');
        $this->dropForeignIfExists('citas', 'servicio_id');
        $this->dropForeignIfExists('agenda_citas', 'servicio_id');
        $this->dropForeignIfExists('agenda_disponibilidad', 'servicio_id');
        $this->dropForeignIfExists('config_productos', 'servicio_id');
        $this->dropForeignIfExists('config_whatsapp', 'servicio_id');
        $this->dropForeignIfExists('config_whatsapp', 'sede_id');

        $this->dropUniqueIfExists('user_servicios', ['user_id', 'servicio_id']);
        $this->dropUniqueIfExists('config_whatsapp', ['sede_id', 'servicio_id']);
        $this->dropIndexIfExists('agenda_disponibilidad', 'agenda_disponibilidad_servicio_id_index');

        $this->dropIndexIfExists('config_servicio_producto', 'config_servicio_producto_servicio_id_foreign');
        $this->dropIndexIfExists('user_servicios', 'user_servicios_servicio_id_foreign');
        $this->dropIndexIfExists('user_servicios', 'user_servicios_user_id_foreign');
        $this->dropIndexIfExists('citas', 'citas_servicio_id_foreign');
        $this->dropIndexIfExists('agenda_citas', 'agenda_citas_servicio_id_foreign');
        $this->dropIndexIfExists('config_productos', 'config_productos_servicio_id_foreign');
        $this->dropIndexIfExists('config_whatsapp', 'config_whatsapp_servicio_id_foreign');
        $this->dropIndexIfExists('config_whatsapp', 'config_whatsapp_sede_id_foreign');

        DB::statement('ALTER TABLE `config_servicio_producto` RENAME COLUMN `servicio_id` TO `servicio_cita_id`');
        DB::statement('ALTER TABLE `user_servicios` RENAME COLUMN `servicio_id` TO `servicio_cita_id`');
        DB::statement('ALTER TABLE `citas` RENAME COLUMN `servicio_id` TO `servicio_cita_id`');
        DB::statement('ALTER TABLE `agenda_citas` RENAME COLUMN `servicio_id` TO `servicio_cita_id`');
        DB::statement('ALTER TABLE `agenda_disponibilidad` RENAME COLUMN `servicio_id` TO `servicio_cita_id`');
        DB::statement('ALTER TABLE `config_whatsapp` RENAME COLUMN `servicio_id` TO `servicio_cita_id`');

        Schema::rename('config_servicios', 'config_servicio_citas');
        Schema::rename('config_servicio_producto', 'config_servicio_cita_producto');
        Schema::rename('user_servicios', 'user_servicio_citas');

        Schema::table('user_servicio_citas', function (Blueprint $table) {
            $table->unique(['user_id', 'servicio_cita_id']);
        });
        Schema::table('config_whatsapp', function (Blueprint $table) {
            $table->unique(['sede_id', 'servicio_cita_id']);
        });
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->index('servicio_cita_id');
        });

        Schema::table('config_servicio_cita_producto', function (Blueprint $table) {
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas')->onDelete('cascade');
        });
        Schema::table('user_servicio_citas', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas');
        });
        Schema::table('citas', function (Blueprint $table) {
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas')->onDelete('set null');
        });
        Schema::table('agenda_citas', function (Blueprint $table) {
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas');
        });
        Schema::table('agenda_disponibilidad', function (Blueprint $table) {
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas');
        });
        Schema::table('config_productos', function (Blueprint $table) {
            $table->foreign('servicio_id')->references('id')->on('config_servicio_citas')->onDelete('set null');
        });
        Schema::table('config_whatsapp', function (Blueprint $table) {
            $table->foreign('sede_id')->references('id')->on('config_sedes')->cascadeOnDelete();
            $table->foreign('servicio_cita_id')->references('id')->on('config_servicio_citas')->cascadeOnDelete();
        });
    }

    private function dropForeignIfExists(string $table, string $column): void
    {
        $name = "{$table}_{$column}_foreign";
        $exists = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$table, $name]
        );
        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$name}`");
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (bool) DB::selectOne(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$table, $index]
        );
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }

    private function dropUniqueIfExists(string $table, array $columns): void
    {
        $this->dropIndexIfExists($table, $table.'_'.implode('_', $columns).'_unique');
    }
};
