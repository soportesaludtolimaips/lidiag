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
                $user->password = Hash::make('123456'); // Contraseña por defecto
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

        // Usamos chunkById para evitar problemas de memoria con grandes volúmenes de datos
        Lectura::with(['medico', 'paciente.tipoDocumento', 'eps', 'productos', 'usuarioRegistrador.personal'])
            ->chunkById(100, function ($lecturas) use ($bar) {
                foreach ($lecturas as $lectura) {
                    // try {
                    $ecografia = new Ecografia;

                    // 2. Buscar Médico (User)
                    if (! $lectura->medico) {
                        Log::error("Lectura {$lectura->id_lectura} sin médico asociado.");
                        exit;
                    }

                    $numDoc = $lectura->medico->num_docu ?? null;

                    if (! $numDoc) {
                        Log::error("Lectura {$lectura->id_lectura} con médico sin num_docu.");
                        exit;
                    }

                    // Buscar por número de documento
                    $user = User::where('num_docu', $lectura->medico->num_docu)->first();

                    $medicoId = '';
                    if (! $user) {
                        $userNew = new User;
                        $userNew->num_docu = $lectura->medico->num_docu;
                        $userNew->password = Hash::make('123456'); // Contraseña por defecto
                        $userNew->name = trim($lectura->medico->pri_nom.' '.$lectura->medico->pri_ape);
                        $userNew->email = $lectura->medico->e_mail ?? trim($lectura->medico->num_docu).'@salidtolimaips.com';
                        $userNew->reg_med = $lectura->medico->num_regis;
                        $userNew->save();
                        $medicoId = $userNew->id;
                    }

                    if ($medicoId) {
                        $ecografia->medico_id = $medicoId;
                    }

                    if ($user->id) {
                        $ecografia->medico_id = $user->id;
                    }

                    // 2.1 Buscar Usuario Registrador (User)
                    $usuaRegis = null;
                    if ($lectura->usuarioRegistrador && $lectura->usuarioRegistrador->personal) {
                        $usuaRegis = User::where('num_docu', $lectura->usuarioRegistrador->personal->num_docu)->first();
                    }
                    // Si no se encuentra, usamos el médico como fallback o el usuario 1 (admin) para evitar error SQL
                    $ecografia->usua_regis_id = $usuaRegis ? $usuaRegis->id : ($medico ? $medico->id : 1);

                    // 3. Buscar Paciente
                    $pacienteId = null;
                    if ($lectura->paciente) {

                        $sourceDocType = $lectura->paciente->tipoDocumento;

                        $tipoDocu = null;
                        switch ($sourceDocType) {
                            case 'NI': $tipoDocu = 10;
                                break;
                            case 'CC': $tipoDocu = 1;
                                break;
                            case 'CE': $tipoDocu = 2;
                                break;
                            case 'RC': $tipoDocu = 5;
                                break;
                            case 'PA': $tipoDocu = 4;
                                break;
                            case 'TI': $tipoDocu = 4;
                                break;
                            case 'AS': $tipoDocu = 6;
                                break;
                            case 'MS': $tipoDocu = 7;
                                break;
                            case 'NU': $tipoDocu = 8;
                                break;
                            case 'RU': $tipoDocu = 9;
                                break;
                            case 'PE': $tipoDocu = 11;
                                break;
                        }

                        // Buscar paciente por num_docu en el destino
                        $paciente = Paciente::where('num_docu', $lectura->paciente->num_docu)->first();

                        if (! $paciente) {

                            $sourcePaciente = $lectura->paciente;

                            if (! $sourcePaciente->num_docu) {
                                $this->info('Paciente sin documento');
                                $this->info('Lectura: '.$lectura->id_lectura);
                                exit;
                            }

                            $paciente = new Paciente;
                            $paciente->tipo_documento_id = $tipoDocu;
                            $paciente->num_docu = $sourcePaciente->num_docu;
                            $paciente->nombres = $sourcePaciente->pri_nom;
                            $paciente->seg_nom = $sourcePaciente->seg_nom;
                            $paciente->pri_ape = $sourcePaciente->pri_ape;
                            $paciente->seg_ape = $sourcePaciente->seg_ape;
                            $paciente->direccion = $sourcePaciente->dir;
                            $paciente->sexo = $sourcePaciente->sexo;
                            $paciente->fec_naci = $sourcePaciente->fec_naci;
                            $paciente->tel = $sourcePaciente->tel;
                            $paciente->cel = $sourcePaciente->cel;
                            $paciente->email = $sourcePaciente->e_mail;
                            $paciente->save();
                        }

                        $pacienteId = $paciente->id;
                    }

                    $ecografia->paciente_id = $pacienteId;

                    // 4. Buscar EPS (ConfigAdminSalud)
                    $adminSalud = null;
                    if ($lectura->eps) {
                        $adminSalud = ConfigAdminSalud::where('cod_admin_salud', $lectura->eps->cod_eps)->first();
                    }
                    $ecografia->admin_salud_id = $adminSalud ? $adminSalud->id : null;

                    // Otros campos
                    $ecografia->estado = 1;
                    $ecografia->atencion_id = $lectura->id_atencion;
                    $ecografia->fec_cita_sahi = $lectura->fechor_lectura;
                    $ecografia->fec_regis_cita_sahi = $lectura->fechor_lectura;

                    $ecografia->save();

                    // 5. Sincronizar Productos de la Ecografía
                    // Borraremos los existentes para esta ecografía para evitar duplicados al re-sincronizar (aunque si es nueva no debería haber)
                    EcografiaProducto::where('ecografia_id', $ecografia->id)->delete();

                    $this->info($lectura);
                    exit;
                    foreach ($lectura->productos as $prodLectura) {
                        $targetProd = TargetConfigProducto::where('cod_cups', $prodLectura->cod_produc)->first();

                        if (! $targetProd) {
                            // Crear si no existe
                            $targetProd = new TargetConfigProducto;
                            $targetProd->cod_cups = $prodLectura->cod_produc;
                            $targetProd->nom_produc = $prodLectura->nom_produc;
                            $targetProd->val_produc = $prodLectura->val_soat;
                            $targetProd->estado = 1;
                            $targetProd->save();
                        }

                        $ecoProd = new EcografiaProducto;
                        $ecoProd->ecografia_id = $ecografia->id;
                        $ecoProd->producto_id = $targetProd->id;
                        $ecoProd->lectura = $lectura->lectura;
                        $ecoProd->opinion = $lectura->opinion;
                        $ecoProd->estado = 1;
                        $ecoProd->save();
                    }
                    /* } catch (\Exception $e) {
                        $this->error("Error sincronizando lectura ID {$lectura->id_lectura}: " . $e->getMessage());
                        Log::error("medico: " . get_class($lectura->medico));
                        Log::error("usuarioRegistrador: " . get_class($lectura->user));
                        Log::error("paciente: " . get_class($lectura->paciente));
                        //Log::error("Error sincronizando lectura {$lectura->id_lectura}: " . $e->getMessage());
                    } */
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info('Ecografías sincronizadas.');
    }
}
