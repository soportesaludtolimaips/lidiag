<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;

class PlantillaEstudioController extends Controller
{
    /**
     * Genera un documento Word con la lectura del estudio y lo convierte a PDF.
     *
     * Flujo del proceso:
     * 1. Busca la plantilla_rx asociada a la sede.
     * 2. Carga la plantilla_rx Word con `TemplateProcessor`.
     * 3. Reemplaza variables simples (paciente, fechas, médico, producto).
     * 4. Procesa el texto de la lectura (negritas para títulos clave).
     * 5. Inserta la firma del médico si existe.
     * 6. Guarda el archivo .docx temporal.
     * 7. Convierte el .docx a PDF usando `convertDocxToPdf`.
     * 8. Elimina el .docx temporal.
     *
     * @param  Request  $request  Datos del estudio y lectura.
     * @return JsonResponse Confirmación con nombre del PDF o error.
     */
    public function editWord(Request $request)
    {
        /**
         * Buscarmos la sede para saber cual es la plantilla_rx
         */
        $sede = ConfigSede::find($request->sede_id);
        if (! $sede->plantilla_rx) {
            return response()->json(['message' => 'La plantilla_rx para la sede '.$sede->nom_sede.' no está  configurada, por favor consulte con el administrador del sistema'], 402);
        }

        // Ruta del archivo de plantilla_rx
        $filePath = storage_path('app/plantillas_lecturas/'.$sede->plantilla_rx);

        // Cargar el archivo de plantilla_rx
        $templateProcessor = new TemplateProcessor($filePath);
        // Obtener datos desde la solicitud
        $nombrePaciente = str_replace('^^^', '', $request->nom_pacien);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        $templateProcessor->setValue('nombre_paciente', $nombrePaciente);
        $templateProcessor->setValue('num_docu', $request->num_docu);
        $templateProcessor->setValue('sexo', $request->sexo);
        $templateProcessor->setValue('edad', $request->edad);
        $templateProcessor->setValue('eps', $request->eps);
        $templateProcessor->setValue('contrato', $request->contrato);
        $fecEstudio = $request->fec_estudio ? Carbon::parse($request->fec_estudio)->format('d/m/Y') : '';
        $fecLectura = $request->fechor_lectura ? Carbon::parse($request->fechor_lectura)->format('d/m/Y H:i') : '';
        $fecTrascrito = $request->fechor_trascrito ? Carbon::parse($request->fechor_trascrito)->format('d/m/Y H:i') : '';

        $templateProcessor->setValue('fechor_estudio', $fecEstudio);
        $templateProcessor->setValue('fechor_lectura', $fecLectura);
        $templateProcessor->setValue('fechor_transcipcion', $fecTrascrito);

        if ($request->atencion == 'null') {
            $templateProcessor->setValue('atencion', '');
        } else {
            $templateProcessor->setValue('atencion', $request->atencion);
        }

        // Crear un TextRun para manejar el texto enriquecido
        $textRun = new TextRun;

        // Agregar el texto con el formato necesario
        foreach (explode("\n", $request->lectura) as $line) {
            if (
                strpos($line, 'Atención:') !== false || strpos($line, 'Fecha y Hora:') !== false ||
                strpos($line, 'HALLAZGOS:') !== false || strpos($line, 'IMPRESIÓN DIAGNÓSTICA:') !== false ||
                strpos($line, 'CONCLUSIÓN:') !== false || strpos($line, $request->nom_produc) !== false
            ) {
                $textRun->addText($line, ['bold' => true]);
            } else {
                $textRun->addText($line);
            }
            $textRun->addTextBreak();
        }

        // Usar setComplexValue para insertar el TextRun en la plantilla_rx
        $templateProcessor->setComplexValue('lectura', $textRun);

        $templateProcessor->setValue('nom_sede', $request->nom_sede);

        $templateProcessor->setValue('cod_producto', $request->cod_cups);
        $templateProcessor->setValue('nom_producto', $request->nom_produc);

        $templateProcessor->setValue('nombres_medico', $request->nombres_medico);
        $templateProcessor->setValue('reg_medico', $request->reg_medico);
        $templateProcessor->setValue('num_docu_medico', $request->num_docu_medico);

        // Manejar la carga de la imagen
        if ($request->firma_medico) {
            // Insertar la imagen en el archivo de Word
            $templateProcessor->setImageValue('firma_medico', [
                'path' => storage_path('app/'.$request->firma_medico),
                'width' => 100,
                'height' => 100,
                'ratio' => false,
            ]);
        }

        // Ruta para guardar el archivo editado
        $nomArchivo = $request->estudio_producto_id."-".$request->num_docu."-".$nombrePaciente;
        $outputPath = storage_path('app/reporte_lecturas/'.$nomArchivo.'.docx');
        $templateProcessor->saveAs($outputPath);

        $plantilla_rxPdf = $this->convertDocxToPdf($nomArchivo);
        if ($plantilla_rxPdf === true) {

            unlink($outputPath);

            return response()->json(
                [
                    'messaje' => 'Plantilla creada correctament',
                    'plantilla' => $nomArchivo.".pdf",
                ],
                200
            );
        } else {
            // Mantener compatibilidad: error 400 con mensaje claro
            return response()->json(['message' => 'No fue posible convertir la plantilla_rx a PDF'], 400);
        }
    }

    /**
     * Convierte un archivo Word (.docx) a PDF usando unoconv.
     *
     * @param  string  $nomPlantilla  Nombre base del archivo (sin extensión).
     * @return string|null Ruta del archivo PDF generado o null si falla.
     */
    public function convertDocxToPdf($nomPlantilla): bool
    {
        // Rutas de los archivos de entrada y salida
        $sourceFile = storage_path('app/reporte_lecturas/'.$nomPlantilla.'.docx');
        $destinationFile = storage_path('app/reporte_lecturas/'.$nomPlantilla.'.pdf');

        // Comando para convertir el archivo .docx a PDF utilizando unoconv
        $command = escapeshellcmd('C:\unoconv\unoconv -f pdf -o '.escapeshellarg($destinationFile).' '.escapeshellarg($sourceFile));

        // Ejecutar el comando
        $output = shell_exec($command);

        // Verificar si la conversión fue exitosa
        if (! file_exists($destinationFile)) {
            // Registrar el error
            \Log::error('Error converting DOCX to PDF: '.$output);

            return false;
        }

        return true;
    }
}
