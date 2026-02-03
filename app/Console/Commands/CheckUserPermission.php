<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class CheckUserPermission extends Command
{
    /**
     * Usage:
     * - php artisan permissions:user-check --search=carlos --perm=estudio.leerestudio --perm=estudio.leerEstudio
     * - php artisan permissions:user-check 5 --perm=estudio.leerestudio
     */
    protected $signature = 'permissions:user-check
                            {user? : ID, email o nombre exacto}
                            {--search= : Busca usuarios por nombre/email (like) y lista candidatos}
                            {--perm=* : Permisos a validar (puedes pasar varios --perm=...)}';

    protected $description = 'Diagnóstico: verifica roles/permisos de un usuario (Spatie).';

    public function handle(): int
    {
        $registrar = app(PermissionRegistrar::class);
        $forgot = $registrar->forgetCachedPermissions();

        $search = trim((string) ($this->option('search') ?? ''));
        $perms = (array) ($this->option('perm') ?? []);
        $perms = array_values(array_filter(array_map('trim', $perms)));

        if ($search !== '') {
            $q = User::query()
                ->where('name', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');

            $users = $q->limit(25)->get(['id', 'name', 'email']);

            if ($users->isEmpty()) {
                $this->warn("Sin resultados para --search={$search}");
                return Command::SUCCESS;
            }

            $this->info("Candidatos (max 25) para --search={$search}:");
            foreach ($users as $u) {
                $this->line("- id={$u->id}  name=\"{$u->name}\"  email=\"{$u->email}\"");
            }
            return Command::SUCCESS;
        }

        $identifier = $this->argument('user');
        if (!$identifier) {
            $this->error('Debes pasar {user} (id/email/nombre exacto) o usar --search=');
            return Command::FAILURE;
        }

        $user = null;
        if (is_numeric($identifier)) {
            $user = User::query()->find((int) $identifier);
        }
        if (!$user) {
            $user = User::query()
                ->where('email', (string) $identifier)
                ->orWhere('name', (string) $identifier)
                ->first();
        }

        if (!$user) {
            $this->error("No encontré usuario: {$identifier}");
            return Command::FAILURE;
        }

        $this->info("Usuario: id={$user->id}  name=\"{$user->name}\"  email=\"{$user->email}\"");

        $roles = $user->getRoleNames()->values();
        $this->line('Roles (' . $roles->count() . '): ' . ($roles->isEmpty() ? '—' : $roles->implode(', ')));

        $directPerms = $user->getDirectPermissions()->pluck('name')->sort()->values();
        $this->line('Permisos directos (' . $directPerms->count() . '): ' . ($directPerms->isEmpty() ? '—' : $directPerms->implode(', ')));

        $allPerms = $user->getAllPermissions()->pluck('name')->sort()->values();
        $this->line('Permisos efectivos (roles+directos) (' . $allPerms->count() . ').');

        if (!empty($perms)) {
            $this->newLine();
            $possibleGuards = Guard::getNames($user)->values();
            $defaultGuard = Guard::getDefaultName($user);
            $this->line('Guards usuario: posibles=' . ($possibleGuards->isEmpty() ? '—' : $possibleGuards->implode(', ')) . '  default=' . $defaultGuard);
            $this->line('Spatie cache: key=' . PermissionRegistrar::$cacheKey . '  forget=' . ($forgot ? 'OK' : 'NO/FAIL'));

            $this->newLine();
            $this->info('Chequeo de permisos:');

            $check = function (User $user, string $perm, ?string $guard = null): string {
                try {
                    return $user->hasPermissionTo($perm, $guard) ? 'SI' : 'NO';
                } catch (PermissionDoesNotExist $e) {
                    return 'ERROR(PermissionDoesNotExist)';
                }
            };

            foreach ($perms as $p) {
                $permGuards = Permission::query()
                    ->where('name', $p)
                    ->orderBy('id')
                    ->pluck('guard_name')
                    ->unique()
                    ->sort()
                    ->values();
                $exists = $permGuards->isNotEmpty() ? 'SI' : 'NO';
                $guardsStr = $permGuards->isEmpty() ? '—' : $permGuards->implode(', ');

                $permIds = Permission::query()
                    ->where('name', $p)
                    ->orderBy('id')
                    ->pluck('id')
                    ->values();
                $idsStr = $permIds->isEmpty() ? '—' : $permIds->implode(', ');

                $hasDefault = $check($user, $p, null);
                $hasWeb = $check($user, $p, 'web');
                $hasApi = $check($user, $p, 'api');
                $this->line("- {$p}: tiene(default)={$hasDefault}  tiene(web)={$hasWeb}  tiene(api)={$hasApi}  existe_en_bd={$exists}  ids_en_bd={$idsStr}  guards_en_bd={$guardsStr}");
            }
        } else {
            $this->newLine();
            $this->line('Tip: agrega --perm=... para validar permisos específicos.');
        }

        return Command::SUCCESS;
    }
}
