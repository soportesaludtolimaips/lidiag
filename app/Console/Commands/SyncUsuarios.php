<?php

namespace App\Console\Commands;

use App\Models\Configuracion\ConfigPlantilla as PlantillaDestino;
use App\Models\Configuracion\ConfigPlantillaTipo as PlantillaTipoDestino;
use App\Models\ImportarEcografias\ConfigPlantillaDetalle as PlantillaDetalleOrigen;
use App\Models\ImportarEcografias\ConfigPlantillaTipo as PlantillaTipoOrigen;
use App\Models\ImportarEcografias\GenePersonal as PersonalOrigen;
use App\Models\User as UserDestino;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SyncUsuarios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync-usuarios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando sincronización de usuarios...');
        $this->newLine();

        $this->syncUsers();

        $this->info('Iniciando sincronización de las plantilla de ecografias...');
        $this->newLine();

        $this->syncPlantillaEcografias();

        $this->newLine();
        $this->info('Sincronización completada.');
    }

    /**
     * Sincroniza los usuarios y las plantillas de ecografías desde el origen de importación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function syncUsers()
    {
        $this->info('Sincronizando usuarios...');

        $users = PersonalOrigen::get();

        foreach ($users as $user) {
            $this->info("Sincronizando usuario: {$user->num_docu} - {$user->nom_usua} - {$user->pri_nom} {$user->pri_ape}");

            $userDestino = UserDestino::where('num_docu', $user->num_docu)->first();
            if (! $userDestino) {
                $userDestino = new UserDestino;
                $userDestino->num_docu = $user->num_docu;
                $userDestino->password = Hash::make('123456'); // Contraseña por defecto
                $userDestino->name = trim($user->pri_nom.' '.$user->pri_ape);
                $userDestino->reg_med = $user->num_regis;

                $email = trim($user->e_mail ?? '');
                if (empty($email)) {
                    $email = $user->num_docu.'@placeholder.com';
                }
                $userDestino->email = $email;

                $userDestino->interface_id = $user->id_sahi;
                $userDestino->estado = ! is_null($user->acti) ? (int) $user->acti : 1;
                $userDestino->save();

                // Rol Medico si es médico (tipo_personal == 2 o tiene registro médico), sino Administrativo
                if (($user->tipo_personal == 2) || ! empty($user->num_regis)) {
                    $userDestino->assignRole('Medico');
                } else {
                    $userDestino->assignRole('Administrativo');
                }

                $userDestino->modulos()->syncWithoutDetaching([2]);
            }
        }
    }

    /**
     * Sincroniza los tipos de plantilla y sus detalles desde el origen de importación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-13
     */
    private function syncPlantillaEcografias()
    {
        $this->info('Sincronizando plantilla de ecografias...');

        $plantillaOrigen = PlantillaTipoOrigen::get();

        foreach ($plantillaOrigen as $plantilla) {
            $this->info("Sincronizando plantilla: {$plantilla->nom_tipo}");

            $plantillaDestinoTipo = PlantillaTipoDestino::where('nom_tipo', $plantilla->nom_tipo)->first();
            if (! $plantillaDestinoTipo) {
                $plantillaDestinoTipo = new PlantillaTipoDestino;
                $plantillaDestinoTipo->nom_tipo = $plantilla->nom_tipo;
                $plantillaDestinoTipo->save();
            }

            $plantillaDetalleOrigen = PlantillaDetalleOrigen::where('tipo_id_tipo', $plantilla->id_tipo)->get();
            foreach ($plantillaDetalleOrigen as $plantillaDetalle) {
                /**
                 * Personal origen
                 */
                $personalOrigen = PersonalOrigen::where('id_personal', $plantillaDetalle->id_personal)->first();
                if (! $personalOrigen) {
                    $this->warn("Personal origen ID {$plantillaDetalle->id_personal} no encontrado.");

                    continue;
                }

                /**
                 * Personal destino
                 */
                $personalDestino = UserDestino::where('num_docu', $personalOrigen->num_docu)->first();
                if (! $personalDestino) {
                    $this->warn("Usuario destino no encontrado para cédula: {$personalOrigen->num_docu}");

                    continue;
                }

                /**
                 * Plantilla detalle destino (idempotente)
                 */
                $plantillaDestinoDetalle = PlantillaDestino::where('plantilla_tipo_id', $plantillaDestinoTipo->id)
                    ->where('medico_id', $personalDestino->id)
                    ->where('nom_plantilla', $plantillaDetalle->nom_plantilla)
                    ->first();

                if (! $plantillaDestinoDetalle) {
                    $plantillaDestinoDetalle = new PlantillaDestino;
                    $plantillaDestinoDetalle->plantilla_tipo_id = $plantillaDestinoTipo->id;
                    $plantillaDestinoDetalle->medico_id = $personalDestino->id;
                    $plantillaDestinoDetalle->nom_plantilla = $plantillaDetalle->nom_plantilla;
                }

                $plantillaDestinoDetalle->plantilla = $plantillaDetalle->detalle ?? '';
                $plantillaDestinoDetalle->opinion = $plantillaDetalle->opinion ?? '';
                $plantillaDestinoDetalle->estado = ! is_null($plantillaDetalle->acti) ? (int) $plantillaDetalle->acti : 1;
                $plantillaDestinoDetalle->save();
            }
        }
    }
}
