<?php

namespace Tests\Feature;

use App\Services\Backup\BackupService;
use Mockery;
use Tests\TestCase;

/**
 * Pruebas de integración para los comandos Artisan de backup.
 *
 * Flujo del proceso:
 * 1. Verifica ejecución del comando lidiag:backup --only-db delegando a BackupService::ejecutar.
 * 2. Verifica ejecución del comando lidiag:backup --only-files delegando a BackupService::ejecutar.
 * 3. Verifica manejo de salida de error cuando el proceso de backup falla.
 *
 * @author Jhon Javer Lozano Arce
 *
 * @date 2026-10-20
 */
class BackupArtisanCommandTest extends TestCase
{
    public function test_comando_backup_ejecuta_modo_only_db_correctamente(): void
    {
        $mockBackup = Mockery::mock(BackupService::class);
        $mockBackup->shouldReceive('ejecutar')
            ->once()
            ->with(
                Mockery::on(fn ($opts) => ($opts['only_db'] ?? false) === true),
                Mockery::type('closure')
            )
            ->andReturn([
                'success' => true,
                'message' => 'Proceso de backup finalizado con éxito.',
                'data' => [
                    'bd_incluida' => true,
                    'archivo_db' => 'backup_test.zip',
                    'tamano_db_humano' => '1.5 MB',
                    'carpetas_procesadas' => 0,
                    'archivos_purgados' => 0,
                ],
            ]);

        $this->app->instance(BackupService::class, $mockBackup);

        $this->artisan('lidiag:backup', ['--only-db' => true])
            ->assertExitCode(0);
    }

    public function test_comando_backup_ejecuta_modo_only_files_correctamente(): void
    {
        $mockBackup = Mockery::mock(BackupService::class);
        $mockBackup->shouldReceive('ejecutar')
            ->once()
            ->with(
                Mockery::on(fn ($opts) => ($opts['only_files'] ?? false) === true),
                Mockery::type('closure')
            )
            ->andReturn([
                'success' => true,
                'message' => 'Proceso de backup finalizado con éxito.',
                'data' => [
                    'bd_incluida' => false,
                    'carpetas_procesadas' => 2,
                    'archivos_storage' => [
                        ['carpeta' => 'audios', 'tamano_humano' => '2 MB'],
                    ],
                    'archivos_purgados' => 0,
                ],
            ]);

        $this->app->instance(BackupService::class, $mockBackup);

        $this->artisan('lidiag:backup', ['--only-files' => true])
            ->assertExitCode(0);
    }

    public function test_comando_backup_retorna_codigo_error_si_falla(): void
    {
        $mockBackup = Mockery::mock(BackupService::class);
        $mockBackup->shouldReceive('ejecutar')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Fallo al conectar con mysqldump',
            ]);

        $this->app->instance(BackupService::class, $mockBackup);

        $this->artisan('lidiag:backup', ['--only-db' => true])
            ->assertExitCode(1);
    }
}
