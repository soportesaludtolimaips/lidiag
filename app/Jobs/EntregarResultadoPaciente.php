<?php

namespace App\Jobs;

use App\Models\Estudio\EntregaResultado;
use App\Services\Entrega\ContextoEstudio;
use App\Services\Entrega\EntregaResultadoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Entrega el resultado al paciente (imágenes al portal + enlace por WhatsApp/correo).
 *
 * Se ejecuta fuera de la petición del médico: bajar cientos de imágenes del PACS y subirlas
 * puede tardar minutos. El contexto lleva datos del paciente, por eso el trabajo va cifrado en
 * la cola (ShouldBeEncrypted).
 */
class EntregarResultadoPaciente implements ShouldBeEncrypted, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Sin reintentos automáticos: un reintento tras un envío parcial podría repetir mensajes. */
    public int $tries = 1;

    public int $timeout = 3600;

    /**
     * @param  array{estudio_producto_id: int, lectura_id: int, sede_id: ?int, study_iuid: ?string, num_docu: string, nom_pacien: ?string, nom_sede: ?string}  $contexto
     */
    public function __construct(
        public array $contexto,
        public bool $automatico = true,
        public ?int $usuarioId = null,
    ) {}

    public function handle(EntregaResultadoService $servicio): void
    {
        @set_time_limit(0);

        $resultado = $servicio->entregar($this->contexto, $this->automatico, $this->usuarioId);

        Log::info('Entrega de resultados', [
            'estudio_producto_id' => $this->contexto['estudio_producto_id'],
            'automatico' => $this->automatico,
            'imagenes' => $resultado['imagenes'],
            'canales' => $resultado['canales'],
        ]);
    }

    /**
     * Despacha el trabajo. Con la cola `sync` (por defecto) se ejecuta al terminar de enviar la
     * respuesta, para no dejar al médico esperando; con una cola real se encola normalmente.
     */
    public static function despachar(array $contexto, bool $automatico = true, ?int $usuarioId = null): void
    {
        $trabajo = new self($contexto, $automatico, $usuarioId);

        config('queue.default') === 'sync'
            ? dispatch($trabajo)->afterResponse()
            : dispatch($trabajo);
    }

    /** La entrega aplica a todas las sedes, o solo a las de `entrega_resultados.sedes` si se definió. */
    public static function sedeHabilitada(?int $sedeId): bool
    {
        $permitidas = (array) config('entrega_resultados.sedes', []);

        return $permitidas === [] || ($sedeId !== null && in_array($sedeId, $permitidas, true));
    }

    /**
     * Programa la entrega cuando la lectura ya está en el portal.
     *
     * No hace nada si la entrega está apagada, si el portal no recibió la lectura (no hay lectura_id) o
     * si falta el estudio. Nunca lanza excepciones: no debe afectar el guardado de la lectura.
     *
     * A QUIÉN se entrega no depende de lo que mande la pantalla: el estudio se resuelve por su id en la BD
     * (documento, sede, UID, nombre de la sede) y el documento de la pantalla, que es el que recibió el
     * portal, debe coincidir con el del estudio. Si no coincide no se entrega nada y queda registrado.
     *
     * @param  array  $resultadoPortal  Respuesta de enviarPortalPacientes: success + data.lectura_id.
     * @param  array  $data  Datos del estudio enviados desde la pantalla de transcripción.
     * @return bool true si se programó la entrega
     */
    public static function programar(array $resultadoPortal, array $data, bool $automatico = true, ?int $usuarioId = null): bool
    {
        $lecturaId = (int) ($resultadoPortal['data']['lectura_id'] ?? 0);
        $estudioProductoId = (int) ($data['estudio_producto_id'] ?? 0);

        if (! config('entrega_resultados.activo')
            || ! ($resultadoPortal['success'] ?? false)
            || $lecturaId === 0
            || $estudioProductoId === 0) {
            return false;
        }

        try {
            $estudio = app(ContextoEstudio::class)->resolver($estudioProductoId);

            if ($estudio === null) {
                Log::warning('Entrega de resultados: el estudio no existe; no se entrega', ['estudio_producto_id' => $estudioProductoId]);

                return false;
            }

            if (! self::sedeHabilitada($estudio['sede_id'])) {
                return false;
            }

            if ($estudio['num_docu'] === '' || $estudio['num_docu'] !== trim((string) ($data['num_docu'] ?? ''))) {
                Log::warning('Entrega de resultados: el documento de la pantalla no coincide con el del estudio; no se entrega', ['estudio_producto_id' => $estudioProductoId]);
                self::registrarOmitido($estudioProductoId, $lecturaId, $estudio['sede_id'], $automatico, $usuarioId);

                return false;
            }

            self::despachar([
                'estudio_producto_id' => $estudioProductoId,
                'lectura_id' => $lecturaId,
                'sede_id' => $estudio['sede_id'],
                'study_iuid' => $estudio['study_iuid'],
                'num_docu' => $estudio['num_docu'],
                'nom_pacien' => $estudio['nom_pacien'],
                'nom_sede' => $estudio['nom_sede'],
            ], $automatico, $usuarioId);

            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo programar la entrega del resultado al paciente: '.$e->getMessage());

            return false;
        }
    }

    /** Deja constancia (por cada canal activo) de que no se entregó por inconsistencia de datos. */
    private static function registrarOmitido(int $estudioProductoId, int $lecturaId, ?int $sedeId, bool $automatico, ?int $usuarioId): void
    {
        $canales = array_keys(array_filter([
            EntregaResultado::CANAL_WHATSAPP => config('entrega_resultados.whatsapp.activo'),
            EntregaResultado::CANAL_CORREO => config('entrega_resultados.correo.activo'),
        ]));

        foreach ($canales as $canal) {
            EntregaResultado::create([
                'estudio_producto_id' => $estudioProductoId,
                'sede_id' => $sedeId,
                'lectura_portal_id' => $lecturaId,
                'canal' => $canal,
                'estado' => EntregaResultado::OMITIDO,
                'detalle' => 'El documento de la pantalla no coincide con el del estudio',
                'automatico' => $automatico,
                'usuario_id' => $usuarioId,
            ]);
        }
    }
}
