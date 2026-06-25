<?php

namespace App\Http\Controllers\Ecografias;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\Ecografias\EcografiaProducto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class PlantillaEcografiaController extends Controller
{
    public function __construct()
    {
        // Este controlador se usa desde endpoints /api, pero también se instancia directamente
        // desde `EcografiaController` (en ese caso los middlewares no aplican).
        $this->middleware('auth:sanctum');
        $this->middleware('permission:ecografias.reporte,web')->only(['editWord']);
    }

    /**
     * Genera el reporte final de ecografía reemplazando marcadores en una plantilla de Word y convirtiéndolo a PDF.
     *
     * Flujo del proceso:
     * 1. Busca la plantilla asociada a la Sede (ConfigSede).
     * 2. Carga datos del médico (nombre, registro, firma) y del paciente.
     * 3. Usa TemplateProcessor para reemplazar variables en el .docx (paciente, estudio, lectura, opinión, lista de productos).
     * 4. Inserta la firma del médico como imagen.
     * 5. Guarda el .docx temporalmente.
     * 6. Llama a convertDocxToPdf para generar el PDF.
     * 7. Elimina el .docx temporal y retorna la URL del PDF.
     *
     * @param  Request  $request  Datos del estudio, paciente, médico y contenido del reporte.
     * @return JsonResponse Retorna la URL del PDF generado o un mensaje de error.
     */
    public function editWord(Request $request)
    {
        /**
         * Buscamos la sede para saber cual es la plantilla
         */
        $filePath = '';

        $sede = ConfigSede::find($request->sede_id);
        if (! $sede) {
            return response()->json(['message' => 'Sede no encontrada.'], 404);
        }

        if (! $sede->plantilla_ecografia) {
            return response()->json(['message' => 'La plantilla para ecografias de la sede '.$sede->nom_sede.' no está  configurada, por favor consulte con el administrador del sistema'], 402);
        } elseif ($sede->plantilla_ecografia) {
            $medico = User::find($request->medico_id);
            if (! $medico) {
                return response()->json(['message' => 'Médico no encontrado.'], 404);
            }
            $request->merge(['nombres_medico' => $medico->name]);
            $request->merge(['reg_medico' => $medico->reg_med]);
            $request->merge(['num_docu_medico' => $medico->num_docu]);
            $request->merge(['firma_medico' => $medico->firma]);

            // Ruta del archivo de plantilla
            $filePath = storage_path('app/plantillas_lecturas/'.$sede->plantilla_ecografia);
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
        $fecEstudio = $request->fec_estudio ? Carbon::parse($request->fec_estudio)->format('d/m/Y') : '';
        $fecLectura = $request->fechor_lectura ? Carbon::parse($request->fechor_lectura)->format('d/m/Y H:i') : '';
        $fecTrascrito = $request->fechor_trascrito ? Carbon::parse($request->fechor_trascrito)->format('d/m/Y H:i') : '';

        $templateProcessor->setValue('fechor_estudio', $fecEstudio);
        $templateProcessor->setValue('fechor_lectura', $fecLectura);
        $templateProcessor->setValue('fechor_transcipcion', $fecTrascrito);

        $templateProcessor->setValue('atencion', $request->atencion);
        $templateProcessor->setValue('cod_producto', $request->cod_cups);
        $templateProcessor->setValue('nom_producto', $request->nom_produc);

        $templateProcessor->setValue('nom_sede', $request->nom_sede);

        // Actualizar los datos de la ecografia
        $templateProcessor->setValue('tipo_estudio', $request->tipo_estudio);
        $templateProcessor->setValue('lectura', $request->lectura);
        $templateProcessor->setValue('opinion', $request->opinion);

        $productosEcografia = EcografiaProducto::where('ecografia_id', $request->ecografia_id)
            ->with('productos')
            ->get();

        $productosLista = $productosEcografia->map(function ($item) {
            return '* '.$item->productos->nom_produc;
        })->implode("\n");

        $templateProcessor->setValue('productos', $productosLista);

        // Actualizar los datos de la administradora de salud
        $templateProcessor->setValue('eps', $request->admin_salud);
        $templateProcessor->setValue('contrato', $request->contrato);

        // Actualizar los datos del médico
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

        // Ruta para guardar el archivo editado (sanear nombre de archivo)
        $rawName = 'ECOGRAFIA-'.($request->tipo_estudio ?? 'ECO').'-'.($request->num_docu ?? 'SIN_DOC')."-".$nombrePaciente;
        $nomArchivo = Str::of($rawName)->replaceMatches('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|]+/', '-')->limit(150, '')->toString();

        $outputPath = storage_path('app/reporte_lecturas/'.$nomArchivo.'.docx');
        $templateProcessor->saveAs($outputPath);

        $plantillaPdf = $this->convertDocxToPdf($nomArchivo);

        if ($plantillaPdf) {

            unlink($outputPath);

            return response()->json(
                [
                    'messaje' => 'Plantilla creada correctament',
                    'plantilla' => asset('storage/reporte_lecturas/'.$nomArchivo.".pdf"),
                ],
                200
            );
        } else {
            // best-effort cleanup del docx
            try {
                @unlink($outputPath);
            } catch (\Exception $e) {
            }

            return response()->json(['message' => 'No fue posible convertir la plantilla a PDF'], 400);
        }
    }

    /**
     * Convierte un archivo Word (.docx) a PDF utilizando la herramienta del sistema unoconv.
     *
     * Verifica la existencia del archivo de entrada y de la herramienta unoconv.
     * Ejecuta el comando de sistema para la conversión.
     *
     * @param  string  $nomPlantilla  Nombre base del archivo (sin extensión) ubicado en storage/app/reporte_lecturas/
     * @return string|JsonResponse Retorna un JSON con la URL pública del PDF o un error.
     */
    public function convertDocxToPdf(string $nomPlantilla): bool
    {
        // Rutas de entrada y salida
        $inputPath = storage_path("app/reporte_lecturas/{$nomPlantilla}.docx");
        $outputPath = storage_path("app/public/reporte_lecturas/{$nomPlantilla}.pdf");

        // Validar existencia del archivo de origen
        if (! file_exists($inputPath)) {
            Log::error("Archivo DOCX no encontrado: {$inputPath}");

            return false;
        }

        // Determinar comando según el sistema operativo
        if (PHP_OS_FAMILY === 'Windows') {
            $unoconvPath = 'C:\unoconv\unoconv.bat';
        } else {
            $unoconvPath = '/usr/bin/unoconv';
        }

        // Validar que unoconv exista
        if (! file_exists($unoconvPath)) {
            Log::error("unoconv no encontrado en la ruta: {$unoconvPath}");

            return false;
        }

        $filename = pathinfo($nomPlantilla, PATHINFO_FILENAME);
        if (! preg_match('/^[a-zA-Z0-9._-]+$/', $filename)) {
            throw new \InvalidArgumentException('Nombre de archivo inválido');
        }

        try {
            // Asegurar carpeta destino
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
