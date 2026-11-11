<?php

namespace App\Services\Entrega;

use App\Mail\EnlaceResultadoPaciente;
use App\Models\Configuracion\ConfigSede;
use App\Models\Estudio\EntregaResultado;
use App\Models\General\Paciente;
use App\Services\WhatsAppService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Entrega del resultado al paciente una vez que existe la lectura.
 *
 * 1. Pide al portal el enlace del paciente (lo genera al recibir la lectura) y comprueba que sea del
 *    dominio del portal (REPORTES_URL_PUBLICA); si no lo es, no se envía nada.
 * 2. Sube las imágenes del estudio al portal (JPEG del PACS, por lotes).
 * 3. Envía el enlace por WhatsApp (plantilla aprobada) y/o por correo (solo el enlace).
 * 4. Deja registro de cada envío (destino enmascarado) en `entregas_resultado`.
 *
 * En modo automático un canal que ya envió con éxito no se repite; el reenvío manual sí.
 * Un fallo en las imágenes no impide enviar el enlace (el informe ya está en el portal).
 */
class EntregaResultadoService
{
    public function __construct(
        private readonly ClientePortalReportes $portal,
        private readonly ImagenesEstudioPacs $pacs,
        private readonly WhatsAppService $whatsapp,
    ) {}

    /**
     * @param  array{estudio_producto_id: int, lectura_id: int, sede_id: ?int, study_iuid: ?string, num_docu: string, nom_pacien: ?string, nom_sede: ?string}  $ctx
     * @return array{imagenes: string, canales: array<string, string>}
     */
    public function entregar(array $ctx, bool $automatico = true, ?int $usuarioId = null): array
    {
        if (! $this->portal->configurado()) {
            Log::warning('Entrega de resultados: el portal no está configurado (URL_API_REPORTES / API_HMAC_SECRET).');

            return ['imagenes' => 'sin_portal', 'canales' => []];
        }

        $info = $this->consultarEnlace($ctx['lectura_id']);
        if ($info === null) {
            Log::warning('Entrega de resultados: no se obtuvo el enlace del portal', ['lectura_id' => $ctx['lectura_id']]);

            return ['imagenes' => 'sin_enlace', 'canales' => []];
        }

        // Solo se entrega un enlace del dominio del portal (REPORTES_URL_PUBLICA): ver ClientePortalReportes::tokenDeEnlace().
        $token = $this->portal->tokenDeEnlace((string) ($info['enlace']['url'] ?? ''));
        if ($token === null) {
            Log::warning('Entrega de resultados: el enlace del portal no corresponde a REPORTES_URL_PUBLICA (sin configurar, sin https en producción u otro dominio); no se envió nada', ['lectura_id' => $ctx['lectura_id']]);

            return ['imagenes' => 'enlace_invalido', 'canales' => []];
        }

        $imagenes = $this->subirImagenes($ctx, (string) ($info['imagenes_estado'] ?? 'sin_imagenes'));

        $contacto = $this->contacto($ctx['num_docu']);
        $enlace = ['url' => $this->portal->urlPublica().'/p/'.$token, 'token' => $token];
        $dias = $this->diasDeVigencia((string) ($info['enlace']['expira_en'] ?? ''));

        return [
            'imagenes' => $imagenes,
            'canales' => [
                EntregaResultado::CANAL_WHATSAPP => $this->porWhatsapp($ctx, $contacto, $enlace, $dias, $automatico, $usuarioId),
                EntregaResultado::CANAL_CORREO => $this->porCorreo($ctx, $contacto, $enlace, $dias, $automatico, $usuarioId),
            ],
        ];
    }

    // ------------------------------------------------------------------ Enlace

    /** @return array{imagenes_estado: ?string, enlace: array}|null */
    private function consultarEnlace(int $lecturaId): ?array
    {
        try {
            $respuesta = $this->portal->enlace($lecturaId);
            if (! $respuesta->successful()) {
                return null;
            }

            $info = (array) $respuesta->json();
            if (empty($info['enlace'])) {
                // El enlace venció o fue revocado: se crea uno nuevo.
                $nuevo = $this->portal->regenerarEnlace($lecturaId, false);
                if (! $nuevo->successful() || empty($nuevo->json('enlace'))) {
                    return null;
                }
                $info['enlace'] = $nuevo->json('enlace');
            }

            return $info;
        } catch (Throwable $e) {
            Log::warning('Entrega de resultados: error consultando el enlace del portal: '.$e->getMessage());

            return null;
        }
    }

