<?php

namespace App\Console\Commands;

use App\Models\Configuracion\ConfigModulo;
use App\Models\Configuracion\ConfigProducto;
use App\Models\Configuracion\ConfigRecomendacionProducto;
use App\Models\Configuracion\ConfigServicio;
use App\Models\Configuracion\ConfigTipoDocumento;
use App\Models\Configuracion\ConfigWhatsapp;
use App\Models\User;
use Database\Seeders\ConfigCitaRechazoMotivoSeeder;
use Database\Seeders\ConfigRecomendacionProductoSeeder;
use Database\Seeders\ConfigServiciosSeeder;
use Database\Seeders\Configuracion\ConfigAdminSaludSeeder;
use Database\Seeders\Configuracion\ConfigDiagnosticoSeeder;
use Database\Seeders\Configuracion\ConfigPrioridadeSeeder;
use Database\Seeders\Configuracion\ConfigProductosDedupeSeeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias1Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias2Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias3Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografias4Seeder;
use Database\Seeders\Configuracion\ConfigProductosEcografiasSeeder;
use Database\Seeders\Configuracion\ConfigProductoSeeder;
use Database\Seeders\Configuracion\ConfigSedeSeeder;
use Database\Seeders\Configuracion\ConfigWhatsappEcografiasRecomendaciones;
use Database\Seeders\Configuracion\ConfigWhatsappEcografiasRequerimientos;
use Database\Seeders\Configuracion\ConfigWhatsappSaludoSeeder;
use Database\Seeders\Configuracion\ConfigWhatsappSedeNumberSeeder;
use Database\Seeders\HelpDesk\HelpDeskCatalogosSeeder;
use Database\Seeders\HelpDesk\HelpDeskPermisosSeeder;
use Database\Seeders\InterfaceTeleRadiologiaSeeder;
use Database\Seeders\ModulosSeeder;
use Database\Seeders\PermisionBackupMonitorSeeder;
use Database\Seeders\PermisionConfigTipoDocumentoSeeder;
use Database\Seeders\PermisionConsentimientoInformadoSeeder;
use Database\Seeders\PermisionConsentimientosEcografiasSeeder;
use Database\Seeders\PermisionContratacionSeeder;
use Database\Seeders\PermisionDosimetriaSeeder;
use Database\Seeders\PermisionEcografiasSeeder;
use Database\Seeders\PermisionEstudiosSeeder;
use Database\Seeders\PermisionLiquidacionSeeder;
use Database\Seeders\PermisionRecomendacionesSeeder;
use Database\Seeders\PermisionSeguridadTokensSeeder;
use Database\Seeders\PermisionServicioCitasSeeder;
use Database\Seeders\PermisionWhatsAppSeeder;
use Database\Seeders\PermisoAuditoriaSeeder;
use Database\Seeders\PermisosAgendamientoSeeder;
use Database\Seeders\PlantillaSeeder;
use Database\Seeders\PlantillaTipoSeeder;
use Database\Seeders\ProductosEspinalSeeder;
use Database\Seeders\RoleSeederAgenda;
use Database\Seeders\RoleSeederReporteLectura;
use Database\Seeders\Seguridad\RoleSeeder;
use Database\Seeders\Seguridad\UserSeeder;
use Database\Seeders\TipoDocumentoSeeder;
use Database\Seeders\UmbralesNrdSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SeedFaltantesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "seed:faltantes
                            {--dry-run : Solo audita y muestra el listado de seeders aplicados y faltantes sin ejecutar cambios}
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
            // 1. Configuración Base y Sedes/Servicios (prerrequisito de llaves foráneas)
            [
                "class" => ConfigSedeSeeder::class,
                "descripcion" => "Sedes hospitalarias asistenciales",
                "check" => fn () => DB::table("config_sedes")->exists(),
            ],
            [
                "class" => ConfigServiciosSeeder::class,
                "descripcion" => "Servicios hospitalarios base (Tomografia, Ecografia, RX, etc.)",
                "check" => fn () => ConfigServicio::query()->exists(),
            ],
            [
                "class" => ConfigAdminSaludSeeder::class,
                "descripcion" => "Catálogo de aseguradoras / EPS",
                "check" => fn () => DB::table("config_admin_salud")->exists(),
            ],
            [
                "class" => ConfigDiagnosticoSeeder::class,
                "descripcion" => "Catálogo de diagnósticos CIE-10",
                "check" => fn () => DB::table("config_diagnosticos")->exists(),
            ],
            [
                "class" => ConfigPrioridadeSeeder::class,
                "descripcion" => "Prioridades de atención clínica",
                "check" => fn () => DB::table("config_prioridades")->exists(),
            ],
            [
                "class" => TipoDocumentoSeeder::class,
                "descripcion" => "Tipos de documento de identidad (CC, TI, etc.)",
                "check" => fn () => ConfigTipoDocumento::query()->exists(),
            ],
            [
                "class" => PermisionConfigTipoDocumentoSeeder::class,
                "descripcion" => "Permisos para gestión de tipos de documento",
                "check" => fn () => Permission::where("name", "config.tipo.documento.index")->exists(),
            ],

            // 2. Roles, Usuarios y Módulos Base
            [
                "class" => RoleSeeder::class,
                "descripcion" => "Roles base institucionales (Administrador, Medico, etc.)",
                "check" => fn () => Role::where("name", "Administrador")->exists(),
            ],
            [
                "class" => UserSeeder::class,
                "descripcion" => "Usuario administrador institucional inicial",
                "check" => fn () => User::where("email", "administrador@administrador.com")->exists(),
            ],
            [
                "class" => ModulosSeeder::class,
                "descripcion" => "Módulos asistenciales base (Rx, Ecografías, Oftalmología)",
                "check" => fn () => ConfigModulo::query()->exists(),
            ],
            [
                "class" => PlantillaSeeder::class,
                "descripcion" => "Permisos de plantillas de informes médicos",
                "check" => fn () => Permission::where("name", "config.plantillas.index")->exists(),
            ],
            [
                "class" => PlantillaTipoSeeder::class,
                "descripcion" => "Permisos de tipos de plantillas de configuración",
                "check" => fn () => Permission::where("name", "config.plantillas.tipos.create")->exists(),
            ],

            // 3. Catálogo de Productos CUPS y Especialidades
            [
                "class" => ConfigProductoSeeder::class,
                "descripcion" => "Catálogo maestro inicial de procedimientos CUPS",
                "check" => fn () => DB::table("config_productos")->exists(),
            ],
            [
                "class" => ConfigProductosEcografiasSeeder::class,
                "descripcion" => "Productos CUPS de ecografías (biopsias, drenajes)",
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
                "descripcion" => "Productos CUPS de ecografías articulares y transvaginal",
                "check" => fn () => ConfigProducto::where("nom_produc", "ECOGRAFIA OBSTETRICA TRANSVAGINAL")->exists(),
            ],
            [
                "class" => ConfigProductosEcografias4Seeder::class,
                "descripcion" => "Productos CUPS de Doppler obstétrico con perfil",
                "check" => fn () => ConfigProducto::where("cod_cups", "881502")->exists(),
            ],
            [
                "class" => ConfigProductosDedupeSeeder::class,
                "descripcion" => "Deduplicación preventiva de códigos CUPS repetidos",
                "check" => fn () => ConfigProducto::where("id", 1)->where("estado", 0)->exists()
                    || ConfigProducto::where("id", 116)->where("estado", 0)->exists(),
            ],
            [
                "class" => ProductosEspinalSeeder::class,
                "descripcion" => "Sincronización masiva de CUPS y tarifas asistenciales Espinal",
                "check" => fn () => ConfigProducto::where("cod_cups", "879111")->where("estado", 1)->exists(),
            ],

            // 4. Estudios y Tele-Radiología
            [
                "class" => PermisionEstudiosSeeder::class,
                "descripcion" => "Permisos de estudios (asignación, lectura, transcripción)",
                "check" => fn () => Permission::where("name", "estudio.index")->exists()
                    && Permission::where("name", "estudio.leer")->exists(),
            ],
            [
                "class" => RoleSeederReporteLectura::class,
                "descripcion" => "Permisos de reportes de lectura de estudios",
                "check" => fn () => Permission::where("name", "estudios.reporteLectura.index")->exists(),
            ],
            [
                "class" => InterfaceTeleRadiologiaSeeder::class,
                "descripcion" => "Permisos de interoperabilidad con Tele-Radiología externa",
                "check" => fn () => Permission::where("name", "tele.ragiologia.index")->exists(),
            ],

            // 5. Consentimientos Informados y Ecografías
            [
                "class" => PermisionConsentimientoInformadoSeeder::class,
                "descripcion" => "Permisos de consentimientos informados generales",
                "check" => fn () => Permission::where("name", "config.consentimiento_informado.index")->exists(),
            ],
            [
                "class" => PermisionConsentimientosEcografiasSeeder::class,
                "descripcion" => "Permisos de consentimientos informados de ecografías",
                "check" => fn () => Permission::where("name", "ecografias.consentimientos.index")->exists(),
            ],
            [
                "class" => PermisionEcografiasSeeder::class,
                "descripcion" => "Permisos del módulo clínico de ecografías",
                "check" => fn () => Permission::where("name", "ecografias.index")->exists(),
            ],

            // 6. Dosimetría Radiológica (Res. 482 de 2018)
            [
                "class" => PermisionDosimetriaSeeder::class,
                "descripcion" => "Permisos de dosimetría y telemetría radiológica",
                "check" => fn () => Permission::where("name", "dosimetria.dashboard")->exists(),
            ],
            [
                "class" => UmbralesNrdSeeder::class,
                "descripcion" => "Niveles de Referencia para Diagnóstico (NRD) por región anatómica",
                "check" => fn () => Schema::hasTable("medicion_dosis_umbrales_nrd")
                    && Schema::hasColumn("medicion_dosis_umbrales_nrd", "region_anatomica")
                    && DB::table("medicion_dosis_umbrales_nrd")->whereNotNull("region_anatomica")->exists(),
            ],

            // 7. Seguridad, Backups, Tokens y Auditoría
            [
                "class" => PermisionBackupMonitorSeeder::class,
                "descripcion" => "Permisos de monitoreo y ejecución de backups",
                "check" => fn () => Permission::where("name", "seguridad.backups.index")->exists(),
            ],
            [
                "class" => PermisoAuditoriaSeeder::class,
                "descripcion" => "Permisos del log de auditoría del sistema",
                "check" => fn () => Permission::where("name", "seguridad.auditoria.index")->exists(),
            ],
            [
                "class" => PermisionSeguridadTokensSeeder::class,
                "descripcion" => "Permisos de tokens API de sedes y usuario del Agente Edge",
                "check" => fn () => Permission::where("name", "seguridad.tokens.index")->exists()
                    && User::where("email", "agente.edge@lidiag.gov.co")->exists(),
            ],

            // 8. Contratación y Liquidación Médica
            [
                "class" => PermisionContratacionSeeder::class,
                "descripcion" => "Permisos de contratos institucionales y tarifas médicas",
                "check" => fn () => Permission::where("name", "contratos.index")->exists(),
            ],
            [
                "class" => PermisionLiquidacionSeeder::class,
                "descripcion" => "Permisos de liquidación y productividad médica",
                "check" => fn () => Permission::where("name", "liquidacion.index")->exists(),
            ],

            // 9. Citas y Agendamiento Hospitalario
            [
                "class" => ConfigCitaRechazoMotivoSeeder::class,
                "descripcion" => "Catálogo de motivos de rechazo y ausentismo de citas",
                "check" => fn () => DB::table("config_cita_rechazo_motivos")->exists(),
            ],
            [
                "class" => PermisionServicioCitasSeeder::class,
                "descripcion" => "Permisos de configuración de servicios de citas",
                "check" => fn () => Permission::where("name", "config.servicio.citas.index")->exists(),
            ],
            [
                "class" => PermisosAgendamientoSeeder::class,
                "descripcion" => "Permisos de agendamiento y disponibilidad de citas",
                "check" => fn () => Permission::where("name", "agendamiento.disponibilidad.index")->exists(),
            ],
            [
                "class" => RoleSeederAgenda::class,
                "descripcion" => "Roles y permisos operativos del módulo agenda",
                "check" => fn () => Permission::where("name", "agenda.index")->exists(),
            ],

            // 10. WhatsApp Bot y Preparaciones Clínicas
            [
                "class" => PermisionWhatsAppSeeder::class,
                "descripcion" => "Permisos de configuración del bot de WhatsApp",
                "check" => fn () => Permission::where("name", "config.whatsapp.saludo.index")->exists(),
            ],
            [
                "class" => ConfigWhatsappSedeNumberSeeder::class,
                "descripcion" => "Líneas telefónicas de WhatsApp por sede",
                "check" => fn () => DB::table(Schema::hasTable("config_whatsapp_numbers") ? "config_whatsapp_numbers" : "config_sede_whatsapp_numbers")->exists(),
            ],
            [
                "class" => ConfigWhatsappSaludoSeeder::class,
                "descripcion" => "Mensajes de bienvenida y saludos del Bot de WhatsApp",
                "check" => fn () => ConfigWhatsapp::query()->exists(),
            ],
            [
                "class" => ConfigWhatsappEcografiasRequerimientos::class,
                "descripcion" => "Requerimientos clínicos WhatsApp para ecografías",
                "check" => fn () => DB::table("config_whatsapp_requerimeintos")->exists(),
            ],
            [
                "class" => ConfigWhatsappEcografiasRecomendaciones::class,
                "descripcion" => "Recomendaciones y preparaciones de ecografías vía WhatsApp",
                "check" => fn () => DB::table("config_whatsapp_recomendaciones")->exists(),
            ],
            [
                "class" => PermisionRecomendacionesSeeder::class,
                "descripcion" => "Permisos de administración de recomendaciones CUPS",
                "check" => fn () => Permission::where("name", "config.recomendaciones.index")->exists(),
            ],
            [
                "class" => ConfigRecomendacionProductoSeeder::class,
                "descripcion" => "Recomendaciones y preparación al paciente por examen CUPS",
                "check" => fn () => ConfigRecomendacionProducto::query()->exists(),
            ],

            // 11. HelpDesk (Catálogos y Permisos)
            [
                "class" => HelpDeskCatalogosSeeder::class,
                "descripcion" => "Categorías y prioridades del sistema HelpDesk",
                "check" => fn () => DB::table("help_desk_categorias")->exists(),
            ],
            [
                "class" => HelpDeskPermisosSeeder::class,
                "descripcion" => "Permisos del módulo de soporte HelpDesk",
                "check" => fn () => Permission::where("name", "helpdesk.tickets.index")->exists(),
            ],
        ];
    }

    /**
     * Ejecuta secuencialmente los seeders verificando previamente si ya fueron aplicados.
     *
     * Flujo del proceso:
     * 1. Limpia la caché en memoria de Spatie para lecturas frescas.
     * 2. Itera sobre cada definición de seeder comprobando si ya se encuentra aplicado.
     * 3. Si se pasa la bandera --dry-run, presenta una tabla informativa con el estado de cada seeder sin ejecutar cambios.
     * 4. Si falta, ejecuta el seeder en modo silencioso y captura excepciones.
     * 5. Invalida y refresca la caché de permisos de Spatie.
     * 6. Presenta un resumen ejecutivo de aplicados, omitidos y fallidos.
     *
     * @return int Código de salida del comando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-11
     */
    public function handle(): int
    {
        $this->info("===================================================================");
        $this->info("  LIDiag — Verificación y Siembra Inteligente de Seeders");
        $this->info("===================================================================");

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $seeders = $this->getSeedersDefinitions();
        $total = count($seeders);
        $force = (bool) $this->option("force");
        $dryRun = (bool) $this->option("dry-run");

        // Modo Auditoría / Vista Previa sin ejecución
        if ($dryRun) {
            $this->comment("MODO AUDITORÍA (--dry-run activo): No se realizarán escrituras en base de datos.");
            $this->newLine();

            $filasTabla = [];
            $pendientes = 0;
            $aplicados = 0;

            foreach ($seeders as $index => $item) {
                $num = $index + 1;
                $class = $item["class"];
                $nombreCorto = class_basename($class);
                $checkFn = $item["check"];

                $yaAplicado = false;
                try {
                    $yaAplicado = (bool) $checkFn();
                } catch (\Throwable) {
                    $yaAplicado = false;
                }

                if ($yaAplicado) {
                    $aplicados++;
                    $estadoLabel = "<fg=green>✔ APLICADO</>";
                } else {
                    $pendientes++;
                    $estadoLabel = "<fg=yellow;options=bold>⏳ PENDIENTE</>";
                }

                $filasTabla[] = [
                    $num,
                    $nombreCorto,
                    $estadoLabel,
                    $item["descripcion"],
                ];
            }

            $this->table(
                ["#", "Seeder", "Estado", "Descripción"],
                $filasTabla
            );

            $this->newLine();
            $this->info("-------------------------------------------------------------------");
            $this->info("Resumen de Auditoría: {$aplicados} aplicados, {$pendientes} pendientes de {$total} seeders.");
            $this->info("-------------------------------------------------------------------");

            if ($pendientes > 0) {
                $this->comment("Para ejecutar los seeders pendientes de forma automática, ejecute:");
                $this->line("  <info>php artisan seed:faltantes</info>");
            } else {
                $this->line("<fg=green>✔ Todos los seeders del catálogo ya se encuentran aplicados.</>");
            }

            return Command::SUCCESS;
        }

        $aplicados = 0;
        $omitidos = 0;
        $fallidos = 0;

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
        $this->info("Sincronizando caché de permisos de Spatie...");
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->callSilent("permissions:cache-reset");
        $this->line("<fg=green>✔ Caché de permisos sincronizada correctamente.</>");

        $this->newLine();
        $this->info("-------------------------------------------------------------------");
        $this->info("Resumen: {$aplicados} aplicados, {$omitidos} omitidos (ya existían), {$fallidos} fallidos de {$total} seeders.");
        $this->info("-------------------------------------------------------------------");

        if ($this->option("audit")) {
            $this->newLine();
            $this->info("Ejecutando auditoría final de permisos...");
            $this->call("permissions:audit");
        }

        return $fallidos === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
