<?php

namespace App\Console\Commands;

use Database\Seeders\ConfigCitaRechazoMotivoSeeder;
use Database\Seeders\Configuracion\ConfigProductosDedupeSeeder;
use Database\Seeders\Configuracion\ConfigWhatsappEcografiasRecomendaciones;
use Database\Seeders\Configuracion\ConfigWhatsappEcografiasRequerimientos;
use Database\Seeders\Configuracion\ConfigWhatsappSedeNumberSeeder;
use Database\Seeders\HelpDesk\HelpDeskCatalogosSeeder;
use Database\Seeders\HelpDesk\HelpDeskPermisosSeeder;
use Database\Seeders\PermisionBackupMonitorSeeder;
use Database\Seeders\PermisionConfigTipoDocumentoSeeder;
use Database\Seeders\PermisionConsentimientoInformadoSeeder;
use Database\Seeders\PermisionConsentimientosEcografiasSeeder;
use Database\Seeders\PermisionContratacionSeeder;
use Database\Seeders\PermisionDosimetriaSeeder;
use Database\Seeders\PermisionEcografiasSeeder;
use Database\Seeders\PermisionLiquidacionSeeder;
use Database\Seeders\PermisionRecomendacionesSeeder;
use Database\Seeders\PermisionServicioCitasSeeder;
use Database\Seeders\PermisionWhatsAppSeeder;
use Database\Seeders\PermisoAuditoriaSeeder;
use Database\Seeders\PermisosAgendamientoSeeder;
use Database\Seeders\RoleSeederAgenda;
use Database\Seeders\RoleSeederReporteLectura;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class SeedFaltantesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "seed:faltantes
                            {--audit : Ejecuta automáticamente permissions:audit al finalizar}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Ejecuta de forma segura e idempotente los seeders de permisos y módulos nuevos sin duplicar datos de producción.";

    /**
     * Lista ordenada de seeders idempotentes a ejecutar.
     *
     * @var array<int, class-string>
     */
    private array $seeders = [
        // Dosimetría y Telemetría Radiológica (Res. 482)
        PermisionDosimetriaSeeder::class,

        // Backups y Monitoreo de Seguridad
        PermisionBackupMonitorSeeder::class,

        // Contratación Médica y Tarifas
        PermisionContratacionSeeder::class,

        // Liquidación de Honorarios Médicos
        PermisionLiquidacionSeeder::class,

        // Recomendaciones y Requerimientos
        PermisionRecomendacionesSeeder::class,

        // Citas, Motivos de Rechazo / Ausentismo y Agenda
        ConfigCitaRechazoMotivoSeeder::class,
        PermisionServicioCitasSeeder::class,
        PermisosAgendamientoSeeder::class,
        RoleSeederAgenda::class,

        // Auditoría
        PermisoAuditoriaSeeder::class,

        // WhatsApp y Parametrización de Sedes
        PermisionWhatsAppSeeder::class,
        ConfigWhatsappSedeNumberSeeder::class,
        ConfigWhatsappEcografiasRequerimientos::class,
        ConfigWhatsappEcografiasRecomendaciones::class,

        // Consentimientos Informados y Ecografías
        PermisionConfigTipoDocumentoSeeder::class,
        PermisionConsentimientoInformadoSeeder::class,
        PermisionConsentimientosEcografiasSeeder::class,
        PermisionEcografiasSeeder::class,
        RoleSeederReporteLectura::class,

        // HelpDesk (Catálogos y Permisos)
        HelpDeskCatalogosSeeder::class,
        HelpDeskPermisosSeeder::class,

        // Deduplicación defensiva de CUPS
        ConfigProductosDedupeSeeder::class,
    ];

    /**
     * Ejecuta secuencialmente los seeders idempotentes y resetea la caché de permisos.
     *
     * Flujo del proceso:
     * 1. Recorre la lista de seeders seguros e idempotentes.
     * 2. Ejecuta cada clase mostrando feedback en tiempo real.
     * 3. Invalida y refresca la caché de permisos de Spatie.
     * 4. Opcionalmente ejecuta la auditoría de permisos para validar consistencia.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function handle(): int
    {
        $this->info("===================================================================");
        $this->info("  LIDiag — Ejecución de Seeders Faltantes / Nuevos (Idempotentes)");
        $this->info("===================================================================");

        $total = count($this->seeders);
        $exitosos = 0;
        $fallidos = 0;

        foreach ($this->seeders as $index => $seederClass) {
            $num = $index + 1;
            $nombreCorto = class_basename($seederClass);

            $this->line("<comment>[{$num}/{$total}]</comment> Ejecutando <info>{$nombreCorto}</info>...");

            try {
                $this->callSilent("db:seed", [
                    "--class" => $seederClass,
                    "--force" => true,
                ]);
                $this->line("      <fg=green>✔ Completado exitosamente.</>");
                $exitosos++;
            } catch (\Throwable $e) {
                $this->error("      ✘ Error en {$nombreCorto}: ".$e->getMessage());
                $fallidos++;
            }
        }

        $this->newLine();
        $this->info("Refrescando cache de permisos de Spatie...");
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->callSilent("permissions:cache-reset");
        $this->line("<fg=green>✔ Cache de permisos reseteado correctamente.</>");

        $this->newLine();
        $this->info("-------------------------------------------------------------------");
        $this->info("Resumen: {$exitosos} completados, {$fallidos} fallidos de {$total} seeders.");
        $this->info("-------------------------------------------------------------------");

        if ($this->option("audit")) {
            $this->newLine();
            $this->info("Ejecutando auditoria de permisos...");
            $this->call("permissions:audit");
        }

        return $fallidos === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
