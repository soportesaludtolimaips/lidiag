<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\configuracion\ConfigAdminSaludSeeder;
use Database\Seeders\configuracion\ConfigDiagnosticoSeeder;
use Database\Seeders\configuracion\ConfigPrioridadeSeeder;
use Database\Seeders\configuracion\ConfigProductosEcografias1Seeder;
use Database\Seeders\configuracion\ConfigProductosEcografias2Seeder;
use Database\Seeders\configuracion\ConfigProductosEcografias3Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias4Seeder;
use Database\Seeders\Configuracion\ConfigProductoSeeder;
use Database\Seeders\configuracion\ConfigSedeSeeder;
use Database\Seeders\Configuracion\ConfigWhatsappEcografiasRecomendaciones;
use Database\Seeders\Configuracion\ConfigWhatsappEcografiasRequerimientos;
use Database\Seeders\Configuracion\ConfigWhatsappSedeNumberSeeder;
use Database\Seeders\HelpDesk\HelpDeskCatalogosSeeder;
use Database\Seeders\HelpDesk\HelpDeskPermisosSeeder;
use Database\Seeders\Seguridad\RoleSeeder;
use Database\Seeders\Seguridad\UserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Configuracion
        $this->call(ConfigAdminSaludSeeder::class);
        $this->call(ConfigDiagnosticoSeeder::class);
        $this->call(ConfigPrioridadeSeeder::class);
        $this->call(ConfigProductoSeeder::class);
        $this->call(ConfigSedeSeeder::class);
        $this->call(PermisionConsentimientoInformadoSeeder::class);
        $this->call(PermisionConfigTipoDocumentoSeeder::class);
        $this->call(TipoDocumentoSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PlantillaSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(RoleSeederReporteLectura::class);
        $this->call(RoleSeederAgenda::class);
        $this->call(ModulosSeeder::class);
        $this->call(PermisionEcografiasSeeder::class);
        $this->call(PermisionConsentimientosEcografiasSeeder::class);

        $this->call(ConfigServiciosSeeder::class);

        // HelpDesk
        $this->call(HelpDeskCatalogosSeeder::class);
        $this->call(HelpDeskPermisosSeeder::class);

        $this->call(InterfaceTeleRadiologiaSeeder::class);
        $this->call(PermisosAgendamientoSeeder::class);

        // WhatsApp Citas
        $this->call(PermisionServicioCitasSeeder::class);
        $this->call(PermisoAuditoriaSeeder::class);
        $this->call(ConfigWhatsappSeeder::class);
        $this->call(ConfigRecomendacionProductoSeeder::class);

        // Contratación
        $this->call(PermisionContratacionSeeder::class);

        // Liquidación de pagos a médicos
        $this->call(PermisionLiquidacionSeeder::class);

        // Nuevos productos de ecografías
        $this->call(ConfigProductosEcografiasSeeder::class);
        $this->call(ConfigProductosEcografias1Seeder::class);
        $this->call(ConfigProductosEcografias2Seeder::class);
        $this->call(ConfigProductosEcografias3Seeder::class);
        $this->call(ConfigProductosEcografias4Seeder::class);

        // Bot whatsapp
        $this->call(ConfigWhatsappSedeNumberSeeder::class);
        $this->call(PermisionWhatsAppSeeder::class);

        $this->call(ConfigWhatsappEcografiasRequerimientos::class);
        $this->call(ConfigWhatsappEcografiasRecomendaciones::class);
    }
}
