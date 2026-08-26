<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Usuarios de prueba con contraseña conocida (`password`) y sin cambio
 * forzado (password_changed_at seteado). A los médicos se les carga una
 * firma PNG de prueba. Idempotente.
 */
class ConsentimientosTestUsersSeeder extends Seeder
{
    private const PASSWORD = 'password';

    public function run(): void
    {
        foreach (['Administrador', 'Medico', 'Administrativo', 'Transcriptor'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        $usuarios = [
            ['num_docu' => 'TEST-ADMIN', 'name' => 'TEST Administrador', 'email' => 'test.admin@lidiag.test', 'role' => 'Administrador', 'firma' => false],
            ['num_docu' => 'TEST-MEDICO', 'name' => 'TEST Medico', 'email' => 'test.medico@lidiag.test', 'role' => 'Medico', 'firma' => true],
            ['num_docu' => 'TEST-RECEP', 'name' => 'TEST Recepcion', 'email' => 'test.recepcion@lidiag.test', 'role' => 'Administrativo', 'firma' => false],
            ['num_docu' => 'TEST-TRANS', 'name' => 'TEST Transcriptor', 'email' => 'test.transcriptor@lidiag.test', 'role' => 'Transcriptor', 'firma' => false],
        ];

        foreach ($usuarios as $u) {
            $user = User::firstOrCreate(
                ['email' => $u['email']],
                [
                    'num_docu' => $u['num_docu'],
                    'name' => $u['name'],
                    'password' => Hash::make(self::PASSWORD),
                    'password_changed_at' => now(),
                ]
            );

            // Asegura password conocido y sin cambio forzado aunque ya existiera
            $user->forceFill([
                'password' => Hash::make(self::PASSWORD),
                'password_changed_at' => now(),
            ])->save();

            $user->syncRoles([$u['role']]);

            if ($u['firma'] && ! $user->firma) {
                $png = $this->generarFirmaPng('Dr(a). '.$user->name);
                $filename = 'test_medico_'.$user->id.'_'.Str::random(10).'.png';
                Storage::disk('firmas_medicos')->put($filename, $png);
                $user->firma = 'firmas_medicos/'.$filename;
                $user->save();
            }
        }

        $this->command?->info('Usuarios de prueba: password = "'.self::PASSWORD.'"');
    }

    private function generarFirmaPng(string $texto): string
    {
        $img = imagecreatetruecolor(320, 100);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 20, 20, 60);
        imagefilledrectangle($img, 0, 0, 320, 100, $white);
        imagesetthickness($img, 2);
        imageline($img, 15, 70, 305, 70, $black);
        imagestring($img, 5, 20, 35, substr($texto, 0, 34), $black);
        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }
}
