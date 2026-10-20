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
     * 1. Define la lista de comandos disponibles con descripciones concisas.
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
            ["command" => "produccion", "description" => "📊 Reporte de producción (leídos/firmados)"],
            ["command" => "tomados", "description" => "🏥 Reporte de estudios tomados en DCM/PACS"],
            ["command" => "sedes", "description" => "🏥 Lista de sedes e identificadores"],
            ["command" => "dd", "description" => "💾 Monitor de discos duros PACS"],
            ["command" => "ayuda", "description" => "❓ Guía de uso y comandos disponibles"],
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
        $chatId = (string) ($chat["id"] ?? "");
        $text = trim($message["text"] ?? "");

        if (! $chatId || $text === "") {
            return;
        }

        // Verificación de lista blanca
        if (! $this->isChatAuthorized($chatId)) {
            $this->sendUnauthorizedMessage($chatId);

            return;
        }

        $this->handleCommand($chatId, $text);
    }

    /**
     * Valida si un Chat ID se encuentra autorizado para interactuar con el bot.
     *
     * Flujo del proceso:
     * 1. Obtiene la lista permitida desde config('telegram.allowed_chat_ids').
     * 2. Si la lista está vacía, permite el acceso (o deniega según política).
     * 3. Compara de forma estricta como string la existencia del Chat ID.
     *
     * @param  string  $chatId  Identificador del chat a verificar.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function isChatAuthorized(string $chatId): bool
    {
        $allowed = config("telegram.allowed_chat_ids", []);

        if (empty($allowed)) {
            return true;
        }

        return in_array($chatId, array_map("strval", $allowed), true);
    }

    /**
     * Envía un mensaje informativo de acceso denegado indicando el Chat ID del usuario.
     *
     * Flujo del proceso:
     * 1. Construye mensaje explicativo con el identificador del usuario.
     * 2. Envía el mensaje mediante TelegramService.
     *
     * @param  string  $chatId  Identificador del remitente no autorizado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function sendUnauthorizedMessage(string $chatId): void
    {
        $text = "⛔ <b>Acceso Restringido — LIDiag</b>\n\n"
            ."Tu identificador de Telegram es: <code>{$chatId}</code>\n"
            ."Actualmente no te encuentras registrado en la lista autorizada para consultar reportes asistenciales o administrativos de LIDiag.\n\n"
            ."<i>Por favor solicita al administrador del sistema que habilite tu Chat ID.</i>";

        $this->telegramService->sendMessage($chatId, $text);
    }

    /**
     * Despacha el comando de texto recibido a la acción correspondiente.
     *
     * Flujo del proceso:
     * 1. Normaliza la instrucción quitando arrobas y barras iniciales.
     * 2. Reconoce comandos /start, /ayuda, /help, /menu, /sedes, /produccion, /tomados o de discos.
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
                    ."Puedes usar el menú táctil con botones interactivos a continuación o escribir /ayuda para ver todos los comandos."
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

            case "sedes":
                $this->sendSedesMessage($chatId);
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
                    "❓ Comando no reconocido: <code>/{$cmd}</code>\nUsa /menu para el menú interactivo o /ayuda para ver todos los comandos."
                );
                break;
        }
    }

    /**
     * Envía el mensaje de bienvenida y catálogo de comandos disponibles.
     *
     * Flujo del proceso:
     * 1. Estructura el mensaje con los comandos asistenciales, de producción y de DCM.
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
            ."🔹 <code>/menu</code> o <code>/reporte</code> — Asistente paso a paso con botones.\n\n"
            ."📊 <b>1. PRODUCCIÓN (ESTUDIOS LEÍDOS Y FIRMADOS):</b>\n"
            ."🔹 <code>/produccion [sede] [periodo]</code> — Resumen y Excel detallado.\n"
            ."   • <code>/produccion 1 mes</code> — Producción mensual por ID (1 = Espinal).\n"
            ."   • <code>/produccion 3 ayer</code> — Producción de ayer por ID (3 = Chaparral).\n"
            ."   • <code>/produccion 6 hoy</code> — Producción de hoy por ID (6 = Honda).\n"
            ."   • <code>/produccion espinal mes</code> — Búsqueda por nombre de sede.\n"
            ."   • <code>/produccion 1 2026-10-01 2026-10-15</code> — Rango de fechas personalizado.\n\n"
            ."🏥 <b>2. ESTUDIOS TOMADOS EN DCM / PACS:</b>\n"
            ."🔹 <code>/tomados [sede] [periodo]</code> o <code>/dcm [sede] [periodo]</code>\n"
            ."   • <code>/tomados 1 hoy</code> — Estudios tomados hoy en PACS Espinal.\n"
            ."   • <code>/tomados 3 mes</code> — Estudios tomados este mes en Chaparral.\n"
            ."   • <code>/tomados 6 semana</code> — Últimos 7 días en Honda.\n\n"
            ."🏥 <b>INFORMACIÓN DE SEDES:</b>\n"
            ."🔹 <code>/sedes</code> — Lista todas las sedes y sus números de ID.\n\n"
            ."💾 <b>MONITOR DE DISCOS DUROS (PACS):</b>\n"
            ."🔹 <code>/dd</code> o <code>/disco</code> — Estado de repositorios DICOM.\n"
            ."🔹 <code>/dd_alertas</code> — Umbrales de advertencia y crítico configurados.\n"
            ."🔹 <code>/dd_info</code> — Información técnica del servidor PACS.\n\n"
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
        $lineas[] = "📋 <b>Menú táctil:</b> <code>/menu</code> o <code>/reporte</code>";

        $this->telegramService->sendMessage($chatId, implode("\n", $lineas));
    }

    /**
     * Paso 1 del Asistente: Despliega botones para elegir el tipo de reporte.
     *
     * Flujo del proceso:
     * 1. Construye el teclado inline con las dos categorías de reportes (Producción vs Tomados DCM).
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
            ],
        ];

        $text = "📋 <b>CENTRO DE REPORTES — LIDIAG</b>\n\n"
            ."Seleccione el tipo de reporte que desea consultar:";

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
            ["text" => "🔙 Volver al Tipo de Reporte", "callback_data" => "wiz:menu"],
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
     * 1. Extrae los datos del callback y responde inmediatamente para descartar el loading.
     * 2. Enruta según la acción (wiz:menu, wiz:tipo, wiz:sede, wiz:gen o retrocompatibilidad).
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
        $chatId = (string) ($chat["id"] ?? "");
        $messageId = isset($message["message_id"]) ? (int) $message["message_id"] : null;
        $data = $callbackQuery["data"] ?? "";

        if ($callbackQueryId !== "") {
            $this->telegramService->answerCallbackQuery($callbackQueryId);
        }

        if (! $chatId || ! $this->isChatAuthorized($chatId)) {
            return;
        }

        // Navegación: Retorno al Paso 1 (Tipo de reporte)
        if ($data === "wiz:menu") {
            $this->sendTipoReporteSelectionKeyboard($chatId, $messageId);

            return;
        }

        // Navegación: Paso 2 (Listado de sedes según tipo)
        if (str_starts_with($data, "wiz:tipo:")) {
            $tipo = substr($data, 9);
            $this->sendSedeSelectionKeyboard($chatId, $tipo, $messageId);

            return;
        }

        // Navegación: Paso 3 (Listado de períodos según sede y tipo)
        if (str_starts_with($data, "wiz:sede:")) {
            $parts = explode(":", $data);
            $tipo = $parts[2] ?? "prod";
            $sedeId = (int) ($parts[3] ?? 1);
            $this->sendPeriodoSelectionKeyboard($chatId, $tipo, $sedeId, $messageId);

            return;
        }

        // Ejecución de reporte desde el asistente
        if (str_starts_with($data, "wiz:gen:")) {
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
     * Genera y envía el reporte de producción completo (Resumen Ejecutivo + Documento Excel).
     *
     * Flujo del proceso:
     * 1. Envía señal de 'typing' a Telegram para indicar procesamiento activo.
     * 2. Resuelve fechas de inicio y fin según el período (hoy, ayer, mes, semana o custom).
     * 3. Consulta métricas consolidadas en MySQL (totales, modalidades, médicos y pendientes).
     * 4. Envía mensaje formateado en HTML con el resumen ejecutivo.
     * 5. Envía señal de 'upload_document'.
     * 6. Genera archivo Excel temporal utilizando ProduccionDetalladaExport.
     * 7. Envía el archivo adjunto a Telegram y lo elimina del almacenamiento temporal.
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

        // 4. Métricas: Pendientes actuales por lectura en esa sede
        $pendientes = DB::table("estudios_productos")
            ->join("estudios", "estudios_productos.estudio_id", "=", "estudios.id")
            ->where("estudios.sede_id", $sedeId)
            ->whereNull("estudios_productos.fechor_lectura")
            ->where("estudios_productos.activo", 1)
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
            $pendientes
        );

        $this->telegramService->sendMessage($chatId, $resumenTexto);

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
     * Genera y envía el reporte de estudios tomados directamente en la base de datos PACS/DCM de la sede.
     *
     * Flujo del proceso:
     * 1. Valida configuración de conexión remota DCM (IP, puerto y base de datos).
     * 2. Ejecuta pre-flight socket check de 2 segundos para no bloquear si la sede está offline.
     * 3. Configura la conexión dinámica mysql_sucursal y consulta estudios y modalidades.
     * 4. Envía resumen ejecutivo en HTML.
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

        $this->telegramService->sendMessage($chatId, $resumenTexto);

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
     * Construye el texto en formato HTML del resumen ejecutivo de producción para Telegram.
     *
     * Flujo del proceso:
     * 1. Formatea encabezados de sede y período.
     * 2. Agrupa modalidades y asigna nombres legibles con iconos clínicos.
     * 3. Enumera los médicos lectores con mayores lecturas registradas.
     * 4. Concatena el número de estudios pendientes por lectura.
     *
     * @param  string  $sedeNombre  Nombre completo de la sede.
     * @param  string  $etiquetaPeriodo  Etiqueta amigable del período.
     * @param  string  $fechaInicio  Fecha inicial YYYY-MM-DD.
     * @param  string  $fechaFin  Fecha final YYYY-MM-DD.
     * @param  int  $totalLeidos  Cantidad total de estudios leídos.
     * @param  mixed  $modalidades  Colección de modalidades y cantidades.
     * @param  mixed  $medicos  Colección de médicos lectores y cantidades.
     * @param  int  $pendientes  Cantidad de estudios pendientes por lectura.
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
        int $pendientes
    ): string {
        $lineas = [
            "📊 <b>REPORTE DE PRODUCCIÓN — LIDIAG</b>",
            "🏥 <b>Sede:</b> {$sedeNombre}",
            "📅 <b>Período:</b> {$etiquetaPeriodo}",
            "────────────────────────",
            "✅ <b>Total Estudios Leídos:</b> <b>{$totalLeidos}</b>",
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
                    "CT" => "Tomografía Computarizada (TAC)",
                    "MR" => "Resonancia Magnética (RM)",
                    "MG" => "Mamografía (MG)",
                    "US" => "Ecografía (US)",
                    default => $m->mod_nombre,
                };

                $lineas[] = "  {$icono} {$nombreMod}: <b>{$m->cantidad}</b>";
            }

            if ($medicos->isNotEmpty()) {
                $lineas[] = "\n👨‍⚕️ <b>Médicos Lectores:</b>";
                foreach ($medicos as $med) {
                    $lineas[] = "  • {$med->medico_nombre}: <b>{$med->cantidad}</b>";
                }
            }
        } else {
            $lineas[] = "\nℹ️ <i>No se registraron lecturas en el período seleccionado.</i>";
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "⏳ <b>Estudios Pendientes por Lectura en Sede:</b> <b>{$pendientes}</b>";

        return implode("\n", $lineas);
    }

    /**
     * Construye el texto en formato HTML del resumen ejecutivo de estudios tomados en DCM para Telegram.
     *
     * Flujo del proceso:
     * 1. Formatea encabezados de sede y período.
     * 2. Agrupa modalidades y asigna nombres legibles con iconos clínicos.
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

                $lineas[] = "  {$icono} {$nombreMod}: <b>{$m->cantidad}</b>";
            }
        } else {
            $lineas[] = "\nℹ️ <i>No se registraron estudios tomados en el PACS durante este período.</i>";
        }

        $lineas[] = "────────────────────────";
        $lineas[] = "💡 <i>Consultado directamente en base de datos local PACS (MySQL).</i>";

        return implode("\n", $lineas);
    }
}
