<?php

namespace App\Console\Commands;

use App\Models\Configuracion\ConfigAdminSalud;
use App\Models\Configuracion\ConfigProducto as TargetConfigProducto;
use App\Models\Ecografias\Ecografia;
// Source Models (ImportarEcografias) - Namespace is App\Models based on file content
use App\Models\Ecografias\EcografiaProducto;
use App\Models\General\Paciente;
use App\Models\ImportarEcografias\ConfigEps;
use App\Models\ImportarEcografias\ConfigProducto as SourceConfigProducto;
// Target Models
use App\Models\ImportarEcografias\Lectura;
use App\Models\ImportarEcografias\SeguUsuario;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncDicomEcografias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:dicom-ecografias';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza usuarios, EPS, productos y ecografías desde la conexión mysql_importar_dicom';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Iniciando sincronización DICOM...');

        try {
            // Eliminamos la transacción global para que los datos se guarden progresivamente
            // y sean visibles si el proceso es largo o si falla más adelante.
            $this->syncUsers();
            $this->syncEps();
            $this->syncProducts();
            $this->syncEcografias();

            $this->info('Sincronización completada exitosamente.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Ocurrió un error durante la sincronización: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    private function syncUsers()
    {
        $this->info('Sincronizando Usuarios...');
        $usuariosOrigen = SeguUsuario::with('personal')->get();
        $count = count($usuariosOrigen);
        $this->info("Se encontraron {$count} usuarios para procesar.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($usuariosOrigen as $usuarioOrigen) {
            try {
                if (! $usuarioOrigen->personal) {
                    $bar->advance();

                    continue;
                }

                $personal = $usuarioOrigen->personal;

                // Buscar por número de documento
                $user = User::where('num_docu', $personal->num_docu)->first();

                if ($user) {
                    $bar->advance();

                    continue;
                }

                $user = new User;
                $user->num_docu = $personal->num_docu;
                $user->password = Hash::make(Str::random(16));
                $user->password_changed_at = null;
                $user->name = trim($personal->pri_nom.' '.$personal->pri_ape);
                $user->reg_med = $personal->num_regis;

                $email = trim($personal->e_mail);
                if (empty($email)) {
                    $email = $usuarioOrigen->login.'@placeholder.com';
                }
                $user->email = $email;

                // $user->interface_id = $usuarioOrigen->id_usua; // Opcional si se quiere guardar referencia
                $user->save();
            } catch (\Exception $e) {
                Log::error("Error sincronizando usuario {$usuarioOrigen->id_usua}: ".$e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Usuarios sincronizados.');
    }

    private function syncEps()
    {
        $this->info('Sincronizando EPS...');
        $epsOrigen = ConfigEps::all();
        $count = count($epsOrigen);
        $this->info("Se encontraron {$count} EPS para procesar.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($epsOrigen as $eps) {
            try {
                $adminSalud = ConfigAdminSalud::where('cod_admin_salud', $eps->cod_eps)->first();

                if ($adminSalud) {
                    $bar->advance();

                    continue;
                }

                $adminSalud = new ConfigAdminSalud;
                $adminSalud->cod_admin_salud = $eps->cod_eps;
                $adminSalud->nom_admin_salud = $eps->nom_eps;
                // $adminSalud->nit_admin_salud = null; // No hay campo equivalente
                $adminSalud->estado = 1;
                $adminSalud->save();
            } catch (\Exception $e) {
                Log::error("Error sincronizando EPS {$eps->cod_eps}: ".$e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('EPS sincronizadas.');
    }

    private function syncProducts()
    {
        $this->info('Sincronizando Productos...');
        $productosOrigen = SourceConfigProducto::all();
        $count = count($productosOrigen);
        $this->info("Se encontraron {$count} productos para procesar.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($productosOrigen as $prodOrigen) {
            try {
                $producto = TargetConfigProducto::where('cod_cups', $prodOrigen->cod_produc)->first();

                if ($producto) {
                    $bar->advance();

                    continue;
                }

                $producto = new TargetConfigProducto;
                $producto->cod_cups = $prodOrigen->cod_produc;
                $producto->nom_produc = $prodOrigen->nom_produc;
                $producto->val_produc = $prodOrigen->val_soat;
                $producto->estado = 1;
                $producto->save();
            } catch (\Exception $e) {
                Log::error("Error sincronizando producto {$prodOrigen->cod_produc}: ".$e->getMessage());
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Productos sincronizados.');
    }

    private function syncEcografias()
    {
        $this->info('Sincronizando Ecografías...');

        $count = Lectura::count();
        $this->info("Se encontraron {$count} lecturas para procesar.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $synced = 0;
        $errors = 0;

        Lectura::with(['medico', 'paciente.tipoDocumento', 'eps', 'productos', 'usuarioRegistrador.personal'])
            ->chunkById(100, function ($lecturas) use ($bar, &$synced, &$errors) {
                foreach ($lecturas as $lectura) {
                    try {
                        $this->syncSingleLectura($lectura);
                        $synced++;
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::error("Error sincronizando lectura {$lectura->id_lectura}: {$e->getMessage()}", [
                            'exception' => $e,
                        ]);
                    }
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Sincronización completada: {$synced} exitosas, {$errors} errores.");
    }

    private function syncSingleLectura($lectura): void
    {
        $medico = $this->resolveMedico($lectura);
        if (! $medico) {
            Log::warning("Lectura {$lectura->id_lectura} omitida: no se pudo resolver el médico.");

            return;
        }

        $ecografia = new Ecografia;
        $ecografia->medico_id = $medico->id;
        $ecografia->usua_regis_id = $this->resolveRegistrador($lectura, $medico);
        $ecografia->paciente_id = $this->resolvePaciente($lectura);
        $ecografia->admin_salud_id = $this->resolveAdminSalud($lectura);
        $ecografia->estado = 1;
        $ecografia->atencion_id = $lectura->id_atencion;
        $ecografia->fec_cita_sahi = $lectura->fechor_lectura;
        $ecografia->fec_regis_cita_sahi = $lectura->fechor_lectura;
        $ecografia->save();

        $this->syncProductos($ecografia, $lectura);
    }

    private function resolveMedico($lectura): ?User
    {
        if (! $lectura->medico || ! $lectura->medico->num_docu) {
            return null;
        }

        $user = User::where('num_docu', $lectura->medico->num_docu)->first();
        if ($user) {
            return $user;
        }

        $user = new User;
        $user->num_docu = $lectura->medico->num_docu;
        $user->password = Hash::make(Str::random(16));
        $user->password_changed_at = null;
        $user->name = trim($lectura->medico->pri_nom.' '.$lectura->medico->pri_ape);
        $user->email = $lectura->medico->e_mail ?? trim($lectura->medico->num_docu).'@salidtolimaips.com';
        $user->reg_med = $lectura->medico->num_regis;
        $user->save();

        return $user;
    }

    private function resolveRegistrador($lectura, User $fallback): int
    {
        if ($lectura->usuarioRegistrador && $lectura->usuarioRegistrador->personal) {
            $usuaRegis = User::where('num_docu', $lectura->usuarioRegistrador->personal->num_docu)->first();
            if ($usuaRegis) {
                return $usuaRegis->id;
            }
        }

        return $fallback->id;
    }

    private function resolvePaciente($lectura): ?int
    {
        if (! $lectura->paciente || ! $lectura->paciente->num_docu) {
            return null;
        }

        $paciente = Paciente::where('num_docu', $lectura->paciente->num_docu)->first();
        if ($paciente) {
            return $paciente->id;
        }

        $tipoDocu = $this->mapTipoDocumento($lectura->paciente->tipoDocumento);

        $source = $lectura->paciente;
        $paciente = new Paciente;
        $paciente->tipo_documento_id = $tipoDocu;
        $paciente->num_docu = $source->num_docu;
        $paciente->nombres = $source->pri_nom;
        $paciente->seg_nom = $source->seg_nom;
        $paciente->pri_ape = $source->pri_ape;
        $paciente->seg_ape = $source->seg_ape;
        $paciente->direccion = $source->dir;
        $paciente->sexo = $source->sexo;
        $paciente->fec_naci = $source->fec_naci;
        $paciente->tel = $source->tel;
        $paciente->cel = $source->cel;
        $paciente->email = $source->e_mail;
        $paciente->save();

        return $paciente->id;
    }

    private function mapTipoDocumento(?string $sourceType): ?int
    {
        return match ($sourceType) {
            'NI' => 10,
            'CC' => 1,
            'CE' => 2,
            'RC' => 5,
            'PA' => 4,
            'TI' => 4,
            'AS' => 6,
            'MS' => 7,
            'NU' => 8,
            'RU' => 9,
            'PE' => 11,
            default => null,
        };
    }

    private function resolveAdminSalud($lectura): ?int
    {
        if (! $lectura->eps || ! $lectura->eps->cod_eps) {
            return null;
        }

        $adminSalud = ConfigAdminSalud::where('cod_admin_salud', $lectura->eps->cod_eps)->first();

        return $adminSalud?->id;
    }

    private function syncProductos(Ecografia $ecografia, $lectura): void
    {
        EcografiaProducto::where('ecografia_id', $ecografia->id)->delete();

        if (! $lectura->productos) {
            return;
        }

        foreach ($lectura->productos as $prodLectura) {
            $target = TargetConfigProducto::where('cod_cups', $prodLectura->cod_produc)->first();

            if (! $target) {
                $target = new TargetConfigProducto;
                $target->cod_cups = $prodLectura->cod_produc;
                $target->nom_produc = $prodLectura->nom_produc;
                $target->val_produc = $prodLectura->val_soat;
                $target->estado = 1;
                $target->save();
            }

            $ecoProd = new EcografiaProducto;
            $ecoProd->ecografia_id = $ecografia->id;
            $ecoProd->producto_id = $target->id;
            $ecoProd->lectura = $lectura->lectura;
            $ecoProd->opinion = $lectura->opinion;
            $ecoProd->estado = 1;
            $ecoProd->save();
        }
    }
}
