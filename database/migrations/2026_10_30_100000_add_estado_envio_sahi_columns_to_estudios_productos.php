<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columnas de tracking para interface SAHI en `estudios_productos`.
     *
     * Columnas:
     * - estado_envio_sahi: PENDIENTE | ENVIANDO | ENVIADO | ERROR (nullable para legacy)
     * - fechor_envio_sahi: fecha/hora del último intento/envío
     * - sahi_esquema_149 / sahi_esquema_150: IDs generados en SAHI (encabezados)
     * - error_envio_sahi: mensaje del último error
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    public function up(): void
    {
        if (! Schema::hasTable('estudios_productos')) {
            return;
        }

        $needsAlter = false;
        foreach (['estado_envio_sahi', 'fechor_envio_sahi', 'sahi_esquema_149', 'sahi_esquema_150', 'error_envio_sahi'] as $col) {
            if (! Schema::hasColumn('estudios_productos', $col)) {
                $needsAlter = true;
                break;
            }
        }

        if (! $needsAlter) {
            return;
        }

        Schema::table('estudios_productos', function (Blueprint $table) {
            if (! Schema::hasColumn('estudios_productos', 'estado_envio_sahi')) {
                $table->string('estado_envio_sahi', 20)->nullable()->after('fechor_trascrito')->index();
            }
            if (! Schema::hasColumn('estudios_productos', 'fechor_envio_sahi')) {
                $table->dateTime('fechor_envio_sahi')->nullable()->after('estado_envio_sahi');
            }
            if (! Schema::hasColumn('estudios_productos', 'sahi_esquema_149')) {
                $table->unsignedBigInteger('sahi_esquema_149')->nullable()->after('fechor_envio_sahi');
            }
            if (! Schema::hasColumn('estudios_productos', 'sahi_esquema_150')) {
                $table->unsignedBigInteger('sahi_esquema_150')->nullable()->after('sahi_esquema_149');
            }
            if (! Schema::hasColumn('estudios_productos', 'error_envio_sahi')) {
                $table->text('error_envio_sahi')->nullable()->after('sahi_esquema_150');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('estudios_productos')) {
            return;
        }

        $columnsToDrop = [];
        foreach (['error_envio_sahi', 'sahi_esquema_150', 'sahi_esquema_149', 'fechor_envio_sahi', 'estado_envio_sahi'] as $col) {
            if (Schema::hasColumn('estudios_productos', $col)) {
                $columnsToDrop[] = $col;
            }
        }

        if (! empty($columnsToDrop)) {
            Schema::table('estudios_productos', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
