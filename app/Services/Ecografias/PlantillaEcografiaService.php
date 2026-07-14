<?php

namespace App\Services\Ecografias;

use App\Models\Configuracion\ConfigSede;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PlantillaEcografiaService extends BaseService
{
    public function editWord(array $data): array
    {
        $sede = ConfigSede::find($data['sede_id'] ?? null);
        if (! $sede) {
            return $this->notFound('Sede no encontrada.');
        }

        if (! $sede->plantilla_ecografia) {
            return $this->error('La plantilla para ecografias de la sede '.$sede->nom_sede.' no está  configurada, por favor consulte con el administrador del sistema', 402);
        }

        $medico = User::find($data['medico_id'] ?? null);
        if (! $medico) {
            return $this->notFound('Médico no encontrado.');
        }

        $data['nombres_medico'] = $medico->name;
        $data['reg_medico'] = $medico->reg_med;
        $data['num_docu_medico'] = $medico->num_docu;
        $data['firma_medico'] = $medico->firma;

        $filePath = storage_path('app/plantillas_lecturas/'.$sede->plantilla_ecografia);

        $templateProcessor = new TemplateProcessor($filePath);

        $nombrePaciente = str_replace('^^^', '', $data['nom_pacien'] ?? '');
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $templateProcessor->setValue('nombre_paciente', $nombrePaciente);
        $templateProcessor->setValue('num_docu', $data['num_docu'] ?? '');
        $templateProcessor->setValue('fec_naci', $data['fec_naci'] ?? '');
        $templateProcessor->setValue('sexo', $data['sexo'] ?? '');
        $templateProcessor->setValue('edad', $data['edad'] ?? '');
        $templateProcessor->setValue('dir', $data['dir'] ?? '');
        $templateProcessor->setValue('tel', $data['tel'] ?? '');
        $templateProcessor->setValue('cel', $data['cel'] ?? '');

        $fecEstudio = ! empty($data['fec_estudio']) ? Carbon::parse($data['fec_estudio'])->format('d/m/Y') : '';
        $fecLectura = ! empty($data['fechor_lectura']) ? Carbon::parse($data['fechor_lectura'])->format('d/m/Y H:i') : '';
        $fecTrascrito = ! empty($data['fechor_trascrito']) ? Carbon::parse($data['fechor_trascrito'])->format('d/m/Y H:i') : '';

        $templateProcessor->setValue('fechor_estudio', $fecEstudio);
        $templateProcessor->setValue('fechor_lectura', $fecLectura);
        $templateProcessor->setValue('fechor_transcipcion', $fecTrascrito);

        $templateProcessor->setValue('atencion', $data['atencion'] ?? '');
        $templateProcessor->setValue('cod_producto', $data['cod_cups'] ?? '');
        $templateProcessor->setValue('nom_producto', $data['nom_produc'] ?? '');

        $templateProcessor->setValue('nom_sede', $data['nom_sede'] ?? '');

        $templateProcessor->setValue('tipo_estudio', $data['tipo_estudio'] ?? '');
        $templateProcessor->setValue('lectura', $data['lectura'] ?? '');
        $templateProcessor->setValue('opinion', $data['opinion'] ?? '');

        $productosEcografia = EcografiaProducto::where('ecografia_id', $data['ecografia_id'] ?? null)
            ->with('productos')
            ->get();

        $productosLista = $productosEcografia->map(function ($item) {
            return '* '.$item->productos->nom_produc;
        })->implode("\n");

        $templateProcessor->setValue('productos', $productosLista);

        $templateProcessor->setValue('eps', $data['admin_salud'] ?? '');
        $templateProcessor->setValue('contrato', $data['contrato'] ?? '');

        $templateProcessor->setValue('nombres_medico', $data['nombres_medico']);
        $templateProcessor->setValue('reg_medico', $data['reg_medico']);
        $templateProcessor->setValue('num_docu_medico', $data['num_docu_medico']);

        if (! empty($data['firma_medico'])) {
            $templateProcessor->setImageValue('firma_medico', [
                'path' => storage_path('app/'.$data['firma_medico']),
                'width' => 100,
                'height' => 100,
                'ratio' => false,
            ]);
        }

        $rawName = 'ECOGRAFIA-'.($data['tipo_estudio'] ?? 'ECO').'-'.($data['num_docu'] ?? 'SIN_DOC')."-".$nombrePaciente;
        $nomArchivo = Str::of($rawName)->replaceMatches('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|]+/', '-')->limit(150, '')->toString();

        $outputPath = storage_path('app/reporte_lecturas/'.$nomArchivo.'.docx');
        $templateProcessor->saveAs($outputPath);

        $plantillaPdf = $this->convertDocxToPdf($nomArchivo);

        if ($plantillaPdf) {
            @unlink($outputPath);

            return $this->success([
                'messaje' => 'Plantilla creada correctament',
                'plantilla' => asset('storage/reporte_lecturas/'.$nomArchivo.'.pdf'),
            ]);
        }

        try {
            @unlink($outputPath);
        } catch (\Exception $e) {
        }

        return $this->error('No fue posible convertir la plantilla a PDF', 400);
    }

    public function convertDocxToPdf(string $nomPlantilla): bool
    {
        $inputPath = storage_path("app/reporte_lecturas/{$nomPlantilla}.docx");
        $outputPath = storage_path("app/public/reporte_lecturas/{$nomPlantilla}.pdf");

        if (! file_exists($inputPath)) {
            Log::error("Archivo DOCX no encontrado: {$inputPath}");

            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $unoconvPath = 'C:\unoconv\unoconv.bat';
        } else {
            $unoconvPath = '/usr/bin/unoconv';
        }

        if (! file_exists($unoconvPath)) {
            Log::error("unoconv no encontrado en la ruta: {$unoconvPath}");

            return false;
        }

        $filename = pathinfo($nomPlantilla, PATHINFO_FILENAME);
        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            throw new \InvalidArgumentException('Nombre de archivo inválido');
        }

        try {
            $outDir = dirname($outputPath);
            if (! is_dir($outDir)) {
                @mkdir($outDir, 0775, true);
            }

            $process = new Process([$unoconvPath, '-f', 'pdf', '-o', $outputPath, $inputPath]);
            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('unoconv error: '.$process->getErrorOutput());
                throw new ProcessFailedException($process);
            }
        } catch (\Exception $e) {
            Log::error("Error durante la conversión: ".$e->getMessage());

            return false;
        }

        if (! file_exists($outputPath)) {
            Log::error("PDF no generado: {$outputPath}");

            return false;
        }

        $encryptedPath = $outputPath.'.enc';
        file_put_contents($encryptedPath, encrypt(file_get_contents($outputPath)));
        @unlink($outputPath);

        return true;
    }
}
