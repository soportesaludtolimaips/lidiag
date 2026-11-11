<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombra la tabla config_sede_whatsapp_numbers a config_whatsapp_numbers.
     *
     * Flujo del proceso:
     * 1. Verifica de forma idempotente que config_sede_whatsapp_numbers exista y config_whatsapp_numbers no exista.
     * 2. Renombra la tabla en MySQL preservando llaves foráneas, índices y registros.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function up(): void
    {
        if (Schema::hasTable('config_sede_whatsapp_numbers') && ! Schema::hasTable('config_whatsapp_numbers')) {
            DB::statement('RENAME TABLE `config_sede_whatsapp_numbers` TO `config_whatsapp_numbers`');
        }
    }

    /**
     * Revierte el renombramiento de la tabla config_whatsapp_numbers a config_sede_whatsapp_numbers.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function down(): void
    {
        if (Schema::hasTable('config_whatsapp_numbers') && ! Schema::hasTable('config_sede_whatsapp_numbers')) {
            DB::statement('RENAME TABLE `config_whatsapp_numbers` TO `config_sede_whatsapp_numbers`');
        }
    }
};