    private function diasDeVigencia(string $expiraEn): int
    {
        try {
            return max(1, (int) ceil(now()->diffInDays(Carbon::parse($expiraEn), false)));
        } catch (Throwable) {
            return 30;
        }
    }

    // ---------------------------------------------------------------- Imágenes

    /** Devuelve el estado final de las imágenes en el portal (completo, parcial, ...) o el motivo de omisión. */
    private function subirImagenes(array $ctx, string $estadoActual): string
    {
        $config = config('entrega_resultados.imagenes');

        if (! $config['activo']) {
            return 'omitidas';
        }
        if ($estadoActual === 'completo') {
            return 'completo'; // ya estaban en el portal: un reenvío no las vuelve a subir
        }

        $sede = $ctx['sede_id'] ? ConfigSede::find($ctx['sede_id']) : null;
        if ($sede === null || empty($sede->ip_dcm) || empty($ctx['study_iuid'])) {
            return 'sin_pacs';
        }

        try {
            $instancias = array_slice($this->pacs->instancias($sede, $ctx['study_iuid']), 0, max(1, (int) $config['max_por_estudio']));
        } catch (Throwable $e) {
            Log::warning('Entrega de resultados: no se pudo consultar el PACS', ['sede_id' => $sede->id, 'error' => $e->getMessage()]);

            return 'error_pacs';
        }

        if ($instancias === []) {
            return 'sin_imagenes';
        }

        $lote = [];
        $bytesLote = 0;
        $inicio = microtime(true);

        foreach ($instancias as $instancia) {
            if (microtime(true) - $inicio > (int) $config['max_segundos']) {
                break; // se cierra como parcial con lo ya subido
            }

            $bytes = $this->pacs->descargar($sede, $ctx['study_iuid'], $instancia);
            if ($bytes === null || strlen($bytes) > (int) $config['max_bytes_lote']) {
                continue;
            }

            $lleno = count($lote) >= (int) $config['max_por_lote'] || $bytesLote + strlen($bytes) > (int) $config['max_bytes_lote'];
            if ($lote !== [] && $lleno) {
                if (! $this->subirLote($ctx['lectura_id'], $lote)) {
                    return $this->cerrarImagenes($ctx['lectura_id'], count($instancias));
                }
                $lote = [];
                $bytesLote = 0;
            }

            $lote[] = [
                'bytes' => $bytes,
                'sop_uid' => $instancia['sop_iuid'],
                'serie_uid' => $instancia['series_iuid'],
                'serie_orden' => $instancia['serie_orden'],
                'orden' => $instancia['orden'],
            ];
            $bytesLote += strlen($bytes);
        }

        if ($lote !== []) {
            $this->subirLote($ctx['lectura_id'], $lote);
        }

        return $this->cerrarImagenes($ctx['lectura_id'], count($instancias));
    }

    private function subirLote(int $lecturaId, array $lote): bool
    {
        try {
            $respuesta = $this->portal->subirLote($lecturaId, $lote);
        } catch (Throwable $e) {
            Log::warning('Entrega de resultados: error subiendo un lote de imágenes: '.$e->getMessage());

            return false;
        }

        if (! $respuesta->successful()) {
            Log::warning('Entrega de resultados: el portal rechazó un lote de imágenes', ['status' => $respuesta->status()]);

            return false;
        }

        return true;
    }

    private function cerrarImagenes(int $lecturaId, int $esperadas): string
    {
        try {
            $respuesta = $this->portal->finalizarImagenes($lecturaId, $esperadas);
        } catch (Throwable $e) {
            Log::warning('Entrega de resultados: error cerrando las imágenes: '.$e->getMessage());

            return 'error_portal';
        }

        return $respuesta->successful() ? (string) $respuesta->json('estado', 'error_portal') : 'error_portal';
    }

    // ---------------------------------------------------------------- Canales

    /** @return array{cel: ?string, tel: ?string, email: ?string} */
    private function contacto(string $numDocu): array
    {
        $paciente = Paciente::where('num_docu', $numDocu)->first();

        return ['cel' => $paciente?->cel, 'tel' => $paciente?->tel, 'email' => $paciente?->email];
    }

