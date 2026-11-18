<?php

namespace App\Services\Ecografias;

use App\Helpers\HtmlToPhpWordHelper;
use App\Helpers\PdfConverterHelper;
use App\Models\Configuracion\ConfigSede;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use App\Services\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;

class PlantillaEcografiaService extends BaseService
{
    /**
     * Genera un reporte de ecografía en formato Word/PDF utilizando la plantilla obligatoria
     * configurada en la sede hospitalaria correspondiente, y convirtiéndolo a PDF.
     *
     * @param  array  $data  Datos necesarios para generar el reporte de ecografía, incluyendo información del médico, paciente, sede y estudio.
     *
     * @returns array Arreglo con el resultado de la operación, incluyendo el estado, mensaje y la URL del archivo generado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-18
     */
    public function editWord(array $data): array
    {
        $sede = ConfigSede::find($data['sede_id'] ?? null);
        if (! $sede || empty($sede->plantilla_ecografia)) {
            return $this->error(
                'La plantilla de ecografías para la sede '.($sede->nom_sede ?? 'N/A').' no está configurada.',
                402
            );
        }

        $plantillaArchivo = $sede->plantilla_ecografia;
        $templatePath = str_starts_with($plantillaArchivo, 'plantillas_lecturas/')
            ? storage_path('app/'.$plantillaArchivo)
            : storage_path('app/plantillas_lecturas/'.$plantillaArchivo);

        if (! file_exists($templatePath)) {
            return $this->error(
                "Archivo de plantilla de ecografías no encontrado en disco ({$plantillaArchivo}).",
                404
            );
        }

        $medico = User::find($data['medico_id'] ?? null);
        if (! $medico) {
            return $this->notFound('Médico no encontrado.');
        }

        $data['nombres_medico'] = $medico->name;
        $data['reg_medico'] = $medico->reg_med;
        $data['num_docu_medico'] = $medico->num_docu;
        $data['firma_medico'] = $medico->firma;

        $nombrePaciente = str_replace(['^^^', ' ^', '^'], ['', '', ' '], $data['nom_pacien'] ?? '');

        $rawName = 'ECOGRAFIA-'.($data['tipo_estudio'] ?? 'ECO').'-'.($data['num_docu'] ?? 'SIN_DOC').'-'.$nombrePaciente;
        $nomArchivo = Str::of($rawName)->replaceMatches('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\s]+/', '-')->limit(150, '')->toString();

        $outputPath = storage_path('app/reporte_lecturas_ecografias/'.$nomArchivo.'.docx');

        Log::debug('editWord generating DOCX from sede plantilla', [
            'sede_id' => $sede->id,
            'nom_sede' => $sede->nom_sede,
            'plantilla_ecografia' => $plantillaArchivo,
            'nomArchivo' => $nomArchivo,
            'outputPath' => $outputPath,
            'rawName' => $rawName,
            'nombrePaciente' => $nombrePaciente,
        ]);

        try {
            $this->fillTemplateWithHtml($templatePath, $data, $nombrePaciente, $outputPath);
        } catch (\Throwable $e) {
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
     * Convierte un archivo DOCX previamente generado a formato PDF utilizando el helper
     * de conversión, verificando la existencia del origen y eliminando el DOCX temporal.
     *
     * @param  string  $nomPlantilla  Nombre del archivo DOCX (sin extensión) que se desea convertir a PDF.
     *
     * @returns bool Retorna true si la conversión fue exitosa, false en caso contrario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-07-14
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
