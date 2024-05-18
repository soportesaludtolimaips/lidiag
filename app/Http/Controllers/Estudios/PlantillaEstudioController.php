<?php

namespace App\Http\Controllers\Estudios;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class PlantillaEstudioController extends Controller
{
    public function editWord(Request $request)
    {
        // Ruta del archivo de plantilla
        $filePath = storage_path('app/public/plantillaEspinal.docx');

        // Cargar el archivo de plantilla
        $templateProcessor = new TemplateProcessor($filePath);

        // Obtener datos desde la solicitud
        $templateProcessor->setValue('nombre_paciente', $request->nom_pacien);
        $templateProcessor->setValue('num_docu', $request->num_docu);
        $templateProcessor->setValue('sexo', $request->sexo);
        $templateProcessor->setValue('edad', $request->edad);
        $templateProcessor->setValue('eps', $request->eps);
        $templateProcessor->setValue('contrato', $request->contrato);

        $fecEstudio = Carbon::parse($request->fec_estudio)->format('d/m/Y');
        $fecLectura = Carbon::parse($request->fechor_lectura)->format('d/m/Y H:i');
        $fecTrascrito = Carbon::parse($request->fechor_trascrito)->format('d/m/Y H:i');

        $templateProcessor->setValue('fechor_estudio', $fecEstudio);
        $templateProcessor->setValue('fechor_lectura', $fecLectura);
        $templateProcessor->setValue('fechor_transcipcion', $fecTrascrito);

        $templateProcessor->setValue('atencion', $request->atencion);
        $lectura = htmlspecialchars($request->lectura);
        $templateProcessor->setValue('lectura', $lectura);

        $templateProcessor->setValue('nombres_medico', $request->medico);
        $templateProcessor->setValue('reg_medico', $request->reg_med);
        $templateProcessor->setValue('num_docu_medico', $request->num_docu_medi);

        // Manejar la carga de la imagen
        if ($request->firma) {
            // Insertar la imagen en el archivo de Word
            $templateProcessor->setImageValue('firma_medico', [
                'path' => storage_path('app/firmas_medicos/39.jpg'),
                'width' => 100,
                'height' => 100,
                'ratio' => false
            ]);
        }

        // Ruta para guardar el archivo editado
        $outputPath = storage_path('app/public/edited_document.docx');
        $templateProcessor->saveAs($outputPath);

        // Crear una instancia de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $dompdf = new Dompdf($options);

        // Cargar el HTML generado a partir del archivo de Word
        $html = file_get_contents($outputPath);

        // Convertir HTML a PDF
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Ruta para guardar el archivo PDF
        $pdfOutputPath = storage_path('app/public/converted_document.pdf');

        // Guardar el archivo PDF
        file_put_contents($pdfOutputPath, $dompdf->output());

        // Eliminar el archivo de Word temporal
        unlink($outputPath);

        return response()->download($pdfOutputPath);
    }
}
