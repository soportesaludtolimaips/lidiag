<?php

namespace App\Services\Estudios;

use App\Helpers\PdfConverterHelper;
use App\Models\Configuracion\ConfigSede;
use App\Services\BaseService;
use Carbon\Carbon;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;

class PlantillaEstudioService extends BaseService
{
    public function editWord(array $data): array
    {
        $sede = ConfigSede::find($data['sede_id'] ?? null);
        if (! $sede || ! $sede->plantilla_rx) {
            return $this->error(
                'La plantilla para la sede '.($sede->nom_sede ?? 'N/A').' no está configurada.',
                402
            );
        }

        $filePath = storage_path('app/plantillas_lecturas/'.$sede->plantilla_rx);
        if (! file_exists($filePath)) {
            return $this->error('Archivo de plantilla no encontrado.', 404);
        }

        $template = new TemplateProcessor($filePath);

        $nombrePaciente = str_replace(['^^^', ' ^', '^'], ['', '', ' '], $data['nom_pacien'] ?? '');

        $template->setValue('nombre_paciente', $nombrePaciente);
        $template->setValue('num_docu', $data['num_docu'] ?? '');
        $template->setValue('sexo', $data['sexo'] ?? '');
        $template->setValue('edad', $data['edad'] ?? '');
        $template->setValue('eps', $data['eps'] ?? '');
        $template->setValue('contrato', $data['contrato'] ?? '');

        $fecEstudio = ! empty($data['fec_estudio']) ? Carbon::parse($data['fec_estudio'])->format('d/m/Y') : '';
        $fecLectura = ! empty($data['fechor_lectura']) ? Carbon::parse($data['fechor_lectura'])->format('d/m/Y H:i') : '';
        $fecTrascrito = ! empty($data['fechor_trascrito']) ? Carbon::parse($data['fechor_trascrito'])->format('d/m/Y H:i') : '';

        $template->setValue('fechor_estudio', $fecEstudio);
        $template->setValue('fechor_lectura', $fecLectura);
        $template->setValue('fechor_transcipcion', $fecTrascrito);

        $template->setValue('atencion', ($data['atencion'] ?? '') === 'null' ? '' : ($data['atencion'] ?? ''));

        $textRun = new TextRun;
        foreach (explode("\n", $data['lectura'] ?? '') as $line) {
            $boldKeywords = ['Atención:', 'Fecha y Hora:', 'HALLAZGOS:', 'IMPRESIÓN DIAGNÓSTICA:', 'CONCLUSIÓN:', $data['nom_produc'] ?? '__NOMATCH__'];
            $isBold = false;
            foreach ($boldKeywords as $keyword) {
                if (strpos($line, $keyword) !== false) {
                    $isBold = true;
                    break;
                }
            }
            $textRun->addText($line, $isBold ? ['bold' => true] : []);
            $textRun->addTextBreak();
        }
        $template->setComplexValue('lectura', $textRun);

        $template->setValue('nom_sede', $data['nom_sede'] ?? '');
        $template->setValue('cod_producto', $data['cod_cups'] ?? '');
        $template->setValue('nom_producto', $data['nom_produc'] ?? '');
        $template->setValue('nombres_medico', $data['nombres_medico'] ?? '');
        $template->setValue('reg_medico', $data['reg_medico'] ?? '');
        $template->setValue('num_docu_medico', $data['num_docu_medico'] ?? '');

        if (! empty($data['firma_medico'])) {
            $firmaPath = storage_path('app/'.$data['firma_medico']);
            if (file_exists($firmaPath)) {
                $template->setImageValue('firma_medico', [
                    'path' => $firmaPath,
                    'width' => 100,
                    'height' => 100,
                    'ratio' => false,
                ]);
            }
        }

        $nomArchivo = ($data['estudio_producto_id'] ?? 'temp').'-'.($data['num_docu'] ?? '0').'-'.$nombrePaciente;
        $outputDocx = storage_path('app/reporte_lecturas/'.$nomArchivo.'.docx');
        $template->saveAs($outputDocx);

        $converted = $this->convertDocxToPdf($nomArchivo);
        if (! $converted) {
            return $this->error('No fue posible convertir la plantilla a PDF', 400);
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            if (@unlink($outputDocx) !== false) {
                break;
            }
            usleep(250000);
        }

        return $this->success([
            'output_name' => $nomArchivo.'.pdf',
        ], 'Plantilla creada correctamente');
    }

    public function convertDocxToPdf(string $nomPlantilla): bool
    {
        $sourceFile = storage_path('app/reporte_lecturas/'.$nomPlantilla.'.docx');
        $destinationFile = storage_path('app/reporte_lecturas/'.$nomPlantilla.'.pdf');

        return PdfConverterHelper::convert($sourceFile, $destinationFile);
    }

    public function eliminarReporte(string $reportePath): void
    {
        $paths = [
            storage_path('app/reporte_lecturas/'.$reportePath),
            storage_path('app/reporte_lecturas/'.str_replace('.pdf', '.docx', $reportePath)),
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}