    /** @param  array{url: string, token: string}  $enlace */
    private function porWhatsapp(array $ctx, array $contacto, array $enlace, int $dias, bool $automatico, ?int $usuarioId): string
    {
        $canal = EntregaResultado::CANAL_WHATSAPP;
        if (! config('entrega_resultados.whatsapp.activo')) {
            return 'desactivado';
        }
        if ($automatico && $this->yaEnviado($ctx['estudio_producto_id'], $canal)) {
            return 'ya_enviado';
        }

        $numero = Contacto::celular($contacto['cel'], $contacto['tel']);
        if ($numero === null) {
            return $this->registrar($ctx, $canal, null, EntregaResultado::OMITIDO, 'Sin celular válido', $automatico, $usuarioId);
        }

        // Plantilla: {{1}} sede, {{2}} días de vigencia; el botón de URL lleva el token como sufijo.
        $variables = [$this->variable($ctx['nom_sede'] ?? null) ?: 'nuestra sede', (string) $dias];

        try {
            $respuesta = $this->whatsapp->enviarPlantilla($numero, (string) config('entrega_resultados.whatsapp.plantilla'), $variables, $enlace['token']);
        } catch (Throwable $e) {
            $respuesta = ['success' => false, 'error' => class_basename($e)];
        }

        $exito = (bool) ($respuesta['success'] ?? false);

        return $this->registrar(
            $ctx,
            $canal,
            Contacto::enmascararCelular($numero),
            $exito ? EntregaResultado::ENVIADO : EntregaResultado::FALLIDO,
            $exito ? ($respuesta['message_id'] ?? null) : (string) ($respuesta['error'] ?? 'Error desconocido'),
            $automatico,
            $usuarioId
        );
    }

    /** @param  array{url: string, token: string}  $enlace */
    private function porCorreo(array $ctx, array $contacto, array $enlace, int $dias, bool $automatico, ?int $usuarioId): string
    {
        $canal = EntregaResultado::CANAL_CORREO;
        if (! config('entrega_resultados.correo.activo')) {
            return 'desactivado';
        }
        if ($automatico && $this->yaEnviado($ctx['estudio_producto_id'], $canal)) {
            return 'ya_enviado';
        }

        $correo = Contacto::correo($contacto['email']);
        if ($correo === null) {
            return $this->registrar($ctx, $canal, null, EntregaResultado::OMITIDO, 'Sin correo válido', $automatico, $usuarioId);
        }

        try {
            Mail::to($correo)->send(new EnlaceResultadoPaciente(
                Contacto::primerNombre($ctx['nom_pacien'] ?? null),
                $ctx['nom_sede'] ?? null,
                $enlace['url'],
                $dias
            ));
            $estado = EntregaResultado::ENVIADO;
            $detalle = null;
        } catch (Throwable $e) {
            // Solo la clase del error: el mensaje del servidor de correo puede incluir la dirección.
            Log::warning('Entrega de resultados: no se pudo enviar el correo ('.class_basename($e).')');
            $estado = EntregaResultado::FALLIDO;
            $detalle = 'Error al enviar el correo ('.class_basename($e).')';
        }

        return $this->registrar($ctx, $canal, Contacto::enmascararCorreo($correo), $estado, $detalle, $automatico, $usuarioId);
    }

    // ---------------------------------------------------------------- Registro

    private function yaEnviado(int $estudioProductoId, string $canal): bool
    {
        return EntregaResultado::where('estudio_producto_id', $estudioProductoId)
            ->where('canal', $canal)
            ->enviados()
            ->exists();
    }

    private function registrar(array $ctx, string $canal, ?string $destino, string $estado, ?string $detalle, bool $automatico, ?int $usuarioId): string
    {
        EntregaResultado::create([
            'estudio_producto_id' => $ctx['estudio_producto_id'],
            'sede_id' => $ctx['sede_id'] ?? null,
            'lectura_portal_id' => $ctx['lectura_id'],
            'canal' => $canal,
            'destino' => $destino,
            'estado' => $estado,
            'detalle' => $detalle !== null ? mb_substr($detalle, 0, 500) : null,
            'automatico' => $automatico,
            'usuario_id' => $usuarioId,
        ]);

        return $estado;
    }

    /** Variables de plantilla de Meta: una línea, sin tabulaciones ni espacios repetidos. */
    private function variable(?string $texto): string
    {
        return trim((string) preg_replace('/\s+/', ' ', (string) $texto));
    }
}
