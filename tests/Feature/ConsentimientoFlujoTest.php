<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Consentimientos\ConsentimientoDocumentoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Prueba de integración del flujo completo de un consentimiento:
 * generar → crear token → firma del paciente → firma del médico → PDF.
 * Corre contra la BD de test aislada (`lidiag_test`) con discos falsos
 * (Storage::fake), sin tocar el almacenamiento real.
 *
 * @author Davide Rivera (Luis Castaño)
 *
 * @date 2026-09-02
 */
class ConsentimientoFlujoTest extends TestCase
{
    use DatabaseTransactions;

    private function pngDataUrl(): string
    {
        $img = imagecreatetruecolor(12, 12);
        ob_start();
        imagepng($img);
        $bytes = (string) ob_get_clean();
        imagedestroy($img);

        return 'data:image/png;base64,'.base64_encode($bytes);
    }

    /**
     * PNG mínimo (67 bytes, sin píxeles) cuya cabecera DECLARA el tamaño indicado.
     */
    private function pngQueDeclara(int $ancho, int $alto): string
    {
        $chunk = static fn (string $tipo, string $datos): string => pack('N', strlen($datos)).$tipo.$datos.pack('N', crc32($tipo.$datos));
        $ihdr = pack('N2', $ancho, $alto)."\x08\x02\x00\x00\x00";
        $bytes = "\x89PNG\r\n\x1a\n".$chunk('IHDR', $ihdr).$chunk('IDAT', "\x78\x9c".str_repeat("\x00", 8)).$chunk('IEND', '');

        return 'data:image/png;base64,'.base64_encode($bytes);
    }

