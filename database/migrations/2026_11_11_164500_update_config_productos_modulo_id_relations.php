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

        // 1. Relacionar productos de ecografías por código CUPS específico
        if ($moduloEco) {
            $cupsEcografias = [
                '881332', '881305', '881302', '881301', '881340', '881390', '881312', '881431',
                '881432', '881435', '881436', '881437', '881438', '881440', '881501', '881601',
                '881602', '881610', '881620', '881621', '881112', '881118', '881121', '881201',
            ];

            DB::table('config_productos')
                ->whereIn('cod_cups', $cupsEcografias)
                ->update(['modulo_id' => $moduloEco]);

            $keywordsEco = ['ECOGRAF', 'ULTRASON', 'DOPPLER', 'ECOCARDIOGRAMA'];
            foreach ($keywordsEco as $kw) {
                DB::table('config_productos')
                    ->where(function ($query) use ($kw) {
                        $query->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloEco]);
            }
        }

        // 2. Mamografía
        if ($moduloMamo) {
            $keywordsMamo = ['MAMOGRAF', 'XEROMAMOGRAFIA', 'SENO'];
            foreach ($keywordsMamo as $kw) {
                DB::table('config_productos')
                    ->where(function ($query) use ($kw) {
                        $query->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloMamo]);
            }
        }

        // 3. Tomografía
        if ($moduloTomo) {
            $keywordsTomo = ['TOMOGRAF', 'TAC', 'UROTAG', 'ANGIOTAC'];
            foreach ($keywordsTomo as $kw) {
                DB::table('config_productos')
                    ->where(function ($query) use ($kw) {
                        $query->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloTomo]);
            }
        }

        // 4. Resonancia
        if ($moduloRes) {
            $keywordsRes = ['RESONANC', 'RMN', 'COLANGIORESONANCIA'];
            foreach ($keywordsRes as $kw) {
                DB::table('config_productos')
                    ->where(function ($query) use ($kw) {
                        $query->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloRes]);
            }
        }

        // 5. Rayos X
        if ($moduloRx) {
            $keywordsRx = ['RADIOGRAF', 'RAYOS', 'FLUOROSCOPIA', ' RX ', 'TELERADIOGRAFIA'];
            foreach ($keywordsRx as $kw) {
                DB::table('config_productos')
                    ->where(function ($query) use ($kw) {
                        $query->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloRx]);
            }
        }

        // 6. Oftalmología
        if ($moduloOft) {
            $keywordsOft = ['OFTALMO', 'CAMPIMETRIA', 'OCT', 'RETINA'];
            foreach ($keywordsOft as $kw) {
                DB::table('config_productos')
                    ->where(function ($query) use ($kw) {
                        $query->where('nom_produc', 'LIKE', "%{$kw}%")
                            ->orWhere('cod_cups', 'LIKE', "%{$kw}%");
                    })
                    ->update(['modulo_id' => $moduloOft]);
            }
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
