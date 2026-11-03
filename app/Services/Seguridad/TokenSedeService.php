<?php

namespace App\Services\Seguridad;

use App\Helpers\AuditLogger;
use App\Models\Configuracion\ConfigSede;
use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class TokenSedeService extends BaseService
{
    /**
     * Correo electrónico institucional del usuario técnico de servicio para los Agentes Edge.
     */
    public const SERVICE_USER_EMAIL = "agente.edge@lidiag.gov.co";

    /**
     * Obtiene o crea de forma idempotente el usuario técnico de sistema propietario de los tokens.
     *
     * Flujo del proceso:
     * 1. Busca el usuario técnico por correo electrónico estandarizado.
     * 2. Si no existe, lo crea con credenciales seguras aleatorias y estado activo.
     * 3. Retorna la instancia de usuario lista para emisión o consulta de tokens.
     *
     * @return User Instancia del usuario técnico de servicio
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function obtenerUsuarioServicio(): User
    {
        return User::firstOrCreate(
            ["email" => self::SERVICE_USER_EMAIL],
            [
                "name" => "Agente Edge LIDiag",
                "num_docu" => "0000000000",
                "password" => Hash::make(Str::random(40)),
                "estado" => 1,
            ]
        );
    }

    /**
     * Lista todas las sedes institucionales con el estado de su token de acceso Edge.
     *
     * Flujo del proceso:
     * 1. Consulta las sedes activas de la red registradas en config_sedes.
     * 2. Obtiene todos los tokens registrados para el usuario de servicio.
     * 3. Mapea cada sede con los metadatos de su token (activo, fecha de creación, último uso).
     * 4. Retorna el consolidado estructurado para visualización en el panel administrativo.
     *
     * @return array Consolidado de sedes con estado de tokens de telemetría
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function listarSedesConTokens(): array
    {
        $sedes = ConfigSede::select([
            "id",
            "nom_sede",
            "ip_dcm",
            "bd_dcm",
            "puerto_dcm",
            "usuario_dcm",
            "estado",
        ])
            ->orderBy("id", "asc")
            ->get();

        $usuarioServicio = $this->obtenerUsuarioServicio();
        $tokens = $usuarioServicio->tokens()->get()->keyBy(function ($token) {
            // Convención de nombres: agente-edge:sede:{sede_id}:{slug}
            if (preg_match('/^agente-edge:sede:(\d+):/', $token->name, $matches)) {
                return (int) $matches[1];
            }

            return null;
        });

        $items = $sedes->map(function ($sede) use ($tokens) {
            $token = $tokens->get($sede->id);

            return [
                "sede_id" => $sede->id,
                "nom_sede" => $sede->nom_sede,
                "ip_dcm" => $sede->ip_dcm,
                "bd_dcm" => $sede->bd_dcm,
                "puerto_dcm" => $sede->puerto_dcm,
                "usuario_dcm" => $sede->usuario_dcm,
                "tiene_token" => $token !== null,
                "token_id" => $token?->id,
                "token_name" => $token?->name,
                "created_at" => $token?->created_at?->format("Y-m-d H:i:s"),
                "last_used_at" => $token?->last_used_at?->format("Y-m-d H:i:s"),
                "ultimo_uso_legible" => $token?->last_used_at ? $token->last_used_at->diffForHumans() : "Nunca",
                "estado_sede" => (bool) $sede->estado,
            ];
        });

        return $this->success([
            "sedes" => $items,
            "total_sedes" => $items->count(),
            "sedes_con_token" => $items->where("tiene_token", true)->count(),
            "usuario_servicio" => [
                "id" => $usuarioServicio->id,
                "name" => $usuarioServicio->name,
                "email" => $usuarioServicio->email,
            ],
        ], "Consolidado de tokens por sede obtenido con éxito.");
    }

    /**
     * Genera un nuevo token de acceso para una sede hospitalaria implementando rotación segura.
     *
     * Flujo del proceso:
     * 1. Valida la existencia de la sede seleccionada.
     * 2. Revoca y elimina de forma atómica cualquier token activo previo de la sede para evitar credenciales huérfanas.
     * 3. Genera un nuevo token unificado permanente mediante Laravel Sanctum con habilidades completas.
     * 4. Prepara el bloque de configuración institucional `config.json` para entrega y descarga inmediata.
     * 5. Registra el evento en la auditoría general del sistema.
     *
     * @param  int  $sedeId  Identificador institucional de la sede
     * @return array Token en texto plano y objeto de configuración para despliegue
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function generarTokenSede(int $sedeId): array
    {
        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            return $this->error("La sede con ID {$sedeId} no existe en el sistema.", 404);
        }

        $usuarioServicio = $this->obtenerUsuarioServicio();
        $prefijoToken = "agente-edge:sede:{$sedeId}:";

        // 1. Rotación segura: Revocar tokens previos de la sede
        $usuarioServicio->tokens()
            ->where("name", "like", "{$prefijoToken}%")
            ->delete();

        // 2. Generación del nuevo token
        $slug = Str::slug($sede->nom_sede);
        $nombreToken = "{$prefijoToken}{$slug}";
        $habilidades = ["dosimetria", "backups", "telemetria", "*"];

        $sanctumToken = $usuarioServicio->createToken($nombreToken, $habilidades);
        $plainTextToken = $sanctumToken->plainTextToken;

        // 3. Estructuración del archivo config.json listo para producción
        $configJson = $this->construirConfigJson($sede, $plainTextToken);

        // 4. Registro de auditoría
        AuditLogger::log(
            "crear",
            $usuarioServicio,
            "Se generó y rotó el token de acceso del Agente Edge para la sede: {$sede->nom_sede} (ID: {$sedeId}).",
            null,
            [
                "sede_id" => $sedeId,
                "sede_nombre" => $sede->nom_sede,
                "token_id" => $sanctumToken->accessToken->id,
                "token_name" => $nombreToken,
            ]
        );

        return $this->success([
            "sede" => [
                "id" => $sede->id,
                "nom_sede" => $sede->nom_sede,
            ],
            "token_id" => $sanctumToken->accessToken->id,
            "token_name" => $nombreToken,
            "plain_text_token" => $plainTextToken,
            "config_json" => $configJson,
        ], "Token generado exitosamente con rotación automática para {$sede->nom_sede}.");
    }

    /**
     * Revoca y elimina definitivamente un token de sede de la base de datos.
     *
     * Flujo del proceso:
     * 1. Localiza el token de acceso personal por su identificador.
     * 2. Elimina el registro impidiendo cualquier conexión futura del agente que lo utilizaba.
     * 3. Registra la revocación en el log de auditoría.
     *
     * @param  int  $tokenId  Identificador del token en personal_access_tokens
     * @return array Confirmación de revocación
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function revocarToken(int $tokenId): array
    {
        $token = PersonalAccessToken::find($tokenId);
        if (! $token) {
            return $this->error("El token solicitado no existe o ya ha sido revocado.", 404);
        }

        $tokenName = $token->name;
        $token->delete();

        AuditLogger::log(
            "eliminar",
            null,
            "Se revocó manualmente el token de acceso de Agente Edge: {$tokenName} (ID: {$tokenId}).",
            ["token_id" => $tokenId, "name" => $tokenName],
            null
        );

        return $this->success(null, "Token de acceso revocado correctamente.");
    }

    /**
     * Genera la estructura de configuración `config.json` para una sede.
     *
     * Flujo del proceso:
     * 1. Resuelve los parámetros de red, base de datos local y QNAP conforme a la sede.
     * 2. Incorpora la URL canónica institucional de la API y el token provisto.
     * 3. Devuelve la estructura en formato array serializable a JSON.
     *
     * @param  ConfigSede  $sede  Instancia del modelo de sede
     * @param  string  $apiToken  Token de acceso plano o marcador de reemplazo
     * @return array Estructura de configuración institucional
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-03
     */
    public function construirConfigJson(ConfigSede $sede, string $apiToken): array
    {
        $slug = Str::slug($sede->nom_sede);
        $slugSub = strtolower(str_replace("-", "", $slug));

        $qnapShares = [
            1 => ["share_bkp" => "backups-db-espinal", "share_img" => "img-dicom-espinal", "user" => "Dcm-Espinal"],
            2 => ["share_bkp" => "backups-db-ladorada", "share_img" => "img-dicom-ladorada", "user" => "Dcm-Ladorada"],
            3 => ["share_bkp" => "backups-db-chaparral", "share_img" => "img-dicom-chaparral", "user" => "Dcm-Chaparral"],
            4 => ["share_bkp" => "backups-db-ibague", "share_img" => "img-dicom-ibague", "user" => "Dcm-Ibague"],
            5 => ["share_bkp" => "backups-db-libano", "share_img" => "img-dicom-libano", "user" => "Dcm-Libano"],
            6 => ["share_bkp" => "backups-db-honda", "share_img" => "img-dicom-honda", "user" => "Dcm-Honda"],
            7 => ["share_bkp" => "backups-db-rovira", "share_img" => "img-dicom-rovira", "user" => "Dcm-Rovira"],
        ];

        $shareData = $qnapShares[$sede->id] ?? [
            "share_bkp" => "backups-db-{$slug}",
            "share_img" => "img-dicom-{$slug}",
            "user" => "Dcm-".ucfirst($slugSub),
        ];

        return [
            "sede_id" => (int) $sede->id,
            "sede_nombre" => $sede->nom_sede,
            "qnap_ip" => "10.35.47.21",
            "qnap_user" => $shareData["user"],
            "qnap_password" => "salud2026*",
            "qnap_share_backups" => $shareData["share_bkp"],
            "qnap_share_imagenes" => $shareData["share_img"],
            "db_host" => $sede->ip_dcm ?: "127.0.0.1",
            "db_port" => (int) ($sede->puerto_dcm ?: 3306),
            "db_name" => $sede->bd_dcm ?: "pacsdb",
            "db_user" => $sede->usuario_dcm ?: "lidiag",
            "db_password" => "Lidiag@852",
            "mysqldump_path" => "C:/Program Files/MySQL/MySQL Server 5.7/bin/mysqldump.exe",
            "destination_dir" => "C:/backups_dcm",
            "retention_days" => 30,
            "local_images_dir" => "D:/img_dcm",
            "sync_interval_seconds" => 180,
            "threads" => 8,
            "min_free_space_gb" => 10,
            "api_url" => url("/api/dosimetria/registros"),
            "api_token" => $apiToken,
            "dosimetria_salt" => "6d9811e8475ff336b07329cd422069f8680a1d353295c6766a496edaff68e46b",
        ];
    }
}
