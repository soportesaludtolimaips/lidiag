<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a las sedes la configuración de la lista de trabajo (worklist).
 *
 * - worklist_activo: si la sede envía las citas al PACS como HL7 ORM.
 * - worklist_puerto: puerto HL7 (MLLP) del dcm4chee; 2575 en 2.x y 5.x.
 * - worklist_aet: aplicación receptora (MSH-5/MSH-6); DCM4CHEE por defecto.
 * - worklist_estacion_aet: AET del equipo (ORC-18), opcional.
 *
 * El host es el mismo ip_dcm que ya usa la sede para el PACS.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-10-11
 */
return new class extends Migration
{
    /**
     * Crea las columnas de configuración de la lista de trabajo.
     */
    public function up(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            $table->boolean('worklist_activo')->default(false)->after('puerto_dcm');
            $table->unsignedInteger('worklist_puerto')->nullable()->default(2575)->after('worklist_activo');
            $table->string('worklist_aet', 16)->nullable()->after('worklist_puerto');
            $table->string('worklist_estacion_aet', 16)->nullable()->after('worklist_aet');
        });
    }

    /**
     * Elimina las columnas de configuración de la lista de trabajo.
     */
    public function down(): void
    {
        Schema::table('config_sedes', function (Blueprint $table) {
            $table->dropColumn(['worklist_activo', 'worklist_puerto', 'worklist_aet', 'worklist_estacion_aet']);
        });
    }
};
