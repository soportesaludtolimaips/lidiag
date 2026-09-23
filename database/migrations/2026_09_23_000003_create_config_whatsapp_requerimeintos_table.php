<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Crea la tabla plural con el esquema nuevo (sede/servicio/producto).
     * Si ya existe con el esquema viejo, solo agrega las columnas faltantes
     * para no perder datos. También elimina la tabla singular
     * (config_whatsapp_requerimeinto) si está vacía, pues ningún código la usa.
     */
    public function up(): void
    {
        $table = 'config_whatsapp_requerimeintos';

        if (! $this->tableExists($table)) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sede_id')->nullable();
                $table->foreign('sede_id')->references('id')->on('config_sedes');
                $table->unsignedBigInteger('servicio_id')->nullable();
                $table->foreign('servicio_id')->references('id')->on('config_servicios');
                $table->unsignedBigInteger('producto_id');
                $table->foreign('producto_id')->references('id')->on('config_productos');
                $table->text('recomendacion');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table($table, function (Blueprint $table) {
                if (! $this->columnExists('config_whatsapp_requerimeintos', 'sede_id')) {
                    $table->unsignedBigInteger('sede_id')->nullable()->after('id');
                    $table->foreign('sede_id')->references('id')->on('config_sedes');
                }
                if (! $this->columnExists('config_whatsapp_requerimeintos', 'servicio_id')) {
                    $table->unsignedBigInteger('servicio_id')->nullable()->after('sede_id');
                    $table->foreign('servicio_id')->references('id')->on('config_servicios');
                }
            });
        }

        $singular = 'config_whatsapp_requerimeinto';
        if ($this->tableExists($singular) && DB::table($singular)->count() === 0) {
            Schema::drop($singular);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('config_whatsapp_requerimeintos');
    }
};
