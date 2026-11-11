<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Subida de backups de las sedes (`POST /api/backups/upload-dcm`).
 *
 * Es un endpoint sin sesión, autenticado solo por token. El controlador es el del equipo tal como está en el servidor
 * (desde la fusión del 2026-11-11): acepta el token estático del .env o un token Sanctum de sede, y la extensión del
 * archivo se valida con `mimes`. Estas pruebas solo fijan ese contrato; el endurecimiento propio que tenía la versión
 * anterior (503 sin token configurado, solo cabeceras, sin ruta en la respuesta) se retiró a pedido del usuario.
 */
class BackupUploadFeatureTest extends TestCase
{
    use DatabaseTransactions;

    private string $destino;

    protected function setUp(): void
    {
        parent::setUp();

        $this->destino = storage_path('app/pruebas-backups-'.uniqid());
        config(['backup.api_token' => 'token-de-prueba-largo-y-aleatorio', 'backup.dcm_destination_path' => $this->destino]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->destino.'/*') ?: [] as $archivo) {
            @unlink($archivo);
        }
        @rmdir($this->destino);

        parent::tearDown();
    }

    private function sedeId(): int
    {
        return (int) DB::table('config_sedes')->insertGetId(['nom_sede' => 'Sede de prueba de backups']);
    }

    private function subir(array $cabeceras = [], ?string $nombre = 'copia.zip')
    {
        $archivo = UploadedFile::fake()->createWithContent($nombre ?? 'copia.zip', 'contenido de prueba del backup');

        return $this->post('/api/backups/upload-dcm', ['sede_id' => $this->sedeId(), 'backup_file' => $archivo], $cabeceras + ['Accept' => 'application/json']);
    }

    public function test_rechaza_sin_token_o_con_token_incorrecto(): void
    {
        $this->subir()->assertStatus(401);
        $this->subir(['Authorization' => 'Bearer otro-token'])->assertStatus(401);
        $this->assertDirectoryDoesNotExist($this->destino);
    }

    public function test_sin_token_configurado_no_entra_nadie_con_el_antiguo_valor_por_defecto(): void
    {
        config(['backup.api_token' => null]);

        $this->subir(['Authorization' => 'Bearer lidiag_backup_secure_token_2026'])->assertStatus(401);
        $this->assertDirectoryDoesNotExist($this->destino);
    }

    public function test_guarda_el_backup_con_el_token_del_env_por_bearer_o_por_x_backup_token(): void
    {
        $this->subir(['Authorization' => 'Bearer token-de-prueba-largo-y-aleatorio'])->assertOk()->assertJsonPath('success', true);
        $this->subir(['X-Backup-Token' => 'token-de-prueba-largo-y-aleatorio'])->assertOk();

        $this->assertCount(2, glob($this->destino.'/backup_dcm_sede_*.zip'));
    }

    public function test_rechaza_extensiones_que_no_son_de_archivos_comprimidos(): void
    {
        foreach (['malo.php', 'malo.bat', 'malo.ps1', 'malo.exe'] as $nombre) {
            $this->subir(['Authorization' => 'Bearer token-de-prueba-largo-y-aleatorio'], $nombre)->assertStatus(422);
        }

        $this->assertDirectoryDoesNotExist($this->destino);
    }

    public function test_acepta_un_token_sanctum_de_sede(): void
    {
        config(['backup.api_token' => null]);
        $id = DB::table('users')->insertGetId([
            'name' => 'Agente de prueba', 'email' => 'agente_'.uniqid().'@test.local',
            'password' => bcrypt('secret'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $token = User::findOrFail($id)->createToken('agente-edge:sede:1:prueba', ['*'])->plainTextToken;

        $this->subir(['Authorization' => 'Bearer '.$token])->assertOk();
    }
}
