<?php

namespace Database\Seeders;

use App\Models\Consentimientos\ConsentimientoDocumento;
use App\Models\Consentimientos\ConsentimientoDocumentoAuditoria;
use App\Models\Consentimientos\ConsentimientoPlantilla;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Deja el flujo completo listo para pruebas:
 *  - Carga una firma PNG de prueba a un médico y al administrador (users.firma).
 *  - Crea documentos en estado PENDIENTE_MEDICO (con firma de paciente ya
 *    aplicada) asignados a esos usuarios, para probar firma médica + PDF +
 *    bloqueo de lecturas sin tener que recorrer todo el flujo manual.
 *
 * Requiere que ConsentimientosDemoSeeder haya corrido (plantilla + pacientes).
 * Idempotente.
 */
class ConsentimientosDemoFlujoSeeder extends Seeder
{
    private const MODULO_RX = 1;

    private const FIRMAS_MEDICO_DISK = 'firmas_medicos';

    private const FIRMAS_PACIENTE_DISK = 'firmas_consntimientos';

    public function run(): void
    {
        $plantilla = ConsentimientoPlantilla::query()
            ->where('config_modulo_id', self::MODULO_RX)
            ->where('vigente', true)
            ->first();

        if (! $plantilla) {
            $this->command?->warn('No hay plantilla vigente. Corre primero ConsentimientosDemoSeeder.');

            return;
        }

        $admin = User::query()->where('email', 'administrador@administrador.com')->first();
        $medico = User::query()->role('Medico')->orderBy('id')->first();

        foreach (array_filter([$admin, $medico]) as $i => $usuario) {
            $this->asignarFirmaMedico($usuario);
            $this->crearDocumentoPendienteMedico($plantilla, $usuario, $admin ?? $usuario, (int) $i);
        }
    }

    private function asignarFirmaMedico(User $usuario): void
    {
        if ($usuario->firma && Storage::disk(self::FIRMAS_MEDICO_DISK)->exists(basename($usuario->firma))) {
            return; // ya tiene firma válida
        }

        $png = $this->generarFirmaPng('Dr(a). '.$usuario->name);
        $filename = 'medico_'.$usuario->id.'_'.Str::random(12).'.png';
        Storage::disk(self::FIRMAS_MEDICO_DISK)->put($filename, $png);

        $usuario->firma = self::FIRMAS_MEDICO_DISK.'/'.$filename;
        $usuario->save();
    }

    private function crearDocumentoPendienteMedico(
        ConsentimientoPlantilla $plantilla,
        User $medico,
        User $generadoPor,
        int $idx
    ): void {
        $numDocu = ['111222333', '444555666', '777888999'][$idx % 3];

        $yaExiste = ConsentimientoDocumento::query()
            ->where('medico_id', $medico->id)
            ->where('estado', 'PENDIENTE_MEDICO')
            ->where('num_docu', $numDocu)
            ->exists();
        if ($yaExiste) {
            return;
        }

        // Firma del paciente (PNG cifrado en disco)
        $firmaPng = $this->generarFirmaPng('Paciente '.$numDocu);
        $firmaFile = 'Paciente_'.Str::random(20).'.png.enc';
        Storage::disk(self::FIRMAS_PACIENTE_DISK)->put($firmaFile, encrypt($firmaPng));

        $doc = ConsentimientoDocumento::create([
            'origen_tipo' => 'MANUAL',
            'producto_id' => (int) $plantilla->producto_id,
            'config_modulo_id' => self::MODULO_RX,
            'plantilla_id' => $plantilla->id,
            'plantilla_version' => (int) $plantilla->version,
            'plantilla_html' => (string) $plantilla->html,
            'num_docu' => $numDocu,
            'nombres' => 'PACIENTE',
            'apellidos' => 'DEMO '.$numDocu,
            'medico_id' => $medico->id,
            'vigente' => true,
            'vigente_key' => 1,
            'estado' => 'PENDIENTE_MEDICO',
            'requiere_firma_medico' => true,
            'fecha_expiracion' => now()->addDay(),
            'firmante_tipo' => 'PACIENTE',
            'firma_paciente_path' => self::FIRMAS_PACIENTE_DISK.'/'.$firmaFile,
            'firma_paciente_at' => now(),
            'ip_firma_paciente' => '127.0.0.1',
            'generado_por_id' => (int) $generadoPor->id,
            'ip_generado' => '127.0.0.1',
        ]);

        ConsentimientoDocumentoAuditoria::create([
            'documento_id' => $doc->id,
            'usuario_id' => $generadoPor->id,
            'accion' => 'create',
            'descripcion' => 'Documento demo pendiente de firma médica',
            'fecha_accion' => now(),
        ]);
    }

    /** Genera un PNG simple con texto que simula una firma. */
    private function generarFirmaPng(string $texto): string
    {
        $img = imagecreatetruecolor(320, 100);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 20, 20, 60);
        imagefilledrectangle($img, 0, 0, 320, 100, $white);
        imagesetthickness($img, 2);
        // trazo decorativo
        imageline($img, 15, 70, 305, 70, $black);
        imagestring($img, 5, 20, 35, substr($texto, 0, 34), $black);

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        return $png;
    }
}
