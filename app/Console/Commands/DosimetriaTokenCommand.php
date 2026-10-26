<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DosimetriaTokenCommand extends Command
{
    /**
     * Nombre y firma del comando en consola.
     *
     * @var string
     */
    protected $signature = "dosimetria:token
                            {--user= : Email o ID del usuario emisor (por defecto administrador@administrador.com)}
                            {--name=agente-dosimetria : Nombre descriptivo del token Sanctum}
                            {--revoke : Revoca tokens anteriores con el mismo nombre antes de emitir uno nuevo}";

    /**
     * Descripción textual del comando.
     *
     * @var string
     */
    protected $description = "Genera o renueva el token Sanctum Bearer para los agentes de Dosimetría Radiológica.";

    /**
     * Ejecuta el proceso de emisión de token Sanctum para el agente de dosimetría.
     *
     * Flujo del proceso:
     * 1. Resuelve el usuario emisor a partir del email o ID suministrado (o administrador por defecto).
     * 2. Si se solicita la bandera --revoke, elimina tokens previos con el mismo nombre.
     * 3. Crea y firma un nuevo Personal Access Token mediante Laravel Sanctum.
     * 4. Imprime en consola el token en texto plano y el fragmento de configuración JSON listo para desplegar.
     *
     * @return int Código de salida del comando (Command::SUCCESS o Command::FAILURE)
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-26
     */
    public function handle(): int
    {
        $userIdentifier = $this->option("user") ?: "administrador@administrador.com";
        $tokenName = (string) $this->option("name") ?: "agente-dosimetria";
        $shouldRevoke = (bool) $this->option("revoke");

        $user = is_numeric($userIdentifier)
            ? User::find((int) $userIdentifier)
            : User::where("email", $userIdentifier)->first();

        if (! $user) {
            $user = User::whereHas("roles", function ($q) {
                $q->where("name", "Administrador");
            })->first() ?: User::first();
        }

        if (! $user) {
            $this->error("[ERROR] No se encontró ningún usuario válido para emitir el token.");

            return Command::FAILURE;
        }

        if ($shouldRevoke) {
            $deletedCount = $user->tokens()->where("name", $tokenName)->delete();
            if ($deletedCount > 0) {
                $this->warn("Se revocaron {$deletedCount} token(s) previo(s) con el nombre '{$tokenName}'.");
            }
        }

        if ($user->password_changed_at === null) {
            $user->password_changed_at = now();
            $user->save();
        }

        // Crear nuevo Personal Access Token
        $tokenResult = $user->createToken($tokenName, ["*"]);
        $plainTextToken = $tokenResult->plainTextToken;

        $this->newLine();
        $this->info("=========================================================================");
        $this->info("       TOKEN BEARER GENERADO PARA AGENTE DE DOSIMETRÍA RADIOLÓGICA      ");
        $this->info("=========================================================================");
        $this->line(" Usuario Emisor : <comment>{$user->name}</comment> ({$user->email}) [ID: {$user->id}]");
        $this->line(" Nombre Token   : <comment>{$tokenName}</comment>");
        $this->line(" Creado el      : <comment>".now()->format("Y-m-d H:i:s")."</comment>");
        $this->newLine();

        $this->line(" <fg=bright-white;bg=blue> TOKEN BEARER (Copiar y pegar en config.json): </>");
        $this->line(" <fg=bright-green;options=bold>{$plainTextToken}</>");
        $this->newLine();

        $this->info("-------------------------------------------------------------------------");
        $this->info(" Fragmento listo para pegar en config.json:");
        $this->info("-------------------------------------------------------------------------");
        $jsonSnippet = json_encode([
            "api_url" => "https://10.35.47.4/lidiag-update/public/api/dosimetria/registros",
            "api_token" => $plainTextToken,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->line($jsonSnippet);
        $this->info("=========================================================================");
        $this->newLine();

        return Command::SUCCESS;
    }
}
