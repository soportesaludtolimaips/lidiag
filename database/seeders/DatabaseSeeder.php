<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Database\Seeders\configuracion\ConfigAdminSaludSeeder;
use Database\Seeders\Configuracion\ConfigDiagnosticoSeeder;
use Database\Seeders\configuracion\ConfigPrioridadeSeeder;
use Database\Seeders\Configuracion\ConfigProductoSeeder;
use Database\Seeders\Configuracion\ConfigSedeSeeder;
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

        // HelpDesk
        $this->call(HelpDeskCatalogosSeeder::class);
        $this->call(HelpDeskPermisosSeeder::class);

        $this->call(OtrasInterfacesSeeder::class);
        $this->call(InterfaceTeleRadiologiaSeeder::class);
        $this->call(PermisosAgendamientoSeeder::class);

        // WhatsApp Citas
        $this->call(ConfigServicioCitasSeeder::class);
        $this->call(ConfigSedeWhatsappNumberSeeder::class);
        $this->call(PermisionServicioCitasSeeder::class);
    }
}
