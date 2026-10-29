<?php

namespace App\Services\Telegram;

use App\Exports\ProduccionDetalladaExport;
use App\Exports\ReportesEstudioPAC;
use App\Models\Configuracion\ConfigSede;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class TelegramBotHandlerService
{
    protected TelegramService $telegramService;

    /**
     * Inicializa el servicio inyectando la instancia de comunicación con Telegram.
     *
     * Flujo del proceso:
     * 1. Almacena la instancia inyectada de TelegramService.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function __construct(TelegramService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Registra los comandos nativos del bot en la interfaz de Telegram para acceso rápido.
     *
     * Flujo del proceso:
     * 1. Define la lista completa de comandos asistenciales, ejecutivos y de monitoreo.
     * 2. Despacha la configuración al endpoint setMyCommands mediante TelegramService.
     *
     * @return array Respuesta decodificada de Telegram API.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function registerBotCommands(): array
    {
        $commands = [
            ["command" => "menu", "description" => "📋 Menú táctil interactivo de reportes"],
            ["command" => "red", "description" => "🌐 Consolidado de toda la red hospitalaria"],
            ["command" => "produccion", "description" => "📊 Producción por sede (estudios leídos)"],
            ["command" => "tomados", "description" => "🏥 Reporte de estudios tomados en DCM/PACS"],
            ["command" => "retrasados", "description" => "🚨 Semáforo de estudios represados (>24h)"],
            ["command" => "citas", "description" => "💬 Estadísticas de citas WhatsApp"],
            ["command" => "sedes", "description" => "🏥 Lista de sedes e identificadores"],
            ["command" => "dd", "description" => "💾 Monitor de discos duros PACS"],
            ["command" => "ayuda", "description" => "❓ Guía completa y comandos"],
        ];

        return $this->telegramService->setMyCommands($commands);
    }

    /**
     * Procesa una actualización entrante enviada por Telegram (Webhook o Polling).
     *
     * Flujo del proceso:
     * 1. Extrae los datos del mensaje directo o callback_query.
     * 2. Valida la autorización del Chat ID contra la lista blanca configurada.
     * 3. Si no está autorizado, responde con notificación informativa del Chat ID.
     * 4. Direcciona el mensaje al despachador de comandos o de callbacks interactivos.
     *
     * @param  array  $update  Estructura completa de la actualización entregada por Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function handleUpdate(array $update): void
    {
        if (isset($update["callback_query"])) {
            $this->handleCallbackQuery($update["callback_query"]);

            return;
        }

        if (! isset($update["message"])) {
            return;
        }

        $message = $update["message"];
        $chat = $message["chat"] ?? [];
        $from = $message["from"] ?? [];
        $chatId = (string) ($chat["id"] ?? "");
        $text = trim($message["text"] ?? "");

        if (! $chatId || $text === "") {
            return;
        }

        // Verificación de lista blanca / Auto-autorización mediante PIN
        if (! $this->isChatAuthorized($chatId)) {
            $this->handleUnauthorizedMessage($chatId, $text, $from);

            return;
        }

        $this->handleCommand($chatId, $text);
    }

    /**
     * Valida si un Chat ID se encuentra autorizado para interactuar con el bot.
     *
     * Flujo del proceso:
     * 1. Consulta la lista de chats persistidos en el almacenamiento local JSON.
     * 2. Si el Chat ID ya fue auto-autorizado con PIN, permite el acceso.
     * 3. Consulta la lista blanca estática configurada en config('telegram.allowed_chat_ids').
     * 4. Si la lista estática contiene el Chat ID, permite el acceso.
     * 5. Si no hay lista estática ni PIN configurado, permite el acceso por defecto.
     *
     * @param  string  $chatId  Identificador del chat a verificar.
     * @return bool Verdadero si el chat se encuentra autorizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    protected function isChatAuthorized(string $chatId): bool
    {
        $persisted = $this->getPersistedAuthorizedChats();
        if (in_array($chatId, array_map("strval", array_keys($persisted)), true)) {
            return true;
        }

        $allowed = config("telegram.allowed_chat_ids", []);
        if (! empty($allowed) && in_array($chatId, array_map("strval", $allowed), true)) {
            return true;
        }

        if (empty($allowed) && empty(config("telegram.access_pin"))) {
            return true;
        }

        return false;
    }

    /**
     * Obtiene el listado de chats de Telegram autorizados persistidos en disco.
     *
     * Flujo del proceso:
     * 1. Verifica si existe el archivo telegram_authorized_chats.json.
     * 2. Decodifica el JSON como arreglo asociativo.
     * 3. Retorna la estructura de chats autorizados.
     *
     * @return array<string, array> Mapa de chat_id => metadata.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    public function getPersistedAuthorizedChats(): array
    {
        $filePath = storage_path("app_lidiag/telegram_authorized_chats.json");
        if (! file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Persiste un Chat ID autorizado en el almacenamiento local JSON.
     *
     * Flujo del proceso:
     * 1. Crea el directorio app_lidiag si no existe.
     * 2. Lee los registros existentes y agrega el nuevo chat_id con metadatos de usuario y fecha.
     * 3. Guarda el archivo con bloqueo exclusivo.
     *
     * @param  string  $chatId  Identificador del chat de Telegram.
     * @param  array  $from  Datos del remitente (first_name, username, etc.).
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    public function persistAuthorizedChat(string $chatId, array $from = []): void
    {
        $dir = storage_path("app_lidiag");
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $filePath = $dir."/telegram_authorized_chats.json";
        $current = $this->getPersistedAuthorizedChats();

        $current[$chatId] = [
            "authorized_at" => now()->format("Y-m-d H:i:s"),
            "first_name" => $from["first_name"] ?? "Usuario",
            "username" => $from["username"] ?? null,
        ];

        file_put_contents($filePath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    /**
     * Revoca la autorización de un Chat ID en el almacenamiento persistente.
     *
     * Flujo del proceso:
     * 1. Lee los chats autorizados y elimina la entrada correspondiente.
     * 2. Guarda el archivo actualizado.
     *
     * @param  string  $chatId  Identificador del chat a revocar.
     * @return bool Verdadero si el chat existía y fue removido.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    public function revokeAuthorizedChat(string $chatId): bool
    {
        $dir = storage_path("app_lidiag");
        $filePath = $dir."/telegram_authorized_chats.json";
        $current = $this->getPersistedAuthorizedChats();

        if (! isset($current[$chatId])) {
            return false;
        }

        unset($current[$chatId]);
        file_put_contents($filePath, json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

        return true;
    }

    /**
     * Procesa mensajes de usuarios no autorizados, validando PIN institucional o solicitándolo.
     *
     * Flujo del proceso:
     * 1. Limpia el texto recibido e identifica si el remitente envió la clave o comando de acceso.
     * 2. Si el texto coincide con TELEGRAM_ACCESS_PIN, auto-autoriza el chat y envía bienvenida.
     * 3. Si no coincide, responde solicitando la clave institucional con instrucciones amigables.
     *
     * @param  string  $chatId  Identificador del chat de Telegram.
     * @param  string  $text  Mensaje enviado por el usuario.
     * @param  array  $from  Datos de perfil del usuario en Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    protected function handleUnauthorizedMessage(string $chatId, string $text, array $from): void
    {
        $accessPin = (string) config("telegram.access_pin", "Lidiag2026*");
        $cleanInput = trim($text);

        // Si enviaron /acceso <PIN>, /pin <PIN>, /clave <PIN> o /login <PIN>
        if (str_starts_with($cleanInput, "/")) {
            $parts = preg_split("/\s+/", $cleanInput);
            if (in_array(strtolower($parts[0]), ["/acceso", "/pin", "/clave", "/login"], true) && isset($parts[1])) {
                $cleanInput = trim($parts[1]);
            }
        }

        // Validación de coincidencia exacta de PIN
        if ($accessPin !== "" && hash_equals($accessPin, $cleanInput)) {
            $this->persistAuthorizedChat($chatId, $from);

            $this->telegramService->sendMessage(
                $chatId,
                "✅ <b>¡Acceso Autorizado Exitosamente!</b>\n\n"
                ."Tu cuenta ha sido vinculada y habilitada para consultar los reportes asistenciales y administrativos de LIDiag.\n\n"
                ."<i>¡Bienvenido al sistema!</i>"
            );

            $this->sendTipoReporteSelectionKeyboard($chatId);

            return;
        }

        // Si no coincide la clave, enviar solicitud descriptiva
        $firstName = htmlspecialchars($from["first_name"] ?? "Colega", ENT_QUOTES, "UTF-8");
        $isFirstInteraction = in_array(strtolower($cleanInput), ["/start", "start", "/help", "/ayuda", "hola"], true);

        if ($isFirstInteraction) {
            $textMsg = "🔒 <b>Acceso Institucional Requerido — LIDiag</b>\n\n"
                ."Hola <b>{$firstName}</b>. Para consultar la producción médica, estudios represados y estadísticas de la red hospitalaria, por favor escribe la <b>Clave de Acceso Institucional</b>:\n\n"
                ."👉 <i>Escribe la clave directamente como respuesta en este chat.</i>\n\n"
                ."<small>Tu Identificador: <code>{$chatId}</code></small>";
        } else {
            $textMsg = "❌ <b>Clave de acceso incorrecta</b>\n\n"
                ."La clave ingresada no es válida. Por favor verifica e intenta nuevamente.\n\n"
                ."👉 <i>Escribe la Clave Institucional en este chat:</i>\n\n"
                ."<small>Si no la conoces, solicítala al Administrador de LIDiag. (Tu ID: <code>{$chatId}</code>)</small>";
        }

        $this->telegramService->sendMessage($chatId, $textMsg);
    }

    /**
     * Despacha el comando de texto recibido a la acción correspondiente.
     *
     * Flujo del proceso:
     * 1. Normaliza la instrucción quitando arrobas y barras iniciales.
     * 2. Reconoce comandos /start, /ayuda, /help, /menu, /red, /sedes, /produccion, /tomados, /retrasados, /citas o de discos.
     * 3. Ejecuta la lógica correspondiente.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  string  $text  Texto completo del mensaje recibido.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function handleCommand(string $chatId, string $text): void
    {
        $clean = trim($text);
        if (str_starts_with($clean, "/")) {
            $clean = substr($clean, 1);
        }

        // Quitar mención de bot (ej: /produccion@salud_tolima_bot -> /produccion)
        if (str_contains($clean, "@")) {
            $parts = explode("@", $clean, 2);
            $clean = $parts[0].(isset($parts[1]) && str_contains($parts[1], " ") ? strstr($parts[1], " ") : "");
        }

        $tokens = preg_split("/\s+/", trim($clean));
        $cmd = strtolower($tokens[0] ?? "");
        $args = array_slice($tokens, 1);

        switch ($cmd) {
            case "start":
                $this->registerBotCommands();
                $this->telegramService->sendMessage(
                    $chatId,
                    "👋 <b>¡Hola! Bienvenido al Asistente Oficial — Salud Tolima / LIDiag</b>\n\n"
                    ."Puedes usar el menú táctil interactivo a continuación o escribir /ayuda para ver todos los comandos disponibles:"
                );
                $this->sendTipoReporteSelectionKeyboard($chatId);
                break;

            case "help":
            case "ayuda":
                $this->sendHelpMessage($chatId);
                break;

            case "menu":
            case "reporte":
            case "reportes":
                $this->sendTipoReporteSelectionKeyboard($chatId);
                break;

            case "red":
            case "consolidado":
                $periodoRed = strtolower($args[0] ?? "hoy");
                $this->generarYEnviarReporteRed($chatId, $periodoRed, $args[0] ?? null, $args[1] ?? null);
                break;

            case "retrasados":
            case "represados":
            case "sla":
            case "pendientes":
                $this->generarYEnviarReporteRetrasados($chatId);
                break;

            case "citas":
            case "agenda":
                $periodoCitas = strtolower($args[0] ?? "hoy");
                $this->generarYEnviarReporteCitas($chatId, null, $periodoCitas);
                break;

            case "sedes":
                $this->sendSedesMessage($chatId);
                break;

            case "usuarios":
            case "usuarios_telegram":
                $this->sendUsuariosAutorizadosMessage($chatId);
                break;

            case "revocar":
                $targetId = $args[0] ?? "";
                if (! $targetId) {
                    $this->telegramService->sendMessage($chatId, "⚠️ Uso: <code>/revocar &lt;chat_id&gt;</code>");
                    break;
                }
                if ($this->revokeAuthorizedChat($targetId)) {
                    $this->telegramService->sendMessage($chatId, "✅ Chat ID <code>{$targetId}</code> revocado exitosamente.");
                } else {
                    $this->telegramService->sendMessage($chatId, "ℹ️ El Chat ID <code>{$targetId}</code> no se encontraba en la lista auto-autorizada.");
                }
                break;

            case "produccion":
            case "prod":
                $this->processProduccionCommand($chatId, $args);
                break;

            case "tomados":
            case "dcm":
            case "pac":
            case "pacs":
                $this->processDcmCommand($chatId, $args);
                break;

            case "dd":
            case "disco":
            case "discos":
                $this->telegramService->sendMessage(
                    $chatId,
                    "💾 <b>MONITOR DE DISCOS DUROS (PACS)</b>\n\n"
                    ."Los repositorios DICOM son monitoreados por el agente autónomo en los servidores de cada hospital.\n\n"
                    ."🔔 <i>Si un repositorio baja del 15% (advertencia) o del 5% (crítico), el bot te enviará una alerta automática inmediatamente.</i>\n\n"
                    ."💡 Usa <code>/dd_alertas</code> para consultar los umbrales de riesgo."
                );
                break;

            case "dd_alertas":
            case "disco_alertas":
                $this->telegramService->sendMessage(
                    $chatId,
                    "⚙️ <b>UMBRALES DE ALERTA DE DISCOS (PACS):</b>\n\n"
                    ."🟡 <b>Advertencia:</b> Menor a 15% libre o menos de 50 GB.\n"
                    ."🔴 <b>Crítico:</b> Menor a 5% libre o menos de 20 GB (riesgo de rechazo C-STORE en modalidades).\n"
                    ."⏳ <b>Silenciador (Anti-spam):</b> 4 horas entre alertas repetidas.\n"
                    ."☀️ <b>Reporte diario matutino:</b> 08:00 AM."
                );
                break;

            case "dd_info":
            case "disco_info":
                $this->telegramService->sendMessage(
                    $chatId,
                    "🖥️ <b>AGENTE PACS — SALUD TOLIMA:</b>\n\n"
                    ."El monitor de discos se ejecuta en Python nativo en el servidor de cada sede hospitalaria.\n"
                    ."Paquete de instalación: <code>storage/app_lidiag/dcm_disk_monitor_telegram.zip</code>"
                );
                break;

            default:
                $this->telegramService->sendMessage(
                    $chatId,
                    "❓ Comando no reconocido: <code>/{$cmd}</code>\nUsa /menu para el menú táctil o /ayuda para ver todos los comandos."
                );
                break;
        }
    }

    /**
     * Envía el mensaje de bienvenida y catálogo de comandos disponibles.
     *
     * Flujo del proceso:
     * 1. Estructura el mensaje con los comandos asistenciales, de producción, DCM y citas.
     * 2. Despacha el mensaje mediante TelegramService.
     *
     * @param  string  $chatId  Identificador del chat.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function sendHelpMessage(string $chatId): void
    {
        $text = "👋 <b>¡Bienvenido al Asistente — Salud Tolima / LIDiag!</b>\n\n"
            ."🏥 <b>Sistema:</b> LIDiag Medical Imaging System\n"
            ."👤 <b>Usuario:</b> <code>{$chatId}</code> (Autorizado ✅)\n\n"
            ."📋 <b>MENÚ TÁCTIL INTERACTIVO:</b>\n"
            ."🔹 <code>/menu</code> — Asistente guiado por botones paso a paso.\n\n"
            ."🌐 <b>DASHBOARD Y CONSOLIDADO DE RED:</b>\n"
            ."🔹 <code>/red [periodo]</code> — Producción de toda la red hospitalaria (Espinal, Chaparral, Honda, Líbano, etc.).\n"
            ."🔹 <code>/retrasados</code> — Semáforo de estudios con más de 24h sin lectura y urgencias pendientes.\n"
            ."🔹 <code>/citas [periodo]</code> — Resumen de citas WhatsApp (aprobadas, rechazadas, pendientes).\n\n"
            ."📊 <b>1. PRODUCCIÓN POR SEDE (LEÍDOS Y FIRMADOS):</b>\n"
            ."🔹 <code>/produccion [sede] [periodo]</code>\n"
            ."   • <code>/produccion 1 mes</code> — Producción mensual por ID (1 = Espinal).\n"
            ."   • <code>/produccion 3 ayer</code> — Producción de ayer por ID (3 = Chaparral).\n"
            ."   • <code>/produccion 6 hoy</code> — Producción de hoy por ID (6 = Honda).\n"
            ."   • <code>/produccion espinal mes</code> — Búsqueda por nombre de sede.\n"
            ."   • <code>/produccion 1 2026-10-01 2026-10-15</code> — Rango personalizado.\n\n"
            ."🏥 <b>2. ESTUDIOS TOMADOS EN DCM / PACS:</b>\n"
            ."🔹 <code>/tomados [sede] [periodo]</code> o <code>/dcm [sede] [periodo]</code>\n"
            ."   • <code>/tomados 1 hoy</code> — Estudios tomados hoy en PACS Espinal.\n"
            ."   • <code>/tomados 3 mes</code> — Estudios tomados este mes en Chaparral.\n"
            ."   • <code>/tomados 6 semana</code> — Últimos 7 días en Honda.\n\n"
            ."🏥 <b>SEDES Y DISCOS DUROS:</b>\n"
            ."🔹 <code>/sedes</code> — Lista todas las sedes y sus números de ID.\n"
            ."🔹 <code>/dd</code> o <code>/disco</code> — Estado de repositorios DICOM.\n"
            ."🔹 <code>/dd_alertas</code> — Umbrales de advertencia y crítico.\n\n"
            ."💡 <i>Períodos aceptados: hoy, ayer, mes, semana o fechas YYYY-MM-DD.</i>";

        $this->telegramService->sendMessage($chatId, $text);
    }

    /**
     * Envía el listado de sedes activas en el sistema con sus identificadores y estado DCM.
     *
     * Flujo del proceso:
     * 1. Consulta las sedes activas en config_sedes.
     * 2. Construye un mensaje estructurado con código, nombre y soporte DCM.
     * 3. Envía el mensaje al usuario.
     *
     * @param  string  $chatId  Identificador del chat.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function sendSedesMessage(string $chatId): void
    {
        $sedes = ConfigSede::where("estado", true)->orderBy("id")->get(["id", "nom_sede", "ip_dcm"]);

        $lineas = ["🏥 <b>SEDES ACTIVAS EN LIDIAG:</b>\n"];
        foreach ($sedes as $s) {
            $hasDcm = ! empty($s->ip_dcm) ? " 📡 (PACS Activo)" : "";
            $lineas[] = "• <b>ID {$s->id}:</b> {$s->nom_sede}{$hasDcm}";
        }

        $lineas[] = "\n💡 <i>Comandos rápidos disponibles:</i>";
        $lineas[] = "📊 <b>Producción:</b> <code>/produccion [id] [periodo]</code> (ej: <code>/produccion 1 mes</code>)";
        $lineas[] = "🏥 <b>Estudios DCM:</b> <code>/tomados [id] [periodo]</code> (ej: <code>/tomados 1 mes</code>)";
        $lineas[] = "🌐 <b>Consolidado Red:</b> <code>/red mes</code> o <code>/red hoy</code>";
        $lineas[] = "📋 <b>Menú táctil:</b> <code>/menu</code>";

        $this->telegramService->sendMessage($chatId, implode("\n", $lineas));
    }

    /**
     * Paso 1 del Asistente: Despliega botones para elegir el tipo de reporte.
     *
     * Flujo del proceso:
     * 1. Construye el teclado inline con todas las categorías de reportes.
     * 2. Si se suministró messageId edita el mensaje en sitio, o envía uno nuevo.
     *
     * @param  string  $chatId  Identificador del chat.
     * @param  int|null  $messageId  Identificador opcional del mensaje a editar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function sendTipoReporteSelectionKeyboard(string $chatId, ?int $messageId = null): void
    {
        $markup = [
            "inline_keyboard" => [
                [
                    ["text" => "📊 1. Producción (Leídos / Firmados)", "callback_data" => "wiz:tipo:prod"],
                ],
                [
                    ["text" => "🏥 2. Estudios Tomados en DCM (PACS)", "callback_data" => "wiz:tipo:dcm"],
                ],
                [
                    ["text" => "🌐 3. Consolidado de Toda la Red", "callback_data" => "wiz:red:hoy"],
                ],
                [
                    ["text" => "🚨 4. Semáforo Represados (>24h)", "callback_data" => "wiz:retrasados"],
                    ["text" => "💬 5. Citas WhatsApp", "callback_data" => "wiz:citas:hoy"],
                ],
            ],
        ];

        $text = "📋 <b>CENTRO DE REPORTES Y ESTADÍSTICAS — LIDIAG</b>\n\n"
            ."Seleccione la consulta que desea realizar:";

        if ($messageId) {
            $this->telegramService->editMessageText($chatId, $messageId, $text, "HTML", $markup);
        } else {
            $this->telegramService->sendMessage($chatId, $text, "HTML", $markup);
        }
    }

    /**
     * Paso 2 del Asistente: Despliega un teclado interactivo para seleccionar la sede.
     *
     * Flujo del proceso:
     * 1. Consulta sedes activas en config_sedes.
     * 2. Genera botones inline agrupados de a 2 por fila con callback wizard contextual.
     * 3. Añade botón para volver al paso 1 (tipo de reporte).
     * 4. Edita el mensaje existente o envía uno nuevo.
     *
     * @param  string  $chatId  Identificador del chat.
     * @param  string  $tipo  Tipo de reporte ('prod' o 'dcm').
     * @param  int|null  $messageId  Identificador opcional del mensaje a editar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function sendSedeSelectionKeyboard(string $chatId, string $tipo = "prod", ?int $messageId = null): void
    {
        $sedes = ConfigSede::where("estado", true)->orderBy("id")->get();

        $tipoEtiqueta = $tipo === "dcm" ? "Estudios Tomados en DCM (PACS)" : "Producción (Leídos)";
        $keyboard = [];
        $currentRow = [];

        foreach ($sedes as $s) {
            $nombreCorto = explode("-", $s->nom_sede)[0];
            $nombreCorto = trim($nombreCorto);

            $currentRow[] = [
                "text" => "🏥 ".$nombreCorto,
                "callback_data" => "wiz:sede:{$tipo}:{$s->id}",
            ];

            if (count($currentRow) === 2) {
                $keyboard[] = $currentRow;
                $currentRow = [];
            }
        }

        if (! empty($currentRow)) {
            $keyboard[] = $currentRow;
        }

        // Botón de retorno al Paso 1
        $keyboard[] = [
            ["text" => "🔙 Volver al Menú Principal", "callback_data" => "wiz:menu"],
        ];

        $markup = ["inline_keyboard" => $keyboard];
        $text = "🏥 <b>{$tipoEtiqueta}</b>\nSeleccione la sede a consultar:";

        if ($messageId) {
            $this->telegramService->editMessageText($chatId, $messageId, $text, "HTML", $markup);
        } else {
            $this->telegramService->sendMessage($chatId, $text, "HTML", $markup);
        }
    }

    /**
     * Paso 3 del Asistente: Despliega botones para seleccionar el período del reporte.
     *
     * Flujo del proceso:
     * 1. Consulta la sede seleccionada para obtener su nombre.
     * 2. Construye botones con períodos predefinidos (Hoy, Ayer, Mes actual, 7 Días).
     * 3. Añade botón para volver al paso 2 (selección de sedes).
     * 4. Edita el mensaje existente o envía uno nuevo.
     *
     * @param  string  $chatId  Identificador del chat.
     * @param  string  $tipo  Tipo de reporte ('prod' o 'dcm').
     * @param  int  $sedeId  Identificador de la sede.
     * @param  int|null  $messageId  Identificador opcional del mensaje a editar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function sendPeriodoSelectionKeyboard(string $chatId, string $tipo, int $sedeId, ?int $messageId = null): void
    {
        $sede = ConfigSede::find($sedeId);
        $nombreSede = $sede ? $sede->nom_sede : "Sede #{$sedeId}";
        $tipoEtiqueta = $tipo === "dcm" ? "Estudios Tomados DCM" : "Producción";

        $markup = [
            "inline_keyboard" => [
                [
                    ["text" => "📅 Hoy", "callback_data" => "wiz:gen:{$tipo}:{$sedeId}:hoy"],
                    ["text" => "⏪ Ayer", "callback_data" => "wiz:gen:{$tipo}:{$sedeId}:ayer"],
                ],
                [
                    ["text" => "🗓️ Este Mes", "callback_data" => "wiz:gen:{$tipo}:{$sedeId}:mes"],
                    ["text" => "📆 Últimos 7 Días", "callback_data" => "wiz:gen:{$tipo}:{$sedeId}:semana"],
                ],
                [
                    ["text" => "🔙 Volver a Sedes", "callback_data" => "wiz:tipo:{$tipo}"],
                ],
            ],
        ];

        $text = "🏥 <b>{$tipoEtiqueta} — {$nombreSede}</b>\n"
            ."Seleccione el período a consultar:";

        if ($messageId) {
            $this->telegramService->editMessageText($chatId, $messageId, $text, "HTML", $markup);
        } else {
            $this->telegramService->sendMessage($chatId, $text, "HTML", $markup);
        }
    }

    /**
     * Procesa las interacciones con botones inline (callback_query).
     *
     * Flujo del proceso:
     * 1. Extrae los datos del callback y responde con toast para descartar el loading.
     * 2. Enruta según la acción (wiz:menu, wiz:tipo, wiz:sede, wiz:gen, wiz:red, wiz:retrasados, wiz:citas).
     * 3. Ejecuta la generación del reporte o actualiza la vista interactiva.
     *
     * @param  array  $callbackQuery  Datos del evento callback_query de Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $callbackQueryId = (string) ($callbackQuery["id"] ?? "");
        $message = $callbackQuery["message"] ?? [];
        $chat = $message["chat"] ?? [];
        $chatId = (string) ($chat["id"] ?? $callbackQuery["from"]["id"] ?? "");
        $messageId = isset($message["message_id"]) ? (int) $message["message_id"] : null;
        $data = $callbackQuery["data"] ?? "";

        if (! $chatId || ! $this->isChatAuthorized($chatId)) {
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId, "Acceso no autorizado", true);
            }

            return;
        }

        // Navegación: Retorno al Menú Principal
        if ($data === "wiz:menu") {
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId);
            }
            $this->sendTipoReporteSelectionKeyboard($chatId, $messageId);

            return;
        }

        // Navegación: Consolidado de Toda la Red
        if (str_starts_with($data, "wiz:red:")) {
            $periodo = substr($data, 8);
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId, "⏳ Consultando red hospitalaria...", false);
            }
            $this->generarYEnviarReporteRed($chatId, $periodo);

            return;
        }

        // Navegación: Semáforo de Retrasados
        if ($data === "wiz:retrasados") {
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId, "⏳ Evaluando estudios represados...", false);
            }
            $this->generarYEnviarReporteRetrasados($chatId);

            return;
        }

        // Navegación: Citas WhatsApp
        if (str_starts_with($data, "wiz:citas:")) {
            $periodo = substr($data, 10);
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId, "⏳ Consultando citas...", false);
            }
            $this->generarYEnviarReporteCitas($chatId, null, $periodo);

            return;
        }

        // Navegación: Paso 2 (Listado de sedes según tipo)
        if (str_starts_with($data, "wiz:tipo:")) {
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId);
            }
            $tipo = substr($data, 9);
            $this->sendSedeSelectionKeyboard($chatId, $tipo, $messageId);

            return;
        }

        // Navegación: Paso 3 (Listado de períodos según sede y tipo)
        if (str_starts_with($data, "wiz:sede:")) {
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId);
            }
            $parts = explode(":", $data);
            $tipo = $parts[2] ?? "prod";
            $sedeId = (int) ($parts[3] ?? 1);
            $this->sendPeriodoSelectionKeyboard($chatId, $tipo, $sedeId, $messageId);

            return;
        }

        // Ejecución de reporte desde el asistente
        if (str_starts_with($data, "wiz:gen:")) {
            if ($callbackQueryId !== "") {
                $this->telegramService->answerCallbackQuery($callbackQueryId, "⏳ Generando reporte...", false);
            }
            $parts = explode(":", $data);
            $tipo = $parts[2] ?? "prod";
            $sedeId = (int) ($parts[3] ?? 1);
            $periodo = $parts[4] ?? "hoy";

            if ($tipo === "dcm") {
                $this->generarYEnviarReporteDcm($chatId, $sedeId, $periodo);
            } else {
                $this->generarYEnviarReporte($chatId, $sedeId, $periodo);
            }

            return;
        }

        // Retrocompatibilidad con botones anteriores de producción
        if (str_starts_with($data, "sede:")) {
            $sedeId = (int) substr($data, 5);
            $this->sendPeriodoSelectionKeyboard($chatId, "prod", $sedeId, $messageId);

            return;
        }

        if (str_starts_with($data, "rep:")) {
            $parts = explode(":", $data);
            $sedeId = (int) ($parts[1] ?? 1);
            $periodo = $parts[2] ?? "hoy";

            $this->generarYEnviarReporte($chatId, $sedeId, $periodo);
        }
    }

    /**
     * Procesa la solicitud directa del comando /produccion evaluando argumentos o mostrando teclado.
     *
     * Flujo del proceso:
     * 1. Si no hay argumentos, despliega teclado interactivo de sedes para producción.
     * 2. Si hay argumentos, resuelve la sede indicada y el rango de fechas.
     * 3. Dispara la generación del resumen y del archivo Excel.
     *
     * @param  string  $chatId  Identificador del chat.
     * @param  array  $args  Argumentos provistos en el comando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function processProduccionCommand(string $chatId, array $args): void
    {
        if (empty($args)) {
            $this->sendSedeSelectionKeyboard($chatId, "prod");

            return;
        }

        $sedeQuery = strtolower($args[0]);
        $sede = $this->resolveSede($sedeQuery);

        if (! $sede) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ No se encontró ninguna sede que coincida con: <b>{$args[0]}</b>.\nUsa /sedes para ver las sedes disponibles o /menu para seleccionarla con botones."
            );

            return;
        }

        $periodo = strtolower($args[1] ?? "hoy");
        $fechaCustomFin = $args[2] ?? null;

        $this->generarYEnviarReporte($chatId, $sede->id, $periodo, $args[1] ?? null, $fechaCustomFin);
    }

    /**
     * Procesa la solicitud directa del comando /tomados o /dcm evaluando argumentos o mostrando teclado.
     *
     * Flujo del proceso:
     * 1. Si no hay argumentos, despliega teclado interactivo de sedes para DCM.
     * 2. Si hay argumentos, resuelve la sede indicada y el rango de fechas.
     * 3. Dispara la consulta al servidor PACS de la sede y el envío del reporte.
     *
     * @param  string  $chatId  Identificador del chat.
     * @param  array  $args  Argumentos provistos en el comando.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    protected function processDcmCommand(string $chatId, array $args): void
    {
        if (empty($args)) {
            $this->sendSedeSelectionKeyboard($chatId, "dcm");

            return;
        }

        $sedeQuery = strtolower($args[0]);
        $sede = $this->resolveSede($sedeQuery);

        if (! $sede) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ No se encontró ninguna sede que coincida con: <b>{$args[0]}</b>.\nUsa /sedes para ver las sedes disponibles o /tomados para seleccionarla con botones."
            );

            return;
        }

        $periodo = strtolower($args[1] ?? "hoy");
        $fechaCustomFin = $args[2] ?? null;

        $this->generarYEnviarReporteDcm($chatId, $sede->id, $periodo, $args[1] ?? null, $fechaCustomFin);
    }

    /**
     * Resuelve una instancia de ConfigSede a partir de un texto de búsqueda o ID numérico.
     *
     * Flujo del proceso:
     * 1. Si es numérico, busca por clave primaria.
     * 2. Si es texto, normaliza y busca coincidencias con LIKE en nom_sede.
     *
     * @param  string  $query  Término de búsqueda o identificador.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function resolveSede(string $query): ?ConfigSede
    {
        if (is_numeric($query)) {
            return ConfigSede::find((int) $query);
        }

        $queryClean = trim($query);

        return ConfigSede::where("nom_sede", "LIKE", "%{$queryClean}%")->first();
    }

    /**
     * Genera y envía el reporte de producción completo (Resumen Ejecutivo + Documento Excel + Botones Rápidos).
     *
     * Flujo del proceso:
     * 1. Envía señal de 'typing' a Telegram para indicar procesamiento activo.
     * 2. Resuelve fechas de inicio y fin según el período (hoy, ayer, mes, semana o custom).
     * 3. Consulta métricas consolidadas en MySQL (totales, modalidades, médicos, TAT y pendientes).
     * 4. Envía mensaje formateado en HTML con barras visuales y teclado de navegación rápida.
     * 5. Envía señal de 'upload_document' y despacha el archivo Excel detallado.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  int  $sedeId  Identificador de la sede hospitalaria.
     * @param  string  $periodo  Identificador de período ('hoy', 'ayer', 'mes', 'semana', o fecha).
     * @param  string|null  $fechaIniCustom  Fecha inicial si se pasó rango manual.
     * @param  string|null  $fechaFinCustom  Fecha final si se pasó rango manual.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    public function generarYEnviarReporte(
        string $chatId,
        int $sedeId,
        string $periodo = "hoy",
        ?string $fechaIniCustom = null,
        ?string $fechaFinCustom = null
    ): void {
        $this->telegramService->sendChatAction($chatId, "typing");

        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            $this->telegramService->sendMessage($chatId, "⚠️ Sede ID {$sedeId} no encontrada.");

            return;
        }

        // Resolución de fechas
        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIniCustom, $fechaFinCustom);

        // 1. Métricas: Total leídos
        $queryBase = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->where("estudios.sede_id", $sedeId)
            ->whereBetween("estudios_productos.fechor_lectura", [
                "{$fechaInicio} 00:00:00",
                "{$fechaFin} 23:59:59",
            ])
            ->where("estudios_productos.activo", 1);

        $totalLeidos = (clone $queryBase)->count();

        // 2. Métricas: Desglose por modalidad
        $modalidades = (clone $queryBase)
            ->selectRaw("COALESCE(NULLIF(estudios.mods_in_study, ''), 'OTRO') as mod_nombre, count(*) as cantidad")
            ->groupBy("mod_nombre")
            ->orderByDesc("cantidad")
            ->get();

        // 3. Métricas: Producción por médico radiólogo
        $medicos = (clone $queryBase)
            ->join("users as medico", "estudios.medico_id", "=", "medico.id")
            ->selectRaw("medico.name as medico_nombre, count(*) as cantidad")
            ->groupBy("medico_nombre")
            ->orderByDesc("cantidad")
            ->limit(5)
            ->get();

        // 4. Métricas: Tiempo Promedio de Respuesta (TAT en minutos)
        $avgMinutos = (clone $queryBase)
            ->whereNotNull("estudios.created_at")
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura)) as avg_min")
            ->value("avg_min");

        $tatTexto = "N/D";
        $tatIcono = "⚪";
        if ($avgMinutos !== null && $avgMinutos > 0) {
            $horas = (int) floor($avgMinutos / 60);
            $mins = (int) round($avgMinutos % 60);
            $tatTexto = $horas > 0 ? "{$horas}h {$mins}m" : "{$mins}m";
            if ($avgMinutos <= 720) {
                $tatIcono = "🟢 (Óptimo &lt;12h)";
            } elseif ($avgMinutos <= 1440) {
                $tatIcono = "🟡 (Aceptable &lt;24h)";
            } else {
                $tatIcono = "🔴 (Retrasado &gt;24h)";
            }
        }

        // 5. Métricas: Pendientes actuales por lectura en esa sede
        $pendientes = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->where("estudios.sede_id", $sedeId)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->count();

        // 6. Métricas: Pendientes represados con más de 24 horas sin informe
        $represados24h = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->where("estudios.sede_id", $sedeId)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->whereRaw("TIMESTAMPDIFF(HOUR, estudios.created_at, NOW()) > 24")
            ->count();

        // Construir mensaje resumen
        $nombreSede = $sede->nom_sede;
        $resumenTexto = $this->buildResumenMessage(
            $nombreSede,
            $etiquetaPeriodo,
            $fechaInicio,
            $fechaFin,
            $totalLeidos,
            $modalidades,
            $medicos,
            $pendientes,
            $tatTexto,
            $tatIcono,
            $represados24h
        );

        // Teclado con botones de navegación rápida post-reporte
        $navMarkup = [
            "inline_keyboard" => [
                [
                    ["text" => "📅 Hoy", "callback_data" => "wiz:gen:prod:{$sedeId}:hoy"],
                    ["text" => "⏪ Ayer", "callback_data" => "wiz:gen:prod:{$sedeId}:ayer"],
                    ["text" => "🗓️ Mes", "callback_data" => "wiz:gen:prod:{$sedeId}:mes"],
                ],
                [
                    ["text" => "🌐 Toda la Red", "callback_data" => "wiz:red:{$periodo}"],
                    ["text" => "🏥 Otra Sede", "callback_data" => "wiz:tipo:prod"],
                    ["text" => "📋 Menú", "callback_data" => "wiz:menu"],
                ],
            ],
        ];

        $this->telegramService->sendMessage($chatId, $resumenTexto, "HTML", $navMarkup);

        // Si no hay estudios leídos, evitamos generar un Excel vacío
        if ($totalLeidos === 0) {
            return;
        }

        // Generar y enviar documento Excel
        $this->telegramService->sendChatAction($chatId, "upload_document");

        try {
            $slugSede = Str::slug(explode("-", $nombreSede)[0]);
            $fileName = "produccion_{$slugSede}_{$fechaInicio}_{$fechaFin}.xlsx";
            $tempRelPath = "temp_reports/{$fileName}";

            $export = new ProduccionDetalladaExport($fechaInicio, $fechaFin, $sedeId, null, null);
            Excel::store($export, $tempRelPath, "local");

            $fullPath = Storage::disk("local")->path($tempRelPath);

            if (file_exists($fullPath)) {
                $caption = "📊 <b>Reporte de Producción Detallado</b>\n"
                    ."🏥 {$nombreSede}\n"
                    ."📅 {$fechaInicio} al {$fechaFin} ({$totalLeidos} estudios)";

                $this->telegramService->sendDocument($chatId, $fullPath, $caption);

                @unlink($fullPath);
            }
        } catch (\Throwable $e) {
            Log::error("Error generando Excel para Telegram: ".$e->getMessage(), [
                "sede_id" => $sedeId,
                "error" => $e->getMessage(),
            ]);

            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ <i>No fue posible generar el archivo Excel adjunto: {$e->getMessage()}</i>"
            );
        }
    }

    /**
     * Genera y envía el reporte consolidado de producción de toda la red hospitalaria.
     *
     * Flujo del proceso:
     * 1. Resuelve las fechas según el período (hoy, ayer, mes o rango).
     * 2. Consulta la producción agregada de todas las sedes activas en MySQL.
     * 3. Estructura el ranking de sedes con barras visuales y desglose de modalidades.
     * 4. Despacha mensaje con botones de navegación rápida.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  string  $periodo  Identificador de período.
     * @param  string|null  $fechaIniCustom  Fecha inicial si se pasó rango.
     * @param  string|null  $fechaFinCustom  Fecha final si se pasó rango.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function generarYEnviarReporteRed(
        string $chatId,
        string $periodo = "hoy",
        ?string $fechaIniCustom = null,
        ?string $fechaFinCustom = null
    ): void {
        $this->telegramService->sendChatAction($chatId, "typing");

        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIniCustom, $fechaFinCustom);

        $queryBase = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->where("estudios_productos.activo", 1)
            ->whereBetween("estudios_productos.fechor_lectura", [
                "{$fechaInicio} 00:00:00",
                "{$fechaFin} 23:59:59",
            ]);

        $totalRedLeidos = (clone $queryBase)->count();

        // Producción por sede
        $sedesData = (clone $queryBase)
            ->selectRaw("config_sedes.id as sede_id, config_sedes.nom_sede, count(*) as cantidad")
            ->groupBy("config_sedes.id", "config_sedes.nom_sede")
            ->orderByDesc("cantidad")
            ->get();

        // Desglose de modalidades en toda la red
        $modalidadesRed = (clone $queryBase)
            ->selectRaw("COALESCE(NULLIF(estudios.mods_in_study, ''), 'OTRO') as mod_nombre, count(*) as cantidad")
            ->groupBy("mod_nombre")
            ->orderByDesc("cantidad")
            ->get();

        // Estudios pendientes acumulados en la red
        $pendientesRed = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->count();

        // TAT promedio en la red
        $avgMinutos = (clone $queryBase)
            ->whereNotNull("estudios.created_at")
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, estudios.created_at, estudios_productos.fechor_lectura)) as avg_min")
            ->value("avg_min");

        $tatTexto = "N/D";
        if ($avgMinutos !== null && $avgMinutos > 0) {
            $horas = (int) floor($avgMinutos / 60);
            $mins = (int) round($avgMinutos % 60);
            $tatTexto = $horas > 0 ? "{$horas}h {$mins}m" : "{$mins}m";
        }

        // Construcción del texto
        $lineas = [
            "🌐 <b>CONSOLIDADO RED HOSPITALARIA — LIDIAG</b>",
            "📅 <b>Período:</b> {$etiquetaPeriodo}",
            "────────────────────────",
            "🏥 <b>Total Estudios Leídos (Red):</b> <b>{$totalRedLeidos}</b>",
            "⏱️ <b>Tiempo Promedio Lectura (TAT):</b> <b>{$tatTexto}</b>",
        ];

        if ($totalRedLeidos > 0) {
            $lineas[] = "\n🏆 <b>Ranking de Producción por Sede:</b>";
            foreach ($sedesData as $s) {
                $nomCorto = trim(explode("-", $s->nom_sede)[0]);
                $barra = $this->generarBarraProgreso($s->cantidad, $totalRedLeidos, 7);
                $lineas[] = "• <b>{$nomCorto}:</b>\n  <code>{$barra}</code> (<b>{$s->cantidad}</b>)";
            }

            $lineas[] = "\n🔬 <b>Modalidades en la Red:</b>";
            foreach ($modalidadesRed as $m) {
                $icono = match ($m->mod_nombre) {
                    "CR", "DX", "DR" => "🩻",
                    "CT" => "🧠",
                    "MR" => "🧲",
                    "MG" => "🎀",
                    "US" => "📡",
                    default => "🔹",
                };
                $nomMod = match ($m->mod_nombre) {
                    "CR", "DX", "DR" => "Rayos X",
                    "CT" => "TAC",
                    "MR" => "Resonancia",
                    "MG" => "Mamografía",
                    "US" => "Ecografía",
                    default => $m->mod_nombre,
                };
                $barraMod = $this->generarBarraProgreso($m->cantidad, $totalRedLeidos, 7);
                $lineas[] = "  {$icono} {$nomMod}: <code>{$barraMod}</code> (<b>{$m->cantidad}</b>)";
            }
        } else {
            $lineas[] = "\nℹ️ <i>No se registraron lecturas en la red durante este período.</i>";
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "⏳ <b>Estudios Pendientes en Toda la Red:</b> <b>{$pendientesRed}</b>";

        $navMarkup = [
            "inline_keyboard" => [
                [
                    ["text" => "📅 Hoy", "callback_data" => "wiz:red:hoy"],
                    ["text" => "⏪ Ayer", "callback_data" => "wiz:red:ayer"],
                    ["text" => "🗓️ Este Mes", "callback_data" => "wiz:red:mes"],
                ],
                [
                    ["text" => "🚨 Semáforo Represados", "callback_data" => "wiz:retrasados"],
                    ["text" => "📋 Menú Principal", "callback_data" => "wiz:menu"],
                ],
            ],
        ];

        $this->telegramService->sendMessage($chatId, implode("\n", $lineas), "HTML", $navMarkup);
    }

    /**
     * Genera y envía el semáforo asistencial de estudios retrasados (>24h) y de urgencias.
     *
     * Flujo del proceso:
     * 1. Consulta estudios pendientes con más de 24 horas transcurridas desde su creación.
     * 2. Consulta estudios prioritarios pendientes (Alta / Urgencias).
     * 3. Despacha mensaje formateado con alertas asistenciales.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function generarYEnviarReporteRetrasados(string $chatId): void
    {
        $this->telegramService->sendChatAction($chatId, "typing");

        // Represados (>24h) por sede
        $represadosPorSede = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->whereRaw("TIMESTAMPDIFF(HOUR, estudios.created_at, NOW()) > 24")
            ->selectRaw("config_sedes.id as sede_id, config_sedes.nom_sede, count(*) as cantidad")
            ->groupBy("config_sedes.id", "config_sedes.nom_sede")
            ->orderByDesc("cantidad")
            ->get();

        // Urgentes / Prioridad Alta pendientes
        $urgentesPendientes = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->join("config_sedes", "estudios.sede_id", "=", "config_sedes.id")
            ->where("config_sedes.estado", 1)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
            ->where("estudios.prioridad_id", 1)
            ->selectRaw("config_sedes.nom_sede, count(*) as cantidad")
            ->groupBy("config_sedes.nom_sede")
            ->orderByDesc("cantidad")
            ->get();

        $totalRepresados = $represadosPorSede->sum("cantidad");
        $totalUrgentes = $urgentesPendientes->sum("cantidad");

        $lineas = [
            "🚨 <b>SEMÁFORO DE ESTUDIOS REPRESADOS — LIDIAG</b>",
            "⏱️ <b>Corte en tiempo real:</b> ".Carbon::now()->format("d/m/Y H:i"),
            "────────────────────────",
            "⚠️ <b>Estudios Represados (&gt;24h sin lectura):</b> <b>{$totalRepresados}</b>",
            "🔴 <b>Estudios Prioridad Alta / Urgencias:</b> <b>{$totalUrgentes}</b>",
        ];

        if ($totalRepresados > 0) {
            $lineas[] = "\n🏥 <b>Represamiento por Sede (>24h):</b>";
            foreach ($represadosPorSede as $rep) {
                $nomCorto = trim(explode("-", $rep->nom_sede)[0]);
                $barra = $this->generarBarraProgreso($rep->cantidad, $totalRepresados, 7);
                $lineas[] = "• <b>{$nomCorto}:</b> <code>{$barra}</code> (<b>{$rep->cantidad}</b>)";
            }
        } else {
            $lineas[] = "\n✅ <i>No hay estudios con retraso mayor a 24 horas en la red.</i>";
        }

        if ($totalUrgentes > 0) {
            $lineas[] = "\n🚑 <b>Urgencias Pendientes por Sede:</b>";
            foreach ($urgentesPendientes as $urg) {
                $nomCorto = trim(explode("-", $urg->nom_sede)[0]);
                $lineas[] = "  • {$nomCorto}: <b>{$urg->cantidad}</b> estudios";
            }
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "💡 <i>Se recomienda priorizar la asignación a radiólogos de turno.</i>";

        $navMarkup = [
            "inline_keyboard" => [
                [
                    ["text" => "🔄 Actualizar Semáforo", "callback_data" => "wiz:retrasados"],
                    ["text" => "🌐 Toda la Red", "callback_data" => "wiz:red:hoy"],
                ],
                [
                    ["text" => "📋 Menú Principal", "callback_data" => "wiz:menu"],
                ],
            ],
        ];

        $this->telegramService->sendMessage($chatId, implode("\n", $lineas), "HTML", $navMarkup);
    }

    /**
     * Genera y envía el reporte de estadísticas del módulo de Citas y Agendamiento WhatsApp.
     *
     * Flujo del proceso:
     * 1. Consulta la tabla agenda_citas en el período indicado.
     * 2. Agrupa por estados de gestión (solicitada, asignada, cancelada, etc.).
     * 3. Despacha mensaje con botones de navegación rápida.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  int|null  $sedeId  Identificador opcional de sede.
     * @param  string  $periodo  Identificador de período.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function generarYEnviarReporteCitas(string $chatId, ?int $sedeId = null, string $periodo = "hoy"): void
    {
        $this->telegramService->sendChatAction($chatId, "typing");

        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo);

        $query = DB::table("agenda_citas");
        if ($sedeId) {
            $query->where("sede_id", $sedeId);
        }
        $query->whereBetween("created_at", ["{$fechaInicio} 00:00:00", "{$fechaFin} 23:59:59"]);

        $totalCitas = (clone $query)->count();
        $estados = (clone $query)
            ->selectRaw("estado, count(*) as cantidad")
            ->groupBy("estado")
            ->pluck("cantidad", "estado")
            ->toArray();

        // Solicitudes actualmente pendientes en bandeja de revisión
        $pendientesRevision = DB::table("agenda_citas")
            ->when($sedeId, fn ($q) => $q->where("sede_id", $sedeId))
            ->where("estado", "solicitada")
            ->count();

        $aprobadas = $estados["asignada"] ?? $estados["confirmada"] ?? 0;
        $canceladas = $estados["cancelada"] ?? $estados["rechazada"] ?? 0;

        $lineas = [
            "💬 <b>ESTADÍSTICAS CITAS (WHATSAPP) — LIDIAG</b>",
            "📅 <b>Período:</b> {$etiquetaPeriodo}",
            "────────────────────────",
            "📲 <b>Total Solicitudes en el Período:</b> <b>{$totalCitas}</b>",
        ];

        if ($totalCitas > 0) {
            $lineas[] = "\n📊 <b>Gestión de Citas:</b>";
            $lineas[] = "  • ✅ Asignadas / Confirmadas: <b>{$aprobadas}</b>";
            $lineas[] = "  • ❌ Canceladas / Rechazadas: <b>{$canceladas}</b>";
            foreach ($estados as $est => $cant) {
                if (! in_array($est, ["asignada", "confirmada", "cancelada", "rechazada"])) {
                    $lineas[] = "  • 🔹 ".ucfirst($est).": <b>{$cant}</b>";
                }
            }
        } else {
            $lineas[] = "\nℹ️ <i>No se registraron solicitudes de cita en el período seleccionado.</i>";
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "⏳ <b>Solicitudes Pendientes en Bandeja WhatsApp:</b> <b>{$pendientesRevision}</b>";

        $navMarkup = [
            "inline_keyboard" => [
                [
                    ["text" => "📅 Hoy", "callback_data" => "wiz:citas:hoy"],
                    ["text" => "🗓️ Este Mes", "callback_data" => "wiz:citas:mes"],
                ],
                [
                    ["text" => "📋 Menú Principal", "callback_data" => "wiz:menu"],
                ],
            ],
        ];

        $this->telegramService->sendMessage($chatId, implode("\n", $lineas), "HTML", $navMarkup);
    }

    /**
     * Genera y envía el reporte de estudios tomados directamente en la base de datos PACS/DCM de la sede.
     *
     * Flujo del proceso:
     * 1. Valida configuración de conexión remota DCM (IP, puerto y base de datos).
     * 2. Ejecuta pre-flight socket check de 2 segundos para no bloquear si la sede está offline.
     * 3. Configura la conexión dinámica mysql_sucursal y consulta estudios y modalidades.
     * 4. Envía resumen ejecutivo en HTML con barras visuales y botones rápidos.
     * 5. Genera y despacha el archivo Excel detallado usando ReportesEstudioPAC.
     *
     * @param  string  $chatId  Identificador del chat destinatario.
     * @param  int  $sedeId  Identificador de la sede hospitalaria.
     * @param  string  $periodo  Identificador de período ('hoy', 'ayer', 'mes', 'semana', o fecha).
     * @param  string|null  $fechaIniCustom  Fecha inicial si se pasó rango manual.
     * @param  string|null  $fechaFinCustom  Fecha final si se pasó rango manual.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function generarYEnviarReporteDcm(
        string $chatId,
        int $sedeId,
        string $periodo = "hoy",
        ?string $fechaIniCustom = null,
        ?string $fechaFinCustom = null
    ): void {
        $this->telegramService->sendChatAction($chatId, "typing");

        $sede = ConfigSede::find($sedeId);
        if (! $sede) {
            $this->telegramService->sendMessage($chatId, "⚠️ Sede ID {$sedeId} no encontrada.");

            return;
        }

        if (empty($sede->ip_dcm) || empty($sede->bd_dcm)) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ <b>Sede {$sede->nom_sede}</b>\nEsta sede no tiene configurada dirección IP o base de datos PACS/DCM en el catálogo de sedes."
            );

            return;
        }

        // Pre-flight check: prueba rápida de socket TCP (2 segundos)
        $host = $sede->ip_dcm;
        $port = (int) ($sede->puerto_dcm ?: 3306);
        $socket = @fsockopen($host, $port, $errno, $errstr, 2.0);

        if (! is_resource($socket)) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ <b>Conexión Fallida con PACS — {$sede->nom_sede}</b>\n\n"
                ."No fue posible conectar con el servidor MySQL DCM (<code>{$host}:{$port}</code>).\n"
                ."<i>Causas posibles: servidor apagado, red/VPN desconectada o firewall bloqueando el puerto.</i>"
            );

            return;
        }
        fclose($socket);

        // Configuración de conexión dinámica a la sucursal
        config([
            "database.connections.mysql_sucursal" => [
                "driver" => "mysql",
                "host" => env("DB_SUCURSAL_HOST", $sede->ip_dcm),
                "port" => env("DB_SUCURSAL_PUERTO", $sede->puerto_dcm),
                "database" => env("DB_SUCURSAL_DATABASE", $sede->bd_dcm),
                "username" => env("DB_SUCURSAL_USERNAME", $sede->usuario_dcm),
                "password" => env("DB_SUCURSAL_PASSWORD", $sede->password_dcm),
                "charset" => env("DB_SUCURSAL_charset", "latin1"),
                "collation" => env("DB_SUCURSAL_collation", "latin1_swedish_ci"),
            ],
        ]);
        DB::purge("mysql_sucursal");

        // Resolución de fechas
        [$fechaInicio, $fechaFin, $etiquetaPeriodo] = $this->resolverRangoFechas($periodo, $fechaIniCustom, $fechaFinCustom);

        try {
            $queryBase = DB::connection("mysql_sucursal")
                ->table("study")
                ->join("patient", "patient.pk", "=", "study.patient_fk")
                ->whereBetween("study.study_datetime", [
                    "{$fechaInicio} 00:00:00",
                    "{$fechaFin} 23:59:59",
                ]);

            $totalTomados = (clone $queryBase)->count();

            $modalidades = (clone $queryBase)
                ->selectRaw("COALESCE(NULLIF(study.mods_in_study, ''), 'OTRO') as mod_nombre, count(*) as cantidad")
                ->groupBy("mod_nombre")
                ->orderByDesc("cantidad")
                ->get();
        } catch (\Throwable $e) {
            Log::error("Error consultando estudios DCM de {$sede->nom_sede}: ".$e->getMessage(), [
                "sede_id" => $sedeId,
                "error" => $e->getMessage(),
            ]);

            $this->telegramService->sendMessage(
                $chatId,
                "❌ <b>Error al consultar base de datos PACS ({$sede->nom_sede}):</b>\n<code>{$e->getMessage()}</code>"
            );

            return;
        }

        // Construir mensaje resumen
        $nombreSede = $sede->nom_sede;
        $resumenTexto = $this->buildResumenDcmMessage(
            $nombreSede,
            $etiquetaPeriodo,
            $fechaInicio,
            $fechaFin,
            $totalTomados,
            $modalidades
        );

        // Teclado con botones de navegación rápida
        $navMarkup = [
            "inline_keyboard" => [
                [
                    ["text" => "📅 Hoy", "callback_data" => "wiz:gen:dcm:{$sedeId}:hoy"],
                    ["text" => "⏪ Ayer", "callback_data" => "wiz:gen:dcm:{$sedeId}:ayer"],
                    ["text" => "🗓️ Mes", "callback_data" => "wiz:gen:dcm:{$sedeId}:mes"],
                ],
                [
                    ["text" => "🏥 Otra Sede", "callback_data" => "wiz:tipo:dcm"],
                    ["text" => "📋 Menú Principal", "callback_data" => "wiz:menu"],
                ],
            ],
        ];

        $this->telegramService->sendMessage($chatId, $resumenTexto, "HTML", $navMarkup);

        if ($totalTomados === 0) {
            return;
        }

        // Generar y enviar documento Excel vía ReportesEstudioPAC
        $this->telegramService->sendChatAction($chatId, "upload_document");

        try {
            $slugSede = Str::slug(explode("-", $nombreSede)[0]);
            $fileName = "estudios_tomados_dcm_{$slugSede}_{$fechaInicio}_{$fechaFin}.xlsx";
            $tempRelPath = "temp_reports/{$fileName}";

            $export = new ReportesEstudioPAC($fechaInicio, $fechaFin, $sedeId, $nombreSede, config("database.default"));
            Excel::store($export, $tempRelPath, "local");

            $fullPath = Storage::disk("local")->path($tempRelPath);

            if (file_exists($fullPath)) {
                $caption = "🏥 <b>Estudios Tomados en DCM / PACS</b>\n"
                    ."🏥 {$nombreSede}\n"
                    ."📅 {$fechaInicio} al {$fechaFin} ({$totalTomados} estudios)";

                $this->telegramService->sendDocument($chatId, $fullPath, $caption);

                @unlink($fullPath);
            }
        } catch (\Throwable $e) {
            Log::error("Error generando Excel DCM para Telegram: ".$e->getMessage(), [
                "sede_id" => $sedeId,
                "error" => $e->getMessage(),
            ]);

            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ <i>No fue posible generar el archivo Excel de estudios DCM: {$e->getMessage()}</i>"
            );
        }
    }

    /**
     * Resuelve los límites de fecha y etiqueta descriptiva según la expresión de período.
     *
     * Flujo del proceso:
     * 1. Evalúa si el período corresponde a 'hoy', 'ayer', 'mes', 'semana' o fecha ISO.
     * 2. Retorna array con [fechaInicio, fechaFin, etiquetaPeriodo].
     *
     * @param  string  $periodo  Expresión de período.
     * @param  string|null  $fechaIniCustom  Fecha inicial provista manualmente.
     * @param  string|null  $fechaFinCustom  Fecha final provista manualmente.
     * @return array{0: string, 1: string, 2: string}
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function resolverRangoFechas(string $periodo, ?string $fechaIniCustom = null, ?string $fechaFinCustom = null): array
    {
        $hoy = Carbon::today()->format("Y-m-d");

        if ($periodo === "ayer") {
            $ayer = Carbon::yesterday()->format("Y-m-d");

            return [$ayer, $ayer, "Ayer (".Carbon::yesterday()->format("d/m/Y").")"];
        }

        if ($periodo === "mes") {
            $inicioMes = Carbon::now()->startOfMonth()->format("Y-m-d");

            return [$inicioMes, $hoy, "Mes actual (".Carbon::now()->translatedFormat("F Y").")"];
        }

        if ($periodo === "semana" || $periodo === "7dias") {
            $inicioSemana = Carbon::now()->subDays(6)->format("Y-m-d");

            return [$inicioSemana, $hoy, "Últimos 7 días"];
        }

        // Si se especificó una fecha en formato YYYY-MM-DD
        if (preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaIniCustom ?? $periodo)) {
            $ini = $fechaIniCustom ?? $periodo;
            $fin = (isset($fechaFinCustom) && preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaFinCustom)) ? $fechaFinCustom : $ini;

            return [$ini, $fin, "Del {$ini} al {$fin}"];
        }

        return [$hoy, $hoy, "Hoy (".Carbon::today()->format("d/m/Y").")"];
    }

    /**
     * Genera una representación gráfica de barra de porcentaje utilizando caracteres Unicode.
     *
     * Flujo del proceso:
     * 1. Calcula el porcentaje relativo entre actual y total.
     * 2. Determina la cantidad proporcional de bloques llenos y vacíos.
     * 3. Formatea la cadena compacta alineada.
     *
     * @param  int  $actual  Cantidad actual.
     * @param  int  $total  Cantidad total de referencia.
     * @param  int  $longitud  Cantidad de caracteres de la barra.
     * @return string Ejemplo: "██████░░ 75%"
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    public function generarBarraProgreso(int $actual, int $total, int $longitud = 8): string
    {
        if ($total <= 0 || $actual <= 0) {
            return str_repeat("░", $longitud)."  0%";
        }

        $porcentaje = min(100, (int) round(($actual / $total) * 100));
        $llenos = min($longitud, (int) round(($porcentaje / 100) * $longitud));
        $vacios = max(0, $longitud - $llenos);
        $pctStr = str_pad((string) $porcentaje, 3, " ", STR_PAD_LEFT);

        return str_repeat("█", $llenos).str_repeat("░", $vacios)." {$pctStr}%";
    }

    /**
     * Construye el texto en formato HTML del resumen ejecutivo de producción para Telegram.
     *
     * Flujo del proceso:
     * 1. Formatea encabezados de sede y período.
     * 2. Agrupa modalidades y doctores con barras visuales de porcentaje.
     * 3. Agrega tiempo de respuesta TAT y semáforo asistencial de represamiento.
     *
     * @param  string  $sedeNombre  Nombre completo de la sede.
     * @param  string  $etiquetaPeriodo  Etiqueta amigable del período.
     * @param  string  $fechaInicio  Fecha inicial YYYY-MM-DD.
     * @param  string  $fechaFin  Fecha final YYYY-MM-DD.
     * @param  int  $totalLeidos  Cantidad total de estudios leídos.
     * @param  mixed  $modalidades  Colección de modalidades y cantidades.
     * @param  mixed  $medicos  Colección de médicos lectores y cantidades.
     * @param  int  $pendientes  Cantidad de estudios pendientes por lectura.
     * @param  string  $tatTexto  Tiempo promedio en formato amigable (ej: 2h 15m).
     * @param  string  $tatIcono  Semáforo textual.
     * @param  int  $represados24h  Cantidad de estudios con >24h sin lectura.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function buildResumenMessage(
        string $sedeNombre,
        string $etiquetaPeriodo,
        string $fechaInicio,
        string $fechaFin,
        int $totalLeidos,
        $modalidades,
        $medicos,
        int $pendientes,
        string $tatTexto = "N/D",
        string $tatIcono = "⚪",
        int $represados24h = 0
    ): string {
        $lineas = [
            "📊 <b>REPORTE DE PRODUCCIÓN — LIDIAG</b>",
            "🏥 <b>Sede:</b> {$sedeNombre}",
            "📅 <b>Período:</b> {$etiquetaPeriodo}",
            "────────────────────────",
            "✅ <b>Total Estudios Leídos:</b> <b>{$totalLeidos}</b>",
            "⏱️ <b>Tiempo Promedio Lectura (TAT):</b> {$tatTexto} {$tatIcono}",
        ];

        if ($totalLeidos > 0) {
            $lineas[] = "\n🔬 <b>Desglose por Modalidad:</b>";
            foreach ($modalidades as $m) {
                $icono = match ($m->mod_nombre) {
                    "CR", "DX", "DR" => "🩻",
                    "CT" => "🧠",
                    "MR" => "🧲",
                    "MG" => "🎀",
                    "US" => "📡",
                    default => "🔹",
                };

                $nombreMod = match ($m->mod_nombre) {
                    "CR", "DX", "DR" => "Rayos X ({$m->mod_nombre})",
                    "CT" => "Tomografía (TAC)",
                    "MR" => "Resonancia (RM)",
                    "MG" => "Mamografía (MG)",
                    "US" => "Ecografía (US)",
                    default => $m->mod_nombre,
                };

                $barra = $this->generarBarraProgreso($m->cantidad, $totalLeidos, 7);
                $lineas[] = "  {$icono} {$nombreMod}:\n  <code>{$barra}</code> (<b>{$m->cantidad}</b>)";
            }

            if ($medicos->isNotEmpty()) {
                $lineas[] = "\n👨‍⚕️ <b>Médicos con Mayor Volumen:</b>";
                foreach ($medicos as $med) {
                    $barraMed = $this->generarBarraProgreso($med->cantidad, $totalLeidos, 7);
                    $lineas[] = "  • {$med->medico_nombre}:\n    <code>{$barraMed}</code> (<b>{$med->cantidad}</b>)";
                }
            }
        } else {
            $lineas[] = "\nℹ️ <i>No se registraron lecturas en el período seleccionado.</i>";
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "⏳ <b>Estudios Pendientes en Sede:</b> <b>{$pendientes}</b>";
        if ($represados24h > 0) {
            $lineas[] = "⚠️ <b>Estudios con >24h sin lectura:</b> <b>{$represados24h}</b>";
        }

        return implode("\n", $lineas);
    }

    /**
     * Construye el texto en formato HTML del resumen ejecutivo de estudios tomados en DCM para Telegram.
     *
     * Flujo del proceso:
     * 1. Formatea encabezados de sede y período.
     * 2. Agrupa modalidades con barras visuales de porcentaje.
     * 3. Concatena notas explicativas de consulta directa PACS.
     *
     * @param  string  $sedeNombre  Nombre completo de la sede.
     * @param  string  $etiquetaPeriodo  Etiqueta amigable del período.
     * @param  string  $fechaInicio  Fecha inicial YYYY-MM-DD.
     * @param  string  $fechaFin  Fecha final YYYY-MM-DD.
     * @param  int  $totalTomados  Cantidad total de estudios tomados en PACS.
     * @param  mixed  $modalidades  Colección de modalidades y cantidades.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-20
     */
    protected function buildResumenDcmMessage(
        string $sedeNombre,
        string $etiquetaPeriodo,
        string $fechaInicio,
        string $fechaFin,
        int $totalTomados,
        $modalidades
    ): string {
        $lineas = [
            "🏥 <b>ESTUDIOS TOMADOS EN DCM / PACS</b>",
            "🏥 <b>Sede:</b> {$sedeNombre}",
            "📅 <b>Período:</b> {$etiquetaPeriodo}",
            "────────────────────────",
            "📸 <b>Total Estudios Tomados:</b> <b>{$totalTomados}</b>",
        ];

        if ($totalTomados > 0) {
            $lineas[] = "\n🔬 <b>Desglose por Modalidad:</b>";
            foreach ($modalidades as $m) {
                $icono = match ($m->mod_nombre) {
                    "CR", "DX", "DR" => "🩻",
                    "CT" => "🧠",
                    "MR" => "🧲",
                    "MG" => "🎀",
                    "US" => "📡",
                    default => "🔹",
                };

                $nombreMod = match ($m->mod_nombre) {
                    "CR", "DX", "DR" => "Rayos X ({$m->mod_nombre})",
                    "CT" => "Tomografía Computarizada (TAC)",
                    "MR" => "Resonancia Magnética (RM)",
                    "MG" => "Mamografía (MG)",
                    "US" => "Ecografía (US)",
                    default => $m->mod_nombre,
                };

                $barra = $this->generarBarraProgreso($m->cantidad, $totalTomados, 7);
                $lineas[] = "  {$icono} {$nombreMod}:\n  <code>{$barra}</code> (<b>{$m->cantidad}</b>)";
            }
        } else {
            $lineas[] = "\nℹ️ <i>No se registraron estudios tomados en el PACS durante este período.</i>";
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "💡 <i>Consultado directamente en base de datos local PACS (MySQL).</i>";

        return implode("\n", $lineas);
    }

    /**
     * Envía lista de usuarios de Telegram autorizados en el sistema.
     *
     * Flujo del proceso:
     * 1. Consulta la lista de chats estáticos y persistidos.
     * 2. Da formato legible con fecha de alta y nombre de usuario.
     * 3. Despacha el mensaje al solicitante.
     *
     * @param  string  $chatId  Identificador del chat solicitante.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-29
     */
    protected function sendUsuariosAutorizadosMessage(string $chatId): void
    {
        $static = config("telegram.allowed_chat_ids", []);
        $persisted = $this->getPersistedAuthorizedChats();

        $text = "👥 <b>USUARIOS AUTORIZADOS EN TELEGRAM</b>\n\n";

        if (! empty($static)) {
            $text .= "<b>📌 Lista Blanca Estática (.env):</b>\n";
            foreach ($static as $id) {
                $text .= "• <code>{$id}</code> (Administrador)\n";
            }
            $text .= "\n";
        }

        $text .= "<b>🔑 Auto-autorizados mediante PIN:</b>\n";
        if (empty($persisted)) {
            $text .= "<i>(Ningún usuario registrado aún por PIN)</i>\n";
        } else {
            foreach ($persisted as $id => $data) {
                $name = $data["first_name"] ?? "Usuario";
                $user = isset($data["username"]) && $data["username"] ? " (@{$data["username"]})" : "";
                $date = $data["authorized_at"] ?? "";
                $text .= "• <code>{$id}</code> — <b>{$name}</b>{$user} [{$date}]\n";
            }
        }

        $text .= "\n💡 <i>Para revocar a alguien: <code>/revocar &lt;chat_id&gt;</code></i>";

        $this->telegramService->sendMessage($chatId, $text);
    }
}
