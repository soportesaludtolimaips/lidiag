<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class AuditPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:audit
                            {--guard=web : Guard a comparar (por defecto web)}
                            {--find= : Busca un permiso exacto en BD (cualquier guard) y muestra guard_name}
                            {--fail : Retorna exit code != 0 si hay faltantes en BD}
                            {--show-unused : Muestra permisos en BD que no se usan en código}
                            {--paths=* : Rutas/dirs adicionales a escanear (relativas a base_path o absolutas)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audita permisos usados en código vs permisos existentes (Spatie) en BD.';

    public function handle(): int
    {
        $guard = (string) ($this->option('guard') ?: 'web');
        $find = trim((string) ($this->option('find') ?? ''));

        // Asegura consistencia si hubo cambios recientes en roles/permisos.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($find !== '') {
            $rows = Permission::query()
                ->where('name', $find)
                ->get(['name', 'guard_name']);

            if ($rows->isEmpty()) {
                $this->error("No existe en BD: {$find}");

                return Command::FAILURE;
            }

            $this->info("Encontrado en BD ({$rows->count()}): {$find}");
            foreach ($rows as $row) {
                $this->line('- guard: '.$row->guard_name);
            }

            return Command::SUCCESS;
        }

        $dbPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->pluck('name')
            ->map(fn ($p) => trim((string) $p))
            ->filter()
            ->unique()
            ->values();

        $usedPermissions = collect($this->collectUsedPermissions())
            ->map(fn ($p) => trim((string) $p))
            ->filter()
            ->unique()
            ->values();

        /** @var array<int,string> $missingInDb */
        $missingInDb = $usedPermissions->diff($dbPermissions)->sort()->values()->all();
        /** @var array<int,string> $unusedInCode */
        $unusedInCode = $dbPermissions->diff($usedPermissions)->sort()->values()->all();

        $this->info("Guard: {$guard}");
        $this->line('-----------------------------');
        $this->line('Permisos detectados en código: '.$usedPermissions->count());
        $this->line('Permisos existentes en BD:     '.$dbPermissions->count());
        $this->newLine();

        if (count($missingInDb) === 0) {
            $this->info('OK: No hay permisos usados en código que falten en BD.');
        } else {
            $this->error('FALTANTES EN BD (usados en código): '.count($missingInDb));
            foreach ($missingInDb as $perm) {
                $suggestions = $this->suggestSimilar($perm, $dbPermissions);
                if ($suggestions->isEmpty()) {
                    $this->line("- {$perm}");

                    continue;
                }

                $this->line("- {$perm}  (quizás: ".$suggestions->implode(', ').')');
            }
        }

        $this->newLine();
        if ($this->option('show-unused')) {
            if (count($unusedInCode) === 0) {
                $this->info('No hay permisos en BD que no se usen en código.');
            } else {
                $this->warn('EN BD PERO NO USADOS EN CÓDIGO: '.count($unusedInCode));
                foreach ($unusedInCode as $perm) {
                    $this->line("- {$perm}");
                }
            }
        } else {
            $this->line('Permisos en BD no usados en código: '.count($unusedInCode).' (usa --show-unused para listarlos)');
        }

        if ($this->option('fail') && count($missingInDb) > 0) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int,string>
     */
    private function collectUsedPermissions(): array
    {
        $paths = [
            base_path('routes/web.php'),
            base_path('app/Http'),
            base_path('resources/views'),
            base_path('resources/js/components'),
            base_path('resources/js/helpers'),
            base_path('resources/js/app.js'),
        ];

        foreach ((array) $this->option('paths') as $extra) {
            $extra = (string) $extra;
            if ($extra === '') {
                continue;
            }

            $paths[] = File::isAbsolutePath($extra) ? $extra : base_path($extra);
        }

        $files = $this->expandToFiles($paths);

        $perms = [];
        foreach ($files as $file) {
            try {
                $content = File::get($file);
            } catch (\Throwable) {
                continue;
            }

            $perms = array_merge($perms, $this->extractPermissionsFromContent($content));
        }

        return $this->normalizePermissions($perms);
    }

    /**
     * @param  array<int,string>  $paths
     * @return array<int,string>
     */
    private function expandToFiles(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            if (! $path || ! File::exists($path)) {
                continue;
            }

            if (File::isFile($path)) {
                $files[] = $path;

                continue;
            }

            // Evitar vendor/plantilla (no relevante y muy pesado)
            if (str_contains(str_replace('\\', '/', $path), 'resources/js/admin-wrap')) {
                continue;
            }

            foreach (File::allFiles($path) as $file) {
                $ext = strtolower($file->getExtension());
                if (! in_array($ext, ['php', 'blade.php', 'js', 'vue'], true)) {
                    // "blade.php" llega como ext "php"; por eso filtramos también por nombre abajo.
                    // Este filtro por extensión solo descarta lo claramente irrelevante.
                }

                $name = $file->getFilename();
                $fullPath = $file->getPathname();

                // Limitar a tipos relevantes
                if (
                    str_ends_with($name, '.php') ||
                    str_ends_with($name, '.blade.php') ||
                    str_ends_with($name, '.js') ||
                    str_ends_with($name, '.vue')
                ) {
                    // Excluir assets de plantilla
                    $normalized = str_replace('\\', '/', $fullPath);
                    if (str_contains($normalized, 'resources/js/admin-wrap')) {
                        continue;
                    }
                    $files[] = $fullPath;
                }
            }
        }

        // Dedupe
        return array_values(array_unique($files));
    }

    /**
     * @return array<int,string>
     */
    private function extractPermissionsFromContent(string $content): array
    {
        $perms = [];

        // 1) Middleware "permission:foo|bar" (routes/web.php, controllers, etc)
        if (preg_match_all('/permission:([^\'"]+)/', $content, $m)) {
            foreach ($m[1] as $chunk) {
                foreach (explode('|', (string) $chunk) as $p) {
                    $perms[] = $p;
                }
            }
        }

        // 2) Blade: @can('perm')
        if (preg_match_all('/@can\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $m)) {
            foreach ($m[1] as $p) {
                $perms[] = $p;
            }
        }

        // 3) Blade: @canany([...]) / hasAnyPermission([...])
        $perms = array_merge($perms, $this->extractQuotedArrayArgs($content, '@canany'));
        $perms = array_merge($perms, $this->extractQuotedArrayArgs($content, 'hasAnyPermission'));

        // 4) Vue/JS: permisos.includes('perm')
        if (preg_match_all('/\bpermisos\.includes\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $m)) {
            foreach ($m[1] as $p) {
                $perms[] = $p;
            }
        }

        // 5) Vue/JS: can('perm')
        if (preg_match_all('/\bcan\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $m)) {
            foreach ($m[1] as $p) {
                $perms[] = $p;
            }
        }

        // 6) Vue/JS: canAny(['a','b'])
        $perms = array_merge($perms, $this->extractQuotedArrayArgs($content, 'canAny'));

        return $perms;
    }

    /**
     * Extrae strings entre comillas dentro de un primer argumento array.
     * Soporta: fnName(['a','b']) o @canany(['a','b'])
     *
     * @return array<int,string>
     */
    private function extractQuotedArrayArgs(string $content, string $fnName): array
    {
        $perms = [];

        $regex = '/'.preg_quote($fnName, '/').'\(\s*\[([\s\S]*?)\]\s*\)/m';
        if (! preg_match_all($regex, $content, $m)) {
            return $perms;
        }

        foreach ($m[1] as $inside) {
            if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', (string) $inside, $strings)) {
                foreach ($strings[1] as $p) {
                    $perms[] = $p;
                }
            }
        }

        return $perms;
    }

    /**
     * @param  array<int,string>  $perms
     * @return array<int,string>
     */
    private function normalizePermissions(array $perms): array
    {
        $out = [];
        foreach ($perms as $p) {
            $p = trim((string) $p);
            if ($p === '') {
                continue;
            }

            // Limpieza básica de basura común
            $p = trim($p, " \t\n\r\0\x0B,)]}");

            // Evitar falsos positivos típicos
            if (str_contains($p, ' ')) {
                continue;
            }

            $out[] = $p;
        }

        return $out;
    }

    private function suggestSimilar(string $needle, Collection $haystack): Collection
    {
        $needle = (string) $needle;
        if ($needle === '') {
            return collect();
        }

        // Heurística simple: sugerir hasta 3 con distancia corta.
        return $haystack
            ->map(function (string $candidate) use ($needle) {
                $distance = levenshtein($needle, $candidate);

                return ['name' => $candidate, 'distance' => $distance];
            })
            ->sortBy('distance')
            ->filter(fn (array $x) => $x['distance'] <= 3)
            ->take(3)
            ->pluck('name')
            ->values();
    }
}
