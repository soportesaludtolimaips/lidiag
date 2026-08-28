<?php

namespace App\Services\Ecografias;

use App\Helpers\HtmlToPhpWordHelper;
use App\Helpers\PdfConverterHelper;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Writer\Word2007;

class PlantillaEcografiaService extends BaseService
{
    private const TEMPLATE_PATH = 'app/plantillas/reporte_ecografia.docx';

    /**
     * Genera un reporte de ecografía en formato Word/PDF utilizando los datos proporcionados,
     * construyendo el documento a partir de una plantilla o desde cero, y convirtiéndolo a PDF.
     *
     * @param  array  $data  Datos necesarios para generar el reporte de ecografía, incluyendo información del médico, paciente y estudio.
     *
     * @returns array Arreglo con el resultado de la operación, incluyendo el estado, mensaje y la URL del archivo generado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function editWord(array $data): array
    {
        $medico = User::find($data['medico_id'] ?? null);
        if (! $medico) {
            return $this->notFound('Médico no encontrado.');
        }

        $data['nombres_medico'] = $medico->name;
        $data['reg_medico'] = $medico->reg_med;
        $data['num_docu_medico'] = $medico->num_docu;
        $data['firma_medico'] = $medico->firma;

        $nombrePaciente = str_replace(['^^^', ' ^', '^'], ['', '', ' '], $data['nom_pacien'] ?? '');

        $rawName = 'ECOGRAFIA-'.($data['tipo_estudio'] ?? 'ECO').'-'.($data['num_docu'] ?? 'SIN_DOC')."-".$nombrePaciente;
        $nomArchivo = Str::of($rawName)->replaceMatches('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\s]+/', '-')->limit(150, '')->toString();

        $outputPath = storage_path('app/reporte_lecturas_ecografias/'.$nomArchivo.'.docx');

        Log::debug('editWord generating DOCX', [
            'nomArchivo' => $nomArchivo,
            'outputPath' => $outputPath,
            'templateExists' => file_exists(storage_path(self::TEMPLATE_PATH)),
            'rawName' => $rawName,
            'nombrePaciente' => $nombrePaciente,
        ]);

        $templatePath = storage_path(self::TEMPLATE_PATH);
        try {
            if (file_exists($templatePath)) {
                $this->fillTemplateWithHtml($templatePath, $data, $nombrePaciente, $outputPath);
            } else {
                $this->buildWordFromData($data, $nombrePaciente, $outputPath);
            }
        } catch (\Exception $e) {
            Log::error('Error generando DOCX: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->error('Error generando plantilla DOCX: '.$e->getMessage(), 400);
        }

        Log::debug('DOCX created', [
            'outputPath' => $outputPath,
            'exists' => file_exists($outputPath),
            'size' => file_exists($outputPath) ? filesize($outputPath) : 0,
        ]);

        $plantillaPdf = $this->convertDocxToPdf($nomArchivo);

        if ($plantillaPdf) {
            @unlink($outputPath);

            return $this->success([
                'message' => 'Plantilla creada correctamente',
                'plantilla' => url('/files/reporte_lecturas_ecografias/'.$nomArchivo.'.pdf'),
            ]);
        }

        @unlink($outputPath);

        return $this->error('No fue posible convertir la plantilla a PDF', 400);
    }

    /**
     * Llena la plantilla de Word con los datos proporcionados y genera un archivo DOCX.
     *
     * @param  string  $templatePath  - La ruta de la plantilla de Word.
     * @param  array  $data  - Los datos necesarios para llenar la plantilla.
     * @param  string  $nombrePaciente  - El nombre del paciente.
     * @param  string  $outputPath  - La ruta donde se guardará el archivo DOCX generado.
     *
     * @throws \Exception - Si ocurre algún error durante el llenado de la plantilla.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    private function fillTemplateWithHtml(string $templatePath, array $data, string $nombrePaciente, string $outputPath): void
    {
        $templateProcessor = new TemplateProcessor($templatePath);

        $fecEstudio = ! empty($data['fec_estudio']) ? Carbon::parse($data['fec_estudio'])->format('d/m/Y') : '';
        $fecLectura = ! empty($data['fechor_lectura']) ? Carbon::parse($data['fechor_lectura'])->format('d/m/Y H:i') : '';
        $fecTrascrito = ! empty($data['fechor_trascrito']) ? Carbon::parse($data['fechor_trascrito'])->format('d/m/Y H:i') : '';

        $productosEcografia = EcografiaProducto::where('ecografia_id', $data['ecografia_id'] ?? null)
            ->with('productos')
            ->get();
        $productosLista = $productosEcografia->map(fn ($item) => '* '.$item->productos->nom_produc)->implode("\n");

        $templateProcessor->setValue('nombre_paciente', $nombrePaciente);
        $templateProcessor->setValue('num_docu', $data['num_docu'] ?? '');
        $templateProcessor->setValue('fec_naci', $data['fec_naci'] ?? '');
        $templateProcessor->setValue('sexo', $data['sexo'] ?? '');
        $templateProcessor->setValue('edad', $data['edad'] ?? '');
        $templateProcessor->setValue('dir', $data['dir'] ?? '');
        $templateProcessor->setValue('tel', $data['tel'] ?? '');
        $templateProcessor->setValue('cel', $data['cel'] ?? '');
        $templateProcessor->setValue('fechor_estudio', $fecEstudio);
        $templateProcessor->setValue('fechor_lectura', $fecLectura);
        $templateProcessor->setValue('fechor_transcripcion', $fecTrascrito);
        $templateProcessor->setValue('atencion', $data['atencion'] ?? '');
        $templateProcessor->setValue('cod_producto', $data['cod_cups'] ?? '');
        $templateProcessor->setValue('nom_producto', $data['nom_produc'] ?? '');
        $templateProcessor->setValue('nom_sede', $data['nom_sede'] ?? '');
        $templateProcessor->setValue('tipo_estudio', $data['tipo_estudio'] ?? '');
        $templateProcessor->setValue('productos', $productosLista);
        $templateProcessor->setValue('eps', $data['admin_salud'] ?? '');
        $templateProcessor->setValue('contrato', $data['contrato'] ?? '');
        $templateProcessor->setValue('nombres_medico', $data['nombres_medico']);
        $templateProcessor->setValue('reg_medico', $data['reg_medico']);
        $templateProcessor->setValue('num_docu_medico', $data['num_docu_medico']);

        $lecturaStyle = ['size' => 10, 'name' => 'Arial'];
        $lecturaTextRun = new TextRun($lecturaStyle);
        HtmlToPhpWordHelper::addToTextRun($lecturaTextRun, $data['lectura'] ?? '', $lecturaStyle);
        $templateProcessor->setComplexValue('lectura', $lecturaTextRun);

        $opinionTextRun = new TextRun($lecturaStyle);
        HtmlToPhpWordHelper::addToTextRun($opinionTextRun, $data['opinion'] ?? '', $lecturaStyle);
        $templateProcessor->setComplexValue('opinion', $opinionTextRun);

        if (! empty($data['firma_medico'])) {
            $firmaPath = storage_path('app/'.$data['firma_medico']);
            if (file_exists($firmaPath)) {
                $templateProcessor->setImageValue('firma_medico', [
                    'path' => $firmaPath,
                    'width' => 100,
                    'height' => 100,
                    'ratio' => false,
                ]);
            }
        }

        $templateProcessor->saveAs($outputPath);
    }

    /**
     * Construye un documento Word desde los datos proporcionados sin utilizar una plantilla.
     *
     * @param  array  $data  - Los datos necesarios para construir el documento Word.
     * @param  string  $nombrePaciente  - El nombre del paciente.
     * @param  string  $outputPath  - La ruta donde se guardará el archivo DOCX generado.
     *
     * @throws \Exception - Si ocurre algún error durante la construcción del documento Word.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2024-06-10
     */
    private function buildWordFromData(array $data, string $nombrePaciente, string $outputPath): void
    {
        $phpWord = new PhpWord;

        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 1200,
            'marginRight' => 1200,
        ]);

        $styleTitle = ['bold' => true, 'size' => 14, 'name' => 'Arial'];
        $styleHeader = ['bold' => true, 'size' => 10, 'name' => 'Arial'];
        $styleNormal = ['size' => 10, 'name' => 'Arial'];

        $section->addText('REPORTE DE ECOGRAFIA', $styleTitle);
        $section->addTextBreak(1);

        $fecEstudio = ! empty($data['fec_estudio']) ? Carbon::parse($data['fec_estudio'])->format('d/m/Y') : '';
        $fecLectura = ! empty($data['fechor_lectura']) ? Carbon::parse($data['fechor_lectura'])->format('d/m/Y H:i') : '';

        $section->addText('Sede: '.($data['nom_sede'] ?? ''), $styleNormal);
        $section->addText('Tipo de estudio: '.($data['tipo_estudio'] ?? ''), $styleNormal);
        $section->addText('Fecha del estudio: '.$fecEstudio, $styleNormal);
        $section->addText('Fecha de lectura: '.$fecLectura, $styleNormal);
        $section->addTextBreak(1);

        $section->addText('DATOS DEL PACIENTE', $styleHeader);
        $section->addText('Nombre: '.$nombrePaciente, $styleNormal);
        $section->addText('Documento: '.($data['num_docu'] ?? ''), $styleNormal);
        $section->addText('Sexo: '.($data['sexo'] ?? '').'  |  Edad: '.($data['edad'] ?? ''), $styleNormal);
        $section->addText('Fecha nacimiento: '.($data['fec_naci'] ?? ''), $styleNormal);
        $section->addText('Direccion: '.($data['dir'] ?? ''), $styleNormal);
        $section->addText('Telefono: '.($data['tel'] ?? '').'  |  Celular: '.($data['cel'] ?? ''), $styleNormal);
        $section->addText('Atencion: '.($data['atencion'] ?? ''), $styleNormal);
        $section->addText('EPS: '.($data['admin_salud'] ?? ''), $styleNormal);
        $section->addText('Contrato: '.($data['contrato'] ?? ''), $styleNormal);
        $section->addTextBreak(1);

        $section->addText('DATOS DEL ESTUDIO', $styleHeader);
        $section->addText('CUPS: '.($data['cod_cups'] ?? ''), $styleNormal);
        $section->addText('Producto: '.($data['nom_produc'] ?? ''), $styleNormal);

        $productosEcografia = EcografiaProducto::where('ecografia_id', $data['ecografia_id'] ?? null)
            ->with('productos')
            ->get();

        if ($productosEcografia->isNotEmpty()) {
            $section->addText('Productos:', $styleNormal);
            foreach ($productosEcografia as $item) {
                $section->addText('• '.$item->productos->nom_produc, $styleNormal);
            }
        }
        $section->addTextBreak(1);

        if (! empty($data['lectura'])) {
            $section->addText('LECTURA', $styleHeader);
            HtmlToPhpWordHelper::addHtmlToSection($section, $data['lectura'], $styleNormal);
            $section->addTextBreak(1);
        }

        if (! empty($data['opinion'])) {
            $section->addText('OPINION', $styleHeader);
            HtmlToPhpWordHelper::addHtmlToSection($section, $data['opinion'], $styleNormal);
            $section->addTextBreak(1);
        }

        $section->addTextBreak(2);

        $section->addText('Medico: '.($data['nombres_medico'] ?? ''), $styleNormal);
        $section->addText('Reg. Medico: '.($data['reg_medico'] ?? ''), $styleNormal);
        $section->addText('Doc. Medico: '.($data['num_docu_medico'] ?? ''), $styleNormal);

        if (! empty($data['firma_medico'])) {
            $firmaPath = storage_path('app/'.$data['firma_medico']);
            if (file_exists($firmaPath)) {
                $section->addImage($firmaPath, [
                    'width' => 100,
                    'height' => 100,
                    'ratio' => false,
                ]);
            }
        }

        $writer = new Word2007($phpWord);
        $writer->save($outputPath);
    }

    /**
     * Convierte un archivo DOCX previamente generado a formato PDF utilizando el helper
     * de conversión, verificando la existencia del origen y eliminando el DOCX temporal.
     *
     * @param  string  $nomPlantilla  Nombre del archivo DOCX (sin extensión) que se desea convertir a PDF.
     *
     * @returns bool Retorna true si la conversión fue exitosa, false en caso contrario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-08-28
     */
    public function convertDocxToPdf(string $nomPlantilla): bool
    {
        $inputPath = storage_path("app/reporte_lecturas_ecografias/{$nomPlantilla}.docx");
        $outputPath = storage_path("app/reporte_lecturas_ecografias/{$nomPlantilla}.pdf");

        if (! file_exists($inputPath)) {
            Log::error("Archivo DOCX no encontrado: {$inputPath}");

            return false;
        }

        $result = PdfConverterHelper::convert($inputPath, $outputPath);

        if (! $result) {
            return false;
        }

        @unlink($inputPath);

        return true;
    }
}
