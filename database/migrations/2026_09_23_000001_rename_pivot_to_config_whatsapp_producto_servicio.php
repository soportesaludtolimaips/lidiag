<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function tableExists(string $table): bool
    {
        return (bool) DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
            [$table]
        );
    }

    private function columnExists(string $table, string $column): bool
    {
        return (bool) DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
            [$table, $column]
        );
    }

    private function fkExists(string $fk): bool
    {
        return (bool) DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ? LIMIT 1',
            [$fk, 'FOREIGN KEY']
        );
    }

    public function up(): void
    {
        $old = 'config_servicio_producto';
        $new = 'config_whatsapp_producto_servicio';

        // 1. Renombrar tabla pivote si aún tiene el nombre viejo.
        if ($this->tableExists($old) && ! $this->tableExists($new)) {
            DB::statement("RENAME TABLE `{$old}` TO `{$new}`");
        }

        if (! $this->tableExists($new)) {
            return;
        }

        // 2. Agregar sede_id (nullable para no romper filas existentes).
        if (! $this->columnExists($new, 'sede_id')) {
            DB::statement("ALTER TABLE `{$new}` ADD COLUMN `sede_id` BIGINT UNSIGNED NULL AFTER `servicio_id`");
        }

        // 3. Backfill: heredar la sede desde el servicio vinculado.
        DB::statement(
            "UPDATE `{$new}` p INNER JOIN `config_servicios` s ON s.`id` = p.`servicio_id` SET p.`sede_id` = s.`sede_id` WHERE p.`sede_id` IS NULL"
        );

        // 4. FK hacia config_sedes + índice.
        $fk = 'config_whatsapp_producto_servicio_sede_id_foreign';
        if (! $this->fkExists($fk)) {
            DB::statement(
                "ALTER TABLE `{$new}` ADD CONSTRAINT `{$fk}` FOREIGN KEY (`sede_id`) REFERENCES `config_sedes` (`id`) ON DELETE CASCADE"
            );
        }
    }

    public function down(): void
    {
        $old = 'config_servicio_producto';
        $new = 'config_whatsapp_producto_servicio';

        if ($this->tableExists($new)) {
            $fk = 'config_whatsapp_producto_servicio_sede_id_foreign';
            if ($this->fkExists($fk)) {
                DB::statement("ALTER TABLE `{$new}` DROP FOREIGN KEY `{$fk}`");
            }
            if ($this->columnExists($new, 'sede_id')) {
                DB::statement("ALTER TABLE `{$new}` DROP COLUMN `sede_id`");
            }
            if (! $this->tableExists($old)) {
                DB::statement("RENAME TABLE `{$new}` TO `{$old}`");
            }
        }
    }
};
