<?php

namespace App\Console\Commands;

use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigServicio;
use Database\Seeders\ConfigCitaRechazoMotivoSeeder;
use Database\Seeders\ConfigServiciosSeeder;
use Database\Seeders\Configuracion\ConfigProductosDedupeSeeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias1Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias2Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias3Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias4Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografiasSeeder;
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
use Database\Seeders\PlantillaTipoSeeder;
use Database\Seeders\RoleSeederAgenda;
use Database\Seeders\RoleSeederReporteLectura;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class SeedFaltantesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "seed:faltantes
                            {--audit : Ejecuta automaticamente permissions:audit al finalizar}
                            {--force : Fuerza la ejecucion de todos los seeders omitiendo la verificacion previa}";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Verifica y ejecuta de forma segura e inteligente solo los seeders faltantes sin duplicar datos.";

    /**
     * Definición de seeders con su lógica de verificación de aplicación previa.
     *
     * @return array<int, array{class: class-string, check: callable(): bool, descripcion: string}>
     */
    private function getSeedersDefinitions(): array
    {
        return [
            // 1. Servicios Base (prerrequisito de llaves foráneas para WhatsApp y Citas)
            [
                "class" => ConfigServiciosSeeder::class,
                "descripcion" => "Servicios hospitalarios base (Tomografia, Ecografia, RX, etc.)",
                "check" => fn () => ConfigServicio::query()->exists(),
            ],

            // 2. Dosimetría y Telemetría Radiológica (Res. 482 de 2018)
            [
                "class" => PermisionDosimetriaSeeder::class,
                "descripcion" => "Permisos de Dosimetria y umbrales NRD nacionales",
                "check" => fn () => Permission::where("name", "dosimetria.dashboard")->exists()
                    && DB::table("medicion_dosis_umbrales_nrd")->exists(),
            ],

            // 3. Backups y Monitoreo de Seguridad
            [
                "class" => PermisionBackupMonitorSeeder::class,
                "descripcion" => "Permisos de monitoreo y ejecucion de backups",
                "check" => fn () => Permission::where("name", "seguridad.backups.index")->exists(),
            ],

            // 4. Contratación Médica
            [
                "class" => PermisionContratacionSeeder::class,
                "descripcion" => "Permisos de contratos y tarifas medicas",
                "check" => fn () => Permission::where("name", "contratos.index")->exists(),
            ],

            // 5. Liquidación de Honorarios Médicos
            [
                "class" => PermisionLiquidacionSeeder::class,
                "descripcion" => "Permisos de liquidacion y productividad medica",
                "check" => fn () => Permission::where("name", "liquidacion.index")->exists(),
            ],

            // 6. Recomendaciones de Productos
            [
                "class" => PermisionRecomendacionesSeeder::class,
                "descripcion" => "Permisos de recomendaciones para ordenes CUPS",
                "check" => fn () => Permission::where("name", "config.recomendaciones.index")->exists(),
            ],

            // 7. Citas — Motivos de Rechazo e Inasistencia
            [
                "class" => ConfigCitaRechazoMotivoSeeder::class,
                "descripcion" => "Catalogo de motivos de rechazo y ausentismo de citas",
                "check" => fn () => DB::table("config_cita_rechazo_motivos")->exists(),
            ],

            // 8. Citas y Agenda Hospitalaria
            [
                "class" => PermisionServicioCitasSeeder::class,
                "descripcion" => "Permisos de servicios de citas",
                "check" => fn () => Permission::where("name", "config.servicio.citas.index")->exists(),
            ],
            [
                "class" => PermisosAgendamientoSeeder::class,
                "descripcion" => "Permisos de agendamiento y disponibilidad",
                "check" => fn () => Permission::where("name", "agendamiento.disponibilidad.index")->exists(),
            ],
            [
                "class" => RoleSeederAgenda::class,
                "descripcion" => "Roles y permisos del modulo agenda",
                "check" => fn () => Permission::where("name", "agenda.index")->exists(),
            ],

            // 9. Auditoría de Seguridad
            [
                "class" => PermisoAuditoriaSeeder::class,
                "descripcion" => "Permisos del log de auditoria",
                "check" => fn () => Permission::where("name", "seguridad.auditoria.index")->exists(),
            ],

            // 10. WhatsApp y Parametrización de Sedes
            [
                "class" => PermisionWhatsAppSeeder::class,
                "descripcion" => "Permisos del bot de WhatsApp",
                "check" => fn () => Permission::where("name", "config.whatsapp.saludo.index")->exists(),
            ],
            [
                "class" => ConfigWhatsappSedeNumberSeeder::class,
                "descripcion" => "Lineas telefonicas WhatsApp por sede",
                "check" => fn () => DB::table("config_sede_whatsapp_numbers")->exists(),
            ],
            [
                "class" => ConfigProductosEcografiasSeeder::class,
                "descripcion" => "Productos CUPS de ecografias (biopsias, drenajes)",
                "check" => fn () => ConfigProducto::where("cod_cups", "061001")->exists(),
            ],
            [
                "class" => ConfigProductosEcografias1Seeder::class,
                "descripcion" => "Productos CUPS de Doppler renal y portal",
                "check" => fn () => ConfigProducto::where("cod_cups", "882222")->exists(),
            ],
            [
                "class" => ConfigProductosEcografias2Seeder::class,
                "descripcion" => "Productos CUPS de Doppler arterial y venoso",
                "check" => fn () => ConfigProducto::where("cod_cups", "881360")->exists(),
            ],
            [
                "class" => ConfigProductosEcografias3Seeder::class,
                "descripcion" => "Productos CUPS de ecografias articulares",
                "check" => fn () => ConfigProducto::where("nom_produc", "ECOGRAFIA OBSTETRICA TRANSVAGINAL")->exists(),
            ],
            [
                "class" => ConfigProductosEcografias4Seeder::class,
                "descripcion" => "Productos CUPS de Doppler obstetrico con perfil",
                "check" => fn () => ConfigProducto::where("cod_cups", "881502")->exists(),
            ],
            [
                "class" => ConfigWhatsappEcografiasRequerimientos::class,
                "descripcion" => "Requerimientos clinicos WhatsApp para ecografias",
                "check" => fn () => DB::table("config_whatsapp_requerimeintos")->exists(),
            ],
            [
                "class" => ConfigWhatsappEcografiasRecomendaciones::class,
                "descripcion" => "Recomendaciones y preparaciones de ecografias",
                "check" => fn () => DB::table("config_whatsapp_recomendaciones")->exists(),
            ],

            // 11. Consentimientos Informados y Ecografías
            [
                "class" => PermisionConfigTipoDocumentoSeeder::class,
                "descripcion" => "Permisos de tipos de documento",
                "check" => fn () => Permission::where("name", "config.tipo.documento.index")->exists(),
            ],
            [
                "class" => PermisionConsentimientoInformadoSeeder::class,
                "descripcion" => "Permisos de consentimientos informados generales",
                "check" => fn () => Permission::where("name", "config.consentimiento_informado.index")->exists(),
            ],
            [
                "class" => PlantillaTipoSeeder::class,
                "descripcion" => "Permisos de tipos de plantillas de configuracion",
                "check" => fn () => Permission::where("name", "config.plantillas.tipos.create")->exists(),
            ],
            [
                "class" => PermisionConsentimientosEcografiasSeeder::class,
                "descripcion" => "Permisos de consentimientos informados de ecografias",
                "check" => fn () => Permission::where("name", "ecografias.consentimientos.index")->exists(),
            ],
            [
                "class" => PermisionEcografiasSeeder::class,
                "descripcion" => "Permisos del modulo clinico de ecografias",
                "check" => fn () => Permission::where("name", "ecografias.index")->exists(),
            ],
            [
                "class" => RoleSeederReporteLectura::class,
                "descripcion" => "Permisos de reportes de lectura de estudios",
                "check" => fn () => Permission::where("name", "estudios.reporteLectura.index")->exists(),
            ],

            // 12. HelpDesk (Catálogos y Permisos)
            [
                "class" => HelpDeskCatalogosSeeder::class,
                "descripcion" => "Categorias y prioridades de HelpDesk",
                "check" => fn () => DB::table("help_desk_categorias")->exists(),
            ],
            [
                "class" => HelpDeskPermisosSeeder::class,
                "descripcion" => "Permisos del modulo HelpDesk",
                "check" => fn () => Permission::where("name", "helpdesk.tickets.index")->exists(),
            ],

            // 13. Deduplicación de CUPS
            [
                "class" => ConfigProductosDedupeSeeder::class,
                "descripcion" => "Deduplicacion preventiva de codigos CUPS",
                "check" => fn () => ConfigProducto::where("id", 1)->where("estado", 0)->exists() || ConfigProducto::where("id", 116)->where("estado", 0)->exists(),
            ],
        ];
    }

    /**
     * Ejecuta secuencialmente los seeders verificando previamente si ya fueron aplicados.
     *
     * Flujo del proceso:
     * 1. Limpia la caché en memoria de Spatie para lecturas frescas.
     * 2. Itera sobre cada definición de seeder comprobando si ya se encuentra aplicado.
     * 3. Si ya existe, lo omite con aviso informativo visual.
     * 4. Si falta, ejecuta el seeder en modo silencioso y captura excepciones.
     * 5. Invalida y refresca la caché de permisos de Spatie.
     * 6. Presenta un resumen ejecutivo de aplicados, omitidos y fallidos.
     *
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-27
     */
    public function handle(): int
    {
        $this->info("===================================================================");
        $this->info("  LIDiag — Verificacion y Siembra Inteligente de Seeders");
        $this->info("===================================================================");

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $seeders = $this->getSeedersDefinitions();
        $total = count($seeders);
        $aplicados = 0;
        $omitidos = 0;
        $fallidos = 0;
        $force = (bool) $this->option("force");

        foreach ($seeders as $index => $item) {
            $num = $index + 1;
            $class = $item["class"];
            $nombreCorto = class_basename($class);
            $checkFn = $item["check"];

            $this->line("<comment>[{$num}/{$total}]</comment> Verificando <info>{$nombreCorto}</info> ({$item['descripcion']})...");

            $yaAplicado = false;
            if (! $force) {
                try {
                    $yaAplicado = (bool) $checkFn();
                } catch (\Throwable) {
                    $yaAplicado = false;
                }
            }

            if ($yaAplicado) {
                $this->line("      <fg=yellow>⏭ Ya aplicado en base de datos. Se omite para evitar duplicados.</>");
                $omitidos++;

                continue;
            }

            try {
                $this->callSilent("db:seed", [
                    "--class" => $class,
                    "--force" => true,
                ]);
                $this->line("      <fg=green>✔ Aplicado exitosamente.</>");
                $aplicados++;
            } catch (\Throwable $e) {
                $this->error("      ✘ Fallo al ejecutar {$nombreCorto}: ".$e->getMessage());
                $fallidos++;
            }
        }

        $this->newLine();
        $this->info("Sincronizando cache de permisos de Spatie...");
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->callSilent("permissions:cache-reset");
        $this->line("<fg=green>✔ Cache de permisos sincronizada correctamente.</>");

        $this->newLine();
        $this->info("-------------------------------------------------------------------");
        $this->info("Resumen: {$aplicados} aplicados, {$omitidos} omitidos (ya existian), {$fallidos} fallidos de {$total} seeders.");
        $this->info("-------------------------------------------------------------------");

        if ($this->option("audit")) {
            $this->newLine();
            $this->info("Ejecutando auditoria final de permisos...");
            $this->call("permissions:audit");
        }

        return $fallidos === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
