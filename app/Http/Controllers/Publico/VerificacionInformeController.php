<?php

namespace App\Http\Controllers\Publico;

use App\Http\Controllers\Controller;
use App\Models\Estudio\EstudioProducto;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

/**
 * Controlador público para validación de autenticidad e integridad de informes médicos radiológicos.
 *
 * Permite a pacientes, médicos remitentes e instituciones prestadoras validar mediante código QR
 * o URL segura la autenticidad, trazabilidad, timestamp y firma digital de cualquier informe
 * emitido por LIDiag conforme a la Ley 527 de 1999 y Resolución 1995 de 1999.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-11-03
 */
class VerificacionInformeController extends Controller
{
    /**
     * Valida la firma digital y autenticidad del informe a través del token seguro.
     *
     * Flujo del proceso:
     * 1. Decodifica el token base64url y verifica la firma criptográfica HMAC-SHA256 con la APP_KEY.
     * 2. Recupera el producto de estudio con sus relaciones asistenciales (paciente, sede, médico).
     * 3. Anonimiza los datos sensibles del paciente (Habeas Data Ley 1581 de 2012).
     * 4. Calcula el hash criptográfico SHA-256 del cuerpo del informe médico para validar integridad.
     * 5. Retorna la vista pública de certificación de autenticidad.
     *
     * @param  string  $token  Token criptográfico generado en el PDF
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function validar(string $token): View|Response
    {
        $decoded = null;
        try {
            $base64 = strtr($token, '-_', '+/');
            $base64Padded = str_pad($base64, strlen($base64) % 4 === 0 ? strlen($base64) : strlen($base64) + (4 - (strlen($base64) % 4)), '=');
            $json = base64_decode($base64Padded, true);
            if ($json !== false) {
                $decoded = json_decode($json, true);
            }
        } catch (\Throwable $e) {
            $decoded = null;
        }

        if (! is_array($decoded) || empty($decoded['p']) || empty($decoded['s'])) {
            return response()->view('publico.validar-informe-error', [
                'mensaje' => 'El código de verificación es inválido o se encuentra mal formado.',
            ], 404);
        }

        $productoId = (int) $decoded['p'];
        $signatureExpected = substr(hash_hmac('sha256', "lidiag_estudio_{$productoId}", (string) config('app.key')), 0, 16);

        if (! hash_equals($signatureExpected, (string) $decoded['s'])) {
            return response()->view('publico.validar-informe-error', [
                'mensaje' => 'La firma digital del código de verificación no coincide con los registros oficiales de LIDiag.',
            ], 403);
        }

        $producto = EstudioProducto::with(['estudio.paciente', 'estudio.sede', 'estudio.medico'])->find($productoId);

        if (! $producto || ! $producto->estudio) {
            return response()->view('publico.validar-informe-error', [
                'mensaje' => 'El informe médico solicitado no fue encontrado en la base de datos central.',
            ], 404);
        }

        $estudio = $producto->estudio;
        $paciente = $estudio->paciente;
        $sede = $estudio->sede;
        $medico = $estudio->medico;

        // Anonimización del nombre (Ej: "JHON JAVER LOZANO" -> "J*** J*** L***")
        $nombreEnmascarado = 'PACIENTE PROTEGIDO';
        if ($paciente && ! empty($paciente->nombres)) {
            $partes = preg_split('/\s+/', trim($paciente->nombres));
            $enmascaradas = array_map(function ($p) {
                return mb_strlen($p) > 1 ? mb_substr($p, 0, 1).'***' : $p;
            }, $partes ?: []);
            $nombreEnmascarado = implode(' ', $enmascaradas);
        }

        // Anonimización del documento (Ej: "12345678" -> "******5678")
        $docEnmascarado = '******';
        if ($paciente && ! empty($paciente->num_docu)) {
            $doc = (string) $paciente->num_docu;
            $len = strlen($doc);
            $docEnmascarado = $len > 4 ? str_repeat('*', $len - 4).substr($doc, -4) : '****';
        }

        $hashIntegridad = hash('sha256', (string) ($producto->lectura ?? ''));

        $fechaEstudio = $estudio->fec_estudio ? Carbon::parse($estudio->fec_estudio)->format('d/m/Y') : 'N/A';
        $fechaLectura = $producto->fechor_lectura ? Carbon::parse($producto->fechor_lectura)->format('d/m/Y H:i:s') : 'N/A';
        $fechaTranscripcion = $producto->fechor_trascrito ? Carbon::parse($producto->fechor_trascrito)->format('d/m/Y H:i:s') : 'N/A';

        return view('publico.validar-informe', [
            'valido' => true,
            'token' => $token,
            'producto' => $producto,
            'estudio' => $estudio,
            'sedeNombre' => $sede?->nom_sede ?? 'Sede Principal LIDiag',
            'pacienteNombre' => $nombreEnmascarado,
            'documentoPaciente' => $docEnmascarado,
            'estudioNombre' => $producto->nom_produc ?: ($estudio->study_desc ?: 'Estudio Radiológico'),
            'codCups' => $producto->cod_cups ?? 'N/A',
            'medicoNombre' => $medico?->name ?? 'Médico Especialista Radiólogo',
            'registroMedico' => $medico?->reg_medico ?? 'N/A',
            'fechaEstudio' => $fechaEstudio,
            'fechaLectura' => $fechaLectura,
            'fechaTranscripcion' => $fechaTranscripcion,
            'hashIntegridad' => $hashIntegridad,
            'timestampVerificacion' => Carbon::now()->format('d/m/Y H:i:s'),
        ]);
    }
}