    /**
     * @return array{0:int,1:int} [moduloId, productoId]
     */
    private function fixtures(bool $requiereMedico): array
    {
        $moduloId = DB::table('config_modulos')->insertGetId([
            'modulo' => 'Rx', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $productoId = DB::table('config_productos')->insertGetId([
            'cod_cups' => '870121', 'nom_produc' => 'RX DE TORAX',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $creadorId = $this->usuario()->id;
        $plantillaId = DB::table('consentimiento_plantillas')->insertGetId([
            'config_modulo_id' => $moduloId,
            'producto_id' => $productoId,
            'usuario_crea_id' => $creadorId,
            'nombre' => 'C.I. RX',
            'html' => '<p>Paciente ${nombres} ${apellidos}. Firmas: ${firma_paciente} ${firma_medico}</p>',
            'version' => 1, 'vigente' => 1, 'vigente_key' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('consentimiento_plantilla_productos')->insert([
            'plantilla_id' => $plantillaId,
            'producto_id' => $productoId,
            'sede_id' => null,
            'requiere_firma_medico' => $requiereMedico ? 1 : 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$moduloId, $productoId];
    }

    private function usuario(array $extra = []): User
    {
        $id = DB::table('users')->insertGetId(array_merge([
            'name' => 'Usuario',
            'email' => 'u_'.uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'created_at' => now(), 'updated_at' => now(),
        ], $extra));

        return User::findOrFail($id);
    }

    private function tokenDesdeUrl(string $url): string
    {
        return basename((string) parse_url($url, PHP_URL_PATH));
    }

    public function test_flujo_completo_con_firma_medico_genera_pdf(): void
    {
        Storage::fake('firmas_consntimientos');
        Storage::fake('files_consentimientos');
        Storage::fake('firmas_medicos');

        // El médico tiene firma cargada en su perfil.
        Storage::disk('firmas_medicos')->put('med.png', base64_decode(
            substr($this->pngDataUrl(), strlen('data:image/png;base64,'))
        ));

        [, $productoId] = $this->fixtures(requiereMedico: true);
        $generador = $this->usuario();
        $medico = $this->usuario(['firma' => 'firmas_medicos/med.png']);
        $svc = app(ConsentimientoDocumentoService::class);

        // 1) Generar
        $gen = $svc->generar([
            'producto_id' => $productoId,
            'num_docu' => '111222333',
            'nombres' => 'JUAN',
            'apellidos' => 'PEREZ',
            'medico_id' => $medico->id,
        ], $generador, '127.0.0.1');
        $this->assertTrue($gen['success'], 'generar debe tener éxito');
        $docId = (int) $gen['data']['consentimiento_id'];
        $this->assertSame('GENERADO', DB::table('consentimiento_documentos')->find($docId)->estado);

        // 2) Token de firma
        $tk = $svc->crearToken(['documento_id' => $docId, 'firmante_tipo' => 'PACIENTE'], $generador, '127.0.0.1');
        $this->assertTrue($tk['success']);
        $token = $this->tokenDesdeUrl($tk['data']['url']);
        $this->assertNotEmpty($token);
        $this->assertSame('PENDIENTE_PACIENTE', DB::table('consentimiento_documentos')->find($docId)->estado);

        // 3) Firma del paciente → PENDIENTE_MEDICO
        $fp = $svc->firmarPacientePorToken($token, ['firma_png' => $this->pngDataUrl()], '127.0.0.1');
        $this->assertTrue($fp['success'], 'firma paciente debe tener éxito');
        $this->assertSame('PENDIENTE_MEDICO', DB::table('consentimiento_documentos')->find($docId)->estado);

        // 4) Firma del médico → COMPLETO + PDF (generado automáticamente)
        $fm = $svc->firmarMedico(['documento_id' => $docId], $medico, '127.0.0.1');
        $this->assertTrue($fm['success'], 'firma médico debe tener éxito');

        $doc = DB::table('consentimiento_documentos')->find($docId);
        $this->assertSame('COMPLETO', $doc->estado);
        $this->assertNotNull($doc->pdf_path, 'debe quedar registrado el PDF');

        // El PDF cifrado existe en el disco (fake) y descifra a un binario PDF.
        $rel = substr($doc->pdf_path, strlen('files_consentimientos/'));
        Storage::disk('files_consentimientos')->assertExists($rel);
        $pdf = decrypt(Storage::disk('files_consentimientos')->get($rel));
        $this->assertStringStartsWith('%PDF', $pdf, 'el contenido debe ser un PDF válido');
    }

    public function test_flujo_sin_firma_medico_completa_al_firmar_paciente(): void
    {
        Storage::fake('firmas_consntimientos');
        Storage::fake('files_consentimientos');

        [, $productoId] = $this->fixtures(requiereMedico: false);
        $generador = $this->usuario();
        $svc = app(ConsentimientoDocumentoService::class);

        $gen = $svc->generar([
            'producto_id' => $productoId, 'num_docu' => '444555', 'nombres' => 'ANA', 'apellidos' => 'GOMEZ',
        ], $generador, '127.0.0.1');
        $docId = (int) $gen['data']['consentimiento_id'];

        $tk = $svc->crearToken(['documento_id' => $docId, 'firmante_tipo' => 'PACIENTE'], $generador, '127.0.0.1');
        $token = $this->tokenDesdeUrl($tk['data']['url']);

        $fp = $svc->firmarPacientePorToken($token, ['firma_png' => $this->pngDataUrl()], '127.0.0.1');
        $this->assertTrue($fp['success']);

        // Sin firma de médico requerida → COMPLETO directo.
        $this->assertSame('COMPLETO', DB::table('consentimiento_documentos')->find($docId)->estado);

        // Generación de PDF explícita.
        $pdf = $svc->generarPdf($docId);
        $this->assertTrue($pdf['success']);
        $this->assertNotNull(DB::table('consentimiento_documentos')->find($docId)->pdf_path);
    }

    public function test_no_se_puede_generar_pdf_incompleto(): void
    {
        Storage::fake('firmas_consntimientos');
        Storage::fake('files_consentimientos');

        [, $productoId] = $this->fixtures(requiereMedico: true);
        $generador = $this->usuario();
        $svc = app(ConsentimientoDocumentoService::class);

        $gen = $svc->generar([
            'producto_id' => $productoId, 'num_docu' => '777', 'nombres' => 'LUZ', 'apellidos' => 'DIAZ',
        ], $generador, '127.0.0.1');
        $docId = (int) $gen['data']['consentimiento_id'];

        // Aún en GENERADO (sin firmas): no debe permitir PDF.
        $pdf = $svc->generarPdf($docId);
        $this->assertFalse($pdf['success']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_rechaza_un_png_que_declara_dimensiones_enormes_sin_decodificarlo(): void
    {
        // Decodificar un PNG que declara 8000x8000 pediría ~256 MB: con solo 48 MB de margen, la versión que valida
        // las dimensiones DESPUÉS de decodificar termina en error fatal; la que las lee de la cabecera, no.
        ini_set('memory_limit', (string) (memory_get_usage(true) + 48 * 1024 * 1024));

        $metodo = new ReflectionMethod(ConsentimientoDocumentoService::class, 'decodePngDataUrl');
        $metodo->setAccessible(true);
        $resultado = $metodo->invoke(app(ConsentimientoDocumentoService::class), $this->pngQueDeclara(8000, 8000));

        $this->assertIsArray($resultado);
        $this->assertFalse($resultado['success']);
        $this->assertSame(422, $resultado['status']);
    }

    public function test_dos_envios_con_el_mismo_enlace_solo_registran_una_firma(): void
    {
        Storage::fake('files_consentimientos');
        $firmas = Storage::fake('firmas_consntimientos');

        [, $productoId] = $this->fixtures(requiereMedico: false);
        $generador = $this->usuario();
        $svc = app(ConsentimientoDocumentoService::class);

        $gen = $svc->generar([
            'producto_id' => $productoId, 'num_docu' => '888999', 'nombres' => 'EVA', 'apellidos' => 'RIOS',
        ], $generador, '127.0.0.1');
        $docId = (int) $gen['data']['consentimiento_id'];
        $tk = $svc->crearToken(['documento_id' => $docId, 'firmante_tipo' => 'PACIENTE'], $generador, '127.0.0.1');
        $token = $this->tokenDesdeUrl($tk['data']['url']);
        $png = $this->pngDataUrl();

        // La segunda petición llega justo mientras la primera guarda su archivo de firma: las dos ya pasaron la
        // comprobación del enlace, que en ese momento seguía vigente.
        $segunda = null;
        $alGuardar = function () use (&$segunda, $svc, $token, $png): void {
            $segunda = $svc->firmarPacientePorToken($token, ['firma_png' => $png], '10.0.0.2');
        };
        Storage::set('firmas_consntimientos', new class($firmas, $alGuardar)
        {
            private bool $yaPaso = false;

            public function __construct(private readonly object $disco, private readonly \Closure $alGuardar) {}

            public function put(mixed ...$argumentos): mixed
            {
                if (! $this->yaPaso) {
                    $this->yaPaso = true;
                    ($this->alGuardar)();
                }

                return $this->disco->put(...$argumentos);
            }

            public function __call(string $metodo, array $argumentos): mixed
            {
                return $this->disco->{$metodo}(...$argumentos);
            }
        });

        $primera = $svc->firmarPacientePorToken($token, ['firma_png' => $png], '10.0.0.1');

        $this->assertTrue($segunda['success'], 'la petición que llegó en medio firma con normalidad');
        $this->assertFalse($primera['success'], 'la otra ya no puede firmar: el enlace se gastó');
        $this->assertSame(410, $primera['status']);
        $this->assertCount(1, $firmas->allFiles(), 'queda un solo archivo de firma (el de la petición rechazada se borra)');
        $this->assertSame(1, DB::table('consentimiento_documento_auditoria')
            ->where('documento_id', $docId)->where('accion', 'firma_paciente')->count());
        $this->assertSame(1, (int) DB::table('consentimiento_documento_tokens')->where('documento_id', $docId)->value('usos_actuales'));
        $ruta = (string) DB::table('consentimiento_documentos')->find($docId)->firma_paciente_path;
        $this->assertTrue($firmas->exists(substr($ruta, strlen('firmas_consntimientos/'))), 'el documento apunta a la firma que sí quedó guardada');
    }
}
