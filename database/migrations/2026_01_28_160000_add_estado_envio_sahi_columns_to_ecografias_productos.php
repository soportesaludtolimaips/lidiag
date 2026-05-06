<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega columnas de tracking para interface SAHI en `ecografias_productos`.
     *
     * Columnas:
     * - estado_envio_sahi: ENVIANDO | ENVIADO | ERROR (nullable para legacy)
     * - fechor_envio_sahi: fecha/hora del último intento/envío
     * - sahi_esquema_149 / sahi_esquema_150: IDs generados en SAHI (encabezados)
     * - error_envio_sahi: mensaje del último error
     */
    public function up(): void
    {
        if (! Schema::hasTable('ecografias_productos')) {
            return;
        }

        $needsAlter = false;
        foreach (['estado_envio_sahi', 'fechor_envio_sahi', 'sahi_esquema_149', 'sahi_esquema_150', 'error_envio_sahi'] as $col) {
            if (! Schema::hasColumn('ecografias_productos', $col)) {
                $needsAlter = true;
                break;
            }
        }

        if (! $needsAlter) {
            return;
        }

        Schema::table('ecografias_productos', function (Blueprint $table) {
            if (! Schema::hasColumn('ecografias_productos', 'estado_envio_sahi')) {
                $table->string('estado_envio_sahi', 20)->nullable()->after('fechor_lectura')->index();
            }
            if (! Schema::hasColumn('ecografias_productos', 'fechor_envio_sahi')) {
                $table->dateTime('fechor_envio_sahi')->nullable()->after('estado_envio_sahi');
            }
            if (! Schema::hasColumn('ecografias_productos', 'sahi_esquema_149')) {
                $table->unsignedBigInteger('sahi_esquema_149')->nullable()->after('fechor_envio_sahi');
            }
            if (! Schema::hasColumn('ecografias_productos', 'sahi_esquema_150')) {
                $table->unsignedBigInteger('sahi_esquema_150')->nullable()->after('sahi_esquema_149');
            }
            if (! Schema::hasColumn('ecografias_productos', 'error_envio_sahi')) {
                $table->text('error_envio_sahi')->nullable()->after('sahi_esquema_150');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ecografias_productos')) {
            return;
        }

        Schema::table('ecografias_productos', function (Blueprint $table) {
            if (Schema::hasColumn('ecografias_productos', 'error_envio_sahi')) {
                $table->dropColumn('error_envio_sahi');
            }
            if (Schema::hasColumn('ecografias_productos', 'sahi_esquema_150')) {
                $table->dropColumn('sahi_esquema_150');
            }
            if (Schema::hasColumn('ecografias_productos', 'sahi_esquema_149')) {
                $table->dropColumn('sahi_esquema_149');
            }
            if (Schema::hasColumn('ecografias_productos', 'fechor_envio_sahi')) {
                $table->dropColumn('fechor_envio_sahi');
            }
            if (Schema::hasColumn('ecografias_productos', 'estado_envio_sahi')) {
                // dropIndex por nombre automático puede variar; intentamos el default (col name)
                try {
                    $table->dropIndex(['estado_envio_sahi']);
                } catch (Throwable $e) {
                }
                $table->dropColumn('estado_envio_sahi');
            }
        });
    }
};
