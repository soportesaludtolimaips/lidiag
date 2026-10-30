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
    /**
     * Genera un reporte Word/PDF a partir de la plantilla configurada para la sede
     *
     * @param  array  $data  Datos completos: sede_id, paciente, lectura, médico, producto, fechas
     *
     * @returns array Respuesta con el nombre del archivo PDF generado
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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
        $safePaciente = trim(preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|]+/', '', $nombrePaciente));
        $safePaciente = preg_replace('/\\s+/', ' ', $safePaciente);

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
        $template->setValue('fechor_transcripcion', $fecTrascrito);

        $template->setValue('atencion', ($data['atencion'] ?? '') === 'null' ? '' : ($data['atencion'] ?? ''));

        $textRun = new TextRun;
        $lecturaSanitizada = $this->sanitizarTextoParaDocx($data['lectura'] ?? '');
        foreach (explode("\n", $lecturaSanitizada) as $line) {
            $line = trim($line);
            if ($line === '') {
                $textRun->addTextBreak();

                continue;
            }

            $boldKeywords = ['Atención:', 'Fecha y Hora:', 'HALLAZGOS:', 'IMPRESIÓN DIAGNÓSTICA:', 'CONCLUSIÓN:', $data['nom_produc'] ?? '__NOMATCH__'];
            $isBold = false;
            foreach ($boldKeywords as $keyword) {
                if (stripos($line, $keyword) !== false) {
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

        $nomArchivo = trim(($data['estudio_producto_id'] ?? 'temp').'-'.($data['num_docu'] ?? '0').'-'.$safePaciente);
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
            'plantilla' => $nomArchivo.'.pdf',
        ], 'Plantilla creada correctamente');
    }

    /**
     * Convierte un archivo DOCX a PDF utilizando el helper de conversión
     *
     * @param  string  $nomPlantilla  Nombre base del archivo (sin extensión)
     *
     * @returns bool true si la conversión fue exitosa, false en caso contrario
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
    public function convertDocxToPdf(string $nomPlantilla): bool
    {
        $sourceFile = storage_path('app/reporte_lecturas/'.$nomPlantilla.'.docx');
        $destinationFile = storage_path('app/reporte_lecturas/'.$nomPlantilla.'.pdf');

        return PdfConverterHelper::convert($sourceFile, $destinationFile);
    }

    /**
     * Elimina los archivos de reporte PDF y DOCX generados
     *
     * @param  string  $reportePath  Nombre del archivo de reporte (con extensión PDF)
     *
     * @returns void
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
     */
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

    /**
     * Sanitiza el texto de lectura eliminando entidades HTML corruptas para OpenXML.
     *
     * Flujo del proceso:
     * 1. Convierte saltos de línea HTML en saltos de línea planos \n.
     * 2. Reemplaza entidades &nbsp; y &#160; por espacios estándar.
     * 3. Decodifica entidades HTML a caracteres UTF-8 reales.
     * 4. Normaliza espacios de no separación UTF-8 (\u{00A0}) a espacios regulares.
     * 5. Elimina etiquetas HTML restantes.
     *
     * @param  string  $texto  Texto de la lectura clínico
     * @return string Texto plano limpio y seguro para el archivo DOCX/XML
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-30
     */
    private function sanitizarTextoParaDocx(string $texto): string
    {
        if (trim($texto) === '') {
            return '';
        }

        $texto = preg_replace('/<\s*br\s*\/?>/i', "\n", $texto);
        $texto = preg_replace('/<\/\s*(?:p|div|h[1-6])\s*>/i', "\n", $texto);
        $texto = str_ireplace(['&nbsp;', '&#160;'], ' ', $texto);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\x{00A0}/u', ' ', $texto);
        $texto = strip_tags($texto);
        $texto = preg_replace("/\r\n|\r/", "\n", $texto);

        return trim($texto);
    }
}
