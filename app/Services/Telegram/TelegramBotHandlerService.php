<?php

namespace App\Services\Telegram;

use App\Exports\ProduccionDetalladaExport;
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
     * 2. Reconoce comandos /start, /ayuda, /help, /sedes, /produccion o /reporte.
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
            case "help":
            case "ayuda":
                $this->sendHelpMessage($chatId);
                break;

            case "sedes":
                $this->sendSedesMessage($chatId);
                break;

            case "produccion":
            case "reporte":
            case "prod":
                $this->processProduccionCommand($chatId, $args);
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
                    "❓ Comando no reconocido: <code>/{$cmd}</code>\nUsa /ayuda para ver los comandos disponibles."
                );
                break;
        }
    }

    /**
     * Envía el mensaje de bienvenida y catálogo de comandos disponibles.
     *
     * Flujo del proceso:
     * 1. Estructura el mensaje con los comandos asistenciales y de producción.
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
            ."📊 <b>MÓDULO DE PRODUCCIÓN Y ESTADÍSTICAS:</b>\n"
            ."🔹 <code>/produccion [sede] [periodo]</code> — Resumen y Excel detallado.\n"
            ."   • <code>/produccion 1 mes</code> — Producción mensual por ID (1 = Espinal).\n"
            ."   • <code>/produccion 3 ayer</code> — Producción de ayer por ID (3 = Chaparral).\n"
            ."   • <code>/produccion 6 mes</code> — Producción mensual de Honda (ID 6).\n"
            ."   • <code>/produccion espinal mes</code> — Producción por nombre de sede.\n"
            ."   • <code>/produccion 1 2026-10-01 2026-10-15</code> — Rango personalizado.\n"
            ."   • <code>/produccion</code> — Selector interactivo con botones táctiles.\n"
            ."🔹 <code>/sedes</code> — Lista todas las sedes activas y sus números de ID.\n\n"
            ."💾 <b>MÓDULO DE DISCOS DUROS (PACS):</b>\n"
            ."🔹 <code>/dd</code> o <code>/disco</code> — Estado de repositorios DICOM.\n"
            ."🔹 <code>/dd_alertas</code> — Umbrales de advertencia y crítico configurados.\n"
            ."🔹 <code>/dd_info</code> — Información técnica del servidor PACS.\n\n"
            ."💡 <i>Tip: Puedes usar /sedes para conocer los códigos de cada hospital antes de consultar.</i>";

        $this->telegramService->sendMessage($chatId, $text);
    }

    /**
     * Envía el listado de sedes activas en el sistema con sus identificadores.
     *
     * Flujo del proceso:
     * 1. Consulta las sedes activas en config_sedes.
     * 2. Construye un mensaje estructurado con código y nombre.
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
        $sedes = ConfigSede::where("estado", true)->orderBy("id")->get(["id", "nom_sede"]);

        $lineas = ["🏥 <b>SEDES ACTIVAS EN LIDIAG:</b>\n"];
        foreach ($sedes as $s) {
            $lineas[] = "• <b>ID {$s->id}:</b> {$s->nom_sede}";
        }

        $lineas[] = "\n💡 <i>Puedes pedir reportes con el ID o nombre:</i>\n<code>/produccion 1 mes</code> o <code>/produccion chaparral ayer</code>";

        $this->telegramService->sendMessage($chatId, implode("\n", $lineas));
    }

    /**
     * Procesa la solicitud del comando /produccion evaluando argumentos o mostrando teclado.
     *
     * Flujo del proceso:
     * 1. Si no hay argumentos, despliega teclado interactivo con las sedes disponibles.
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
            $this->sendSedeSelectionKeyboard($chatId);

            return;
        }

        $sedeQuery = strtolower($args[0]);
        $sede = $this->resolveSede($sedeQuery);

        if (! $sede) {
            $this->telegramService->sendMessage(
                $chatId,
                "⚠️ No se encontró ninguna sede que coincida con: <b>{$args[0]}</b>.\nUsa /sedes para ver las sedes disponibles o /produccion para seleccionarla con botones."
            );

            return;
        }

        $periodo = strtolower($args[1] ?? "hoy");
        $fechaCustomFin = $args[2] ?? null;

        $this->generarYEnviarReporte($chatId, $sede->id, $periodo, $args[1] ?? null, $fechaCustomFin);
    }

    /**
     * Despliega un teclado interactivo en Telegram para seleccionar la sede a consultar.
     *
     * Flujo del proceso:
     * 1. Consulta sedes activas en config_sedes.
     * 2. Genera botones inline agrupados de a 2 por fila.
     * 3. Envía el mensaje con reply_markup.
     *
     * @param  string  $chatId  Identificador del chat.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function sendSedeSelectionKeyboard(string $chatId): void
    {
        $sedes = ConfigSede::where("estado", true)->orderBy("id")->get(["id", "nom_sede"]);

        $keyboard = [];
        $currentRow = [];

        foreach ($sedes as $s) {
            // Nombre corto legible
            $nombreCorto = explode("-", $s->nom_sede)[0];
            $nombreCorto = trim($nombreCorto);

            $currentRow[] = [
                "text" => "🏥 ".$nombreCorto,
                "callback_data" => "sede:{$s->id}",
            ];

            if (count($currentRow) === 2) {
                $keyboard[] = $currentRow;
                $currentRow = [];
            }
        }

        if (! empty($currentRow)) {
            $keyboard[] = $currentRow;
        }

        $markup = ["inline_keyboard" => $keyboard];

        $this->telegramService->sendMessage(
            $chatId,
            "📊 <b>Seleccione la sede para el reporte de producción:</b>",
            "HTML",
            $markup
        );
    }

    /**
     * Procesa las interacciones con botones inline (callback_query).
     *
     * Flujo del proceso:
     * 1. Extrae los datos del callback (sede seleccionada o período).
     * 2. Si es selección de sede, presenta botones de período (Hoy, Ayer, Mes).
     * 3. Si es selección de período, ejecuta la generación del reporte.
     *
     * @param  array  $callbackQuery  Datos del evento callback_query de Telegram.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-10-19
     */
    protected function handleCallbackQuery(array $callbackQuery): void
    {
        $chat = $callbackQuery["message"]["chat"] ?? [];
        $chatId = (string) ($chat["id"] ?? "");
        $data = $callbackQuery["data"] ?? "";

        if (! $chatId || ! $this->isChatAuthorized($chatId)) {
            return;
        }

        if (str_starts_with($data, "sede:")) {
            $sedeId = (int) substr($data, 5);
            $sede = ConfigSede::find($sedeId);

            if (! $sede) {
                $this->telegramService->sendMessage($chatId, "⚠️ Sede no encontrada.");

                return;
            }

            $markup = [
                "inline_keyboard" => [
                    [
                        ["text" => "📅 Hoy", "callback_data" => "rep:{$sedeId}:hoy"],
                        ["text" => "⏪ Ayer", "callback_data" => "rep:{$sedeId}:ayer"],
                    ],
                    [
                        ["text" => "🗓️ Este Mes", "callback_data" => "rep:{$sedeId}:mes"],
                        ["text" => "📆 Últimos 7 Días", "callback_data" => "rep:{$sedeId}:semana"],
                    ],
                ],
            ];

            $this->telegramService->sendMessage(
                $chatId,
                "🏥 Sede seleccionada: <b>{$sede->nom_sede}</b>\nSeleccione el período a consultar:",
                "HTML",
                $markup
            );

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
     * Construye el texto en formato HTML del resumen ejecutivo para Telegram.
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
}
