<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\TemplateProcessor;
use Carbon\Carbon;
use PhpOffice\PhpWord\Element\TextRun;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Response;


class PlantillaEcografiaController extends Controller
{
    public function editWord(Request $request)
    {
        /**
         * Buscamos la sede para saber cual es la plantilla
         */
        $filePath = '';
        
        $sede = ConfigSede::find($request->sede_id);

        if (!$sede->plantilla_ecografia) {
            return response()->json(['message' => 'La plantilla para ecografias de la sede ' . $sede->nom_sede . ' no está  configurada, por favor consulte con el administrador del sistema'], 402);
        }elseif ($sede->plantilla_ecografia) {
            $medico = User::find($request->medico_id);
            $request->merge(['nombres_medico' => $medico->name]);
            $request->merge(['reg_medico' => $medico->reg_med]);
            $request->merge(['num_docu_medico' => $medico->num_docu]);
            $request->merge(['firma_medico' => $medico->firma]);

            // Ruta del archivo de plantilla
            $filePath = storage_path('app/plantillas_lecturas/' . $sede->plantilla_ecografia);
        }

        // Cargar el archivo de plantilla
        $templateProcessor = new TemplateProcessor($filePath);

        // Obtener datos desde la solicitud
        $nombrePaciente = str_replace('^^^', '', $request->nom_pacien);
        $nombrePaciente = str_replace(' ^', '', $nombrePaciente);
        $nombrePaciente = str_replace('^', ' ', $nombrePaciente);

        // Actualizar los datos del paciente
        $templateProcessor->setValue('nombre_paciente', $nombrePaciente);
        $templateProcessor->setValue('num_docu', $request->num_docu);
        $templateProcessor->setValue('fec_naci', $request->fec_naci);
        $templateProcessor->setValue('sexo', $request->sexo);
        $templateProcessor->setValue('edad', $request->edad);
        $templateProcessor->setValue('dir', $request->dir);
        $templateProcessor->setValue('tel', $request->tel);
        $templateProcessor->setValue('cel', $request->cel);

        // Actualizar los datos del estudio
        $fecEstudio = Carbon::parse($request->fec_estudio)->format('d/m/Y');
        $fecLectura = Carbon::parse($request->fechor_lectura)->format('d/m/Y H:i');
        $fecTrascrito = Carbon::parse($request->fechor_trascrito)->format('d/m/Y H:i');

        $templateProcessor->setValue('fechor_estudio', $fecEstudio);
        $templateProcessor->setValue('fechor_lectura', $fecLectura);
        $templateProcessor->setValue('fechor_transcipcion', $fecTrascrito);

        $templateProcessor->setValue('atencion', $request->atencion);
        $templateProcessor->setValue('cod_producto', $request->cod_cups);
        $templateProcessor->setValue('nom_producto', $request->nom_produc);

        $templateProcessor->setValue('nom_sede', $request->nom_sede);

        // Actualizar los datos de la ecografia
        $templateProcessor->setValue('tipo_estudio', $request->tipo_estudio);
        $templateProcessor->setValue('opinion', $request->opinion);

        $productosEcografia = EcografiaProducto::where('ecografia_id', $request->ecografia_id)
            ->with('productos')
            ->get();

        $productosLista = $productosEcografia->map(function ($item) {
            return '* ' . $item->productos->nom_produc;
        })->implode("\n");

        $templateProcessor->setValue('productos', $productosLista);

        // Actualizar los datos de la administradora de salud
        $templateProcessor->setValue('eps', $request->eps);
        $templateProcessor->setValue('contrato', $request->contrato);

        // Actualizar los datos del médico
        $templateProcessor->setValue('nombres_medico', $request->nombres_medico);
        $templateProcessor->setValue('reg_medico', $request->reg_medico);
        $templateProcessor->setValue('num_docu_medico', $request->num_docu_medico);

        // Manejar la carga de la imagen
        if ($request->firma_medico) {
            // Insertar la imagen en el archivo de Word
            $templateProcessor->setImageValue('firma_medico', [
                'path' => storage_path('app/' . $request->firma_medico),
                'width' => 100,
                'height' => 100,
                'ratio' => false
            ]);
        }

        // Ruta para guardar el archivo editado
        $nomArchivo = 'ECOGRAFIA-' . $request->tipo_estudio . '-' . $request->num_docu . "-" . $nombrePaciente;
        
        $outputPath = storage_path('app/reporte_lecturas/' . $nomArchivo . '.docx');
        $templateProcessor->saveAs($outputPath);

        $plantillaPdf = $this->convertDocxToPdf($nomArchivo);
        
        if ($plantillaPdf) {

            unlink($outputPath);

            return response()->json(
                [
                    'messaje' => 'Plantilla creada correctament',
                    'plantilla' =>  asset('storage/reporte_lecturas/' . $nomArchivo . ".pdf"),
                ],
                200
            );
        } else {
            return response()->json('No fue posible convertir la plantilla a PDF', 400);
        }
    }

    function convertDocxToPdf(string $nomPlantilla): string|\Illuminate\Http\JsonResponse
    {
        // Rutas de entrada y salida
        $inputPath = storage_path("app/reporte_lecturas/{$nomPlantilla}.docx");
        $outputPath = storage_path("app/public/reporte_lecturas/{$nomPlantilla}.pdf");

        // Validar existencia del archivo de origen
        if (!file_exists($inputPath)) {
            Log::error("Archivo DOCX no encontrado: {$inputPath}");
            return response()->json(['error' => 'Archivo DOCX no encontrado.'], 404);
        }

        // Determinar comando según el sistema operativo
        if (PHP_OS_FAMILY === 'Windows') {
            $unoconvPath = 'C:\unoconv\unoconv.bat';
        } else {
            $unoconvPath = '/usr/bin/unoconv';
        }

        // Validar que unoconv exista
        if (!file_exists($unoconvPath)) {
            Log::error("unoconv no encontrado en la ruta: {$unoconvPath}");
            return response()->json(['error' => 'unoconv no encontrado en el sistema.'], 500);
        }

        try {
            // Comando para convertir el archivo .docx a PDF utilizando unoconv
            $command = escapeshellcmd('C:\unoconv\unoconv -f pdf -o ' . escapeshellarg($outputPath) . ' ' . escapeshellarg($inputPath));

            // Ejecutar el comando
            $output = shell_exec($command);
        } catch (\Exception $e) {
            Log::error("Error durante la conversión: " . $e->getMessage());
            return response()->json(['error' => 'Error durante la conversión a PDF.'], 500);
        }

        return response()->json([
            'url' => asset("storage/reporte_lecturas/{$nomPlantilla}.pdf")
        ]);
    }
}
