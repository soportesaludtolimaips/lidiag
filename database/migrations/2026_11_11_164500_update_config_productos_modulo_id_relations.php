<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Actualiza la tabla config_productos para relacionar los diferentes productos
     * con sus respectivos módulos asistenciales (Rx, Ecografías, Tomografía, Resonancia, Mamografía, Oftalmología).
     *
     * Flujo del proceso:
     * 1. Verifica la existencia de las tablas config_productos y config_modulos.
     * 2. Asegura la presencia de los registros de modalidades base en config_modulos.
     * 3. Relaciona los productos por códigos CUPS clínicos específicos (ecografías y procedimientos).
     * 4. Asigna los productos restantes a sus módulos mediante patrones y palabras clave clínicas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function up(): void
    {
        if (! Schema::hasTable('config_productos') || ! Schema::hasTable('config_modulos')) {
            return;
        }

        $now = now();
        $modulosBase = ['Rx', 'Ecografias', 'Tomografia', 'Resonancia', 'Mamografia', 'Oftalmologia'];

        foreach ($modulosBase as $moduloNombre) {
            $existe = DB::table('config_modulos')->where('modulo', $moduloNombre)->first();
            if (! $existe) {
                DB::table('config_modulos')->insert([
                    'modulo' => $moduloNombre,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $moduloRx = DB::table('config_modulos')->where('modulo', 'Rx')->value('id');
        $moduloEco = DB::table('config_modulos')->where('modulo', 'Ecografias')->value('id');
        $moduloTomo = DB::table('config_modulos')->where('modulo', 'Tomografia')->value('id');
        $moduloRes = DB::table('config_modulos')->where('modulo', 'Resonancia')->value('id');
        $moduloMamo = DB::table('config_modulos')->where('modulo', 'Mamografia')->value('id');
        $moduloOft = DB::table('config_modulos')->where('modulo', 'Oftalmologia')->value('id');

        // 1. Rayos X (Prioridad alta para términos inequívocos y evitar falsos positivos con palabras como 'senos paranasales' o 'rotación')
        if ($moduloRx) {
            DB::table('config_productos')
                ->where(function ($query) {
                    $query->where('nom_produc', 'LIKE', '%RADIOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%TELERADIOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%ORTOPANTOMOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%FLUOROSCOPIA%')
                        ->orWhere('nom_produc', 'LIKE', '%PORTATIL%')
                        ->orWhere('nom_produc', 'LIKE', '%PORTATILES%')
                        ->orWhere('nom_produc', 'LIKE', '%GASTROVIEW%')
                        ->orWhere('nom_produc', 'LIKE', '%DIATRIZOATO%')
                        ->orWhere('cod_cups', 'LIKE', '870%')
                        ->orWhere('cod_cups', 'LIKE', '871%')
                        ->orWhere('cod_cups', 'LIKE', '872%')
                        ->orWhere('cod_cups', 'LIKE', '873%');
                })
                ->update(['modulo_id' => $moduloRx]);
        }

        // 2. Tomografía
        if ($moduloTomo) {
            DB::table('config_productos')
                ->where(function ($query) {
                    $query->where('nom_produc', 'LIKE', '%TOMOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%ESCANOGRAFIA%')
                        ->orWhere('nom_produc', 'LIKE', '%UROTAG%')
                        ->orWhere('nom_produc', 'LIKE', '%ANGIOTAC%')
                        ->orWhere('nom_produc', 'LIKE', '%IOVERSOL%')
                        ->orWhere('nom_produc', 'LIKE', '%IOPAMIDOL%')
                        ->orWhere('nom_produc', 'LIKE', '%IOBITRIDOL%')
                        ->orWhere('nom_produc', 'LIKE', '%XENETIX%')
                        ->orWhere('nom_produc', 'LIKE', '%OPTIRAY%')
                        ->orWhere('cod_cups', 'LIKE', '879%');
                })
                ->update(['modulo_id' => $moduloTomo]);
        }

        // 3. Resonancia
        if ($moduloRes) {
            DB::table('config_productos')
                ->where(function ($query) {
                    $query->where('nom_produc', 'LIKE', '%RESONANC%')
                        ->orWhere('nom_produc', 'LIKE', '%COLANGIORESONANCIA%')
                        ->orWhere('nom_produc', 'LIKE', '%ANGIORESONANCIA%')
                        ->orWhere('nom_produc', 'LIKE', '%DOTAREM%')
                        ->orWhere('nom_produc', 'LIKE', '%GADOTERICO%')
                        ->orWhere('nom_produc', 'LIKE', '%GADOLINIO%')
                        ->orWhere('cod_cups', 'LIKE', '883%');
                })
                ->update(['modulo_id' => $moduloRes]);
        }

        // 4. Mamografía (evitando colisión con 'senos paranasales')
        if ($moduloMamo) {
            $cupsMamo = ['851101', '851102', '851103', '876801', '876802'];
            DB::table('config_productos')
                ->whereIn('cod_cups', $cupsMamo)
                ->update(['modulo_id' => $moduloMamo]);

            DB::table('config_productos')
                ->where(function ($query) {
                    $query->where('nom_produc', 'LIKE', '%MAMOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%XEROMAMOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%CA DE SENO%')
                        ->orWhere('nom_produc', 'LIKE', '%ESTEREOTAXIA%')
                        ->orWhere('nom_produc', 'LIKE', '%MAMA CON AGUJA%')
                        ->orWhere('nom_produc', 'LIKE', '%AGUJA FINA DE MAMA%')
                        ->orWhere('cod_cups', 'LIKE', '876%');
                })
                ->update(['modulo_id' => $moduloMamo]);
        }

        // 5. Ecografías
        if ($moduloEco) {
            $cupsEco = [
                '881332', '881305', '881302', '881301', '881340', '881390', '881312', '881431',
                '881432', '881435', '881436', '881437', '881438', '881440', '881501', '881601',
                '881602', '881610', '881620', '881621', '881112', '881118', '881121', '881201',
                '061001', '061002', '061302', '345001', '345002', '345401', '542801', '542802',
                '542901', '861101', '881220',
            ];

            DB::table('config_productos')
                ->whereIn('cod_cups', $cupsEco)
                ->update(['modulo_id' => $moduloEco]);

            DB::table('config_productos')
                ->where(function ($query) {
                    $query->where('nom_produc', 'LIKE', '%ECOGRAF%')
                        ->orWhere('nom_produc', 'LIKE', '%ULTRASON%')
                        ->orWhere('nom_produc', 'LIKE', '%DOPPLER%')
                        ->orWhere('nom_produc', 'LIKE', '%DUPLEX SCAN%')
                        ->orWhere('nom_produc', 'LIKE', '%ECOCARDIOG%')
                        ->orWhere('nom_produc', 'LIKE', '%TIROIDES%')
                        ->orWhere('nom_produc', 'LIKE', '%TORACENTESIS%')
                        ->orWhere('nom_produc', 'LIKE', '%PARACENTESIS%')
                        ->orWhere('nom_produc', 'LIKE', '%BIOPSIA DE PLEURA%')
                        ->orWhere('nom_produc', 'LIKE', '%DRENAJE DE COLECCI%')
                        ->orWhere('cod_cups', 'LIKE', '881%')
                        ->orWhere('cod_cups', 'LIKE', '882%');
                })
                ->update(['modulo_id' => $moduloEco]);
        }

        // 6. Oftalmología
        if ($moduloOft) {
            DB::table('config_productos')
                ->where(function ($query) {
                    $query->where('nom_produc', 'LIKE', '%OFTALMO%')
                        ->orWhere('nom_produc', 'LIKE', '%CAMPIMETRIA%')
                        ->orWhere('nom_produc', 'LIKE', '%OCT%')
                        ->orWhere('nom_produc', 'LIKE', '%RETINA%')
                        ->orWhere('nom_produc', 'LIKE', '%FONDO DE OJO%')
                        ->orWhere('nom_produc', 'LIKE', '%PAQUIMETRIA%');
                })
                ->update(['modulo_id' => $moduloOft]);
        }
    }

    /**
     * Revierte la asignación de módulos a los productos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function down(): void
    {
        if (! Schema::hasTable('config_productos')) {
            return;
        }

        DB::table('config_productos')->update(['modulo_id' => null]);
    }
};
