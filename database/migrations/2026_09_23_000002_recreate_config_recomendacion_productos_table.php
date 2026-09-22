<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recrea la tabla vieja de recomendaciones por producto si falta.
     * La migración original (2026_05_06_124749) figura como ejecutada pero la
     * tabla no existe en la BD; sin ella fallan los endpoints de
     * config-recomendacion-productos y el eager load recomendacionProducto.
     *
     * Nota: el FK usa nombre explícito porque el nombre convencional ya está
     * ocupado por la tabla config_whatsapp_recomendaciones.
     */
    private function tableExists(): bool
    {
        return (bool) DB::select(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
            ['config_recomendacion_productos']
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
        if (! $this->tableExists()) {
            Schema::create('config_recomendacion_productos', function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('producto_id');
                $table->text('recomendacion')->nullable()->comment('Recomendaciones del procedimiento a realizar al paciente');
                $table->boolean('estado')->default(true)->comment('Indica si la recomendación está activa o no');
                $table->timestamps();
            });
        }

        $fk = 'config_recomendacion_productos_producto_id_fk';
        if (! $this->fkExists($fk)) {
            DB::statement(
                "ALTER TABLE `config_recomendacion_productos` ADD CONSTRAINT `{$fk}` FOREIGN KEY (`producto_id`) REFERENCES `config_productos` (`id`)"
            );
        }
    }

    public function down(): void
    {
        $fk = 'config_recomendacion_productos_producto_id_fk';
        if ($this->tableExists() && $this->fkExists($fk)) {
            DB::statement("ALTER TABLE `config_recomendacion_productos` DROP FOREIGN KEY `{$fk}`");
        }
        Schema::dropIfExists('config_recomendacion_productos');
    }
};
