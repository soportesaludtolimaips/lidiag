<?php

namespace Tests\Unit;

use App\Services\Backup\BackupService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Pruebas unitarias para BackupService.
 *
 * Flujo del proceso:
 * 1. Verifica lista de subcarpetas críticas de storage declaradas.
 * 2. Verifica formateo legible de bytes (KB, MB, GB).
 * 3. Verifica política de purga por retención de archivos .zip y .sha256.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-19
 */
class BackupServiceTest extends TestCase
{
    protected BackupService $backupService;

    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupService = new BackupService;
        $this->tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR."lidiag_test_backup_".uniqid();
        File::makeDirectory($this->tempDir, 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->tempDir)) {
            File::deleteDirectory($this->tempDir);
        }
        parent::tearDown();
    }

    public function test_carpetas_storage_retorna_lista_no_vacia(): void
    {
        $carpetas = $this->backupService->obtenerCarpetasStorage();

        $this->assertIsArray($carpetas);
        $this->assertContains("audios", $carpetas);
        $this->assertContains("plantillas", $carpetas);
        $this->assertContains("reporte_lecturas", $carpetas);
    }

    public function test_formatear_bytes_convierte_correctamente(): void
    {
        $this->assertSame("0 B", $this->backupService->formatearBytes(0));
        $this->assertSame("1 KB", $this->backupService->formatearBytes(1024, 0));
        $this->assertSame("1 MB", $this->backupService->formatearBytes(1048576, 0));
        $this->assertSame("1.5 GB", $this->backupService->formatearBytes((int) (1.5 * 1073741824), 1));
    }

    public function test_aplicar_retencion_purga_archivos_antiguos_y_hashes(): void
    {
        $archivoAntiguo = $this->tempDir.DIRECTORY_SEPARATOR."backup_lidiag_db_antiguo.zip";
        $hashAntiguo = $archivoAntiguo.".sha256";
        $archivoReciente = $this->tempDir.DIRECTORY_SEPARATOR."backup_lidiag_db_reciente.zip";
        $hashReciente = $archivoReciente.".sha256";

        file_put_contents($archivoAntiguo, "antiguo");
        file_put_contents($hashAntiguo, "hash_antiguo");
        file_put_contents($archivoReciente, "reciente");
        file_put_contents($hashReciente, "hash_reciente");

        // Simular fecha antigua (hace 40 días)
        touch($archivoAntiguo, time() - (40 * 86400));
        touch($hashAntiguo, time() - (40 * 86400));
        // Simular fecha reciente (hace 2 días)
        touch($archivoReciente, time() - (2 * 86400));
        touch($hashReciente, time() - (2 * 86400));

        $purgados = $this->backupService->aplicarPoliticaRetencion($this->tempDir, 30);

        $this->assertContains(basename($archivoAntiguo), $purgados);
        $this->assertFileDoesNotExist($archivoAntiguo);
        $this->assertFileDoesNotExist($hashAntiguo);
        $this->assertFileExists($archivoReciente);
        $this->assertFileExists($hashReciente);
    }
}
