<?php

namespace App\Http\Controllers\HelpDesk;

use App\Http\Controllers\Controller;
use App\Models\Configuracion\ConfigSede;
use App\Models\HelpDesk\HelpDeskEstado;
use App\Models\HelpDesk\HelpDeskFolioCounter;
use App\Models\HelpDesk\HelpDeskTicketHistorial;
use App\Models\HelpDesk\HelpDeskTickets;
use App\Models\User;
use App\Notifications\HelpDesk\HelpDeskTicketNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HelpDeskTicketsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $scope = (string) $request->query('scope', 'mine'); // mine | assigned | all

        if ($scope === 'all' && ! $user->hasRole(['Administrador', 'Soporte Manager'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $q = HelpDeskTickets::query()
            ->with([
                'categoria:id,nombre',
                'prioridad:id,nombre,orden',
                'estado:id,nombre,orden',
                'solicitante:id,name,email',
                'asignado:id,name,email',
                'sede:id,nom_sede,cod_sede',
            ]);

        if ($scope === 'mine') {
            $q->where('quien_registra_id', $user->id);
        } elseif ($scope === 'assigned') {
            $q->where('quien_atiende_id', $user->id);
        }

        // Filtros
        if ($request->filled('estado_id')) {
            $q->where('estado_id', (int) $request->estado_id);
        }
        if ($request->filled('categoria_id')) {
            $q->where('categoria_id', (int) $request->categoria_id);
        }
        if ($request->filled('prioridad_id')) {
            $q->where('prioridad_id', (int) $request->prioridad_id);
        }
        if ($request->filled('sede_id')) {
            $q->where('sedes_id', (int) $request->sede_id);
        }
        if ($request->filled('desde')) {
            $q->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $q->whereDate('created_at', '<=', $request->hasta);
        }
        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($qq) use ($term) {
                $qq->where('folio', 'like', "%{$term}%")
                    ->orWhere('titulo', 'like', "%{$term}%")
                    ->orWhere('descripcion', 'like', "%{$term}%");
            });
        }

        // Orden por defecto: prioridad (orden ASC) y luego más recientes
        $q->leftJoin('help_desk_prioridades as p', 'help_desk_tickets.prioridad_id', '=', 'p.id')
            ->orderByRaw('COALESCE(p.orden, 999999) asc')
            ->orderByDesc('help_desk_tickets.created_at')
            ->select('help_desk_tickets.*');

        return response()->json($q->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'categoria_id' => 'required|integer|exists:help_desk_categorias,id',
            'prioridad_id' => 'required|integer|exists:help_desk_prioridades,id',
            'sede_id' => 'required|integer|exists:config_sedes,id',
            'asignado_id' => 'required|integer|exists:users,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $asignado = User::query()->where('id', (int) $request->asignado_id)->where('estado', 1)->first();
        if (! $asignado || ! $asignado->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return response()->json(['message' => 'Asignado inválido'], 422);
        }

        $estadoAbierto = HelpDeskEstado::query()->where('nombre', 'Abierto')->first();
        if (! $estadoAbierto) {
            return response()->json(['message' => 'Catálogo de estados no inicializado'], 500);
        }

        $sede = ConfigSede::query()->select(['id', 'cod_sede'])->findOrFail((int) $request->sede_id);
        if (! $sede->cod_sede) {
            return response()->json(['message' => 'La sede no tiene cod_sede configurado'], 422);
        }

        $ticket = DB::transaction(function () use ($request, $estadoAbierto, $sede, $asignado) {
            $user = $request->user();
            $now = now();
            $anio = (int) $now->format('Y');

            /** @var HelpDeskFolioCounter $counter */
            $counter = HelpDeskFolioCounter::query()->where('anio', $anio)->lockForUpdate()->first();
            if (! $counter) {
                $counter = HelpDeskFolioCounter::create(['anio' => $anio, 'last_number' => 0]);
                $counter->refresh();
            }

            $counter->last_number = (int) $counter->last_number + 1;
            $counter->save();

            $seq = str_pad((string) $counter->last_number, 5, '0', STR_PAD_LEFT);
            $fecha = $now->format('Ymd'); // YYYYMMDD
            $folio = "{$sede->cod_sede}-{$fecha}-{$seq}";

            $ticket = HelpDeskTickets::create([
                'folio' => $folio,
                'titulo' => $request->titulo,
                'descripcion' => $this->sanitizeHtml($request->descripcion),
                'categoria_id' => (int) $request->categoria_id,
                'prioridad_id' => (int) $request->prioridad_id,
                'estado_id' => (int) $estadoAbierto->id,
                'quien_registra_id' => (int) $user->id,
                'sedes_id' => (int) $sede->id,
                'quien_atiende_id' => (int) $asignado->id,
                'pendiente_de' => 'SOPORTE',
            ]);

            HelpDeskTicketHistorial::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'tipo' => 'CREADO',
                'comentario' => null,
            ]);

            // Notificación interna para solicitante y asignado
            $url = null; // se completará en frontend con ruta web
            $msg = "Ticket creado ({$ticket->folio})";
            $notif = new HelpDeskTicketNotification($ticket->id, $ticket->folio, $ticket->titulo, 'CREADO', $msg, $url);
            $user->notify($notif);
            if ((int) $asignado->id !== (int) $user->id) {
                $asignado->notify($notif);
            }

            return $ticket;
        });

        return response()->json($ticket->load(['categoria', 'prioridad', 'estado']), 201);
    }

    public function show(HelpDeskTickets $ticket, Request $request)
    {
        $this->assertCanSeeTicket($ticket, $request);

        // Marcar visto por asignado la primera vez que abre detalle
        if ((int) $ticket->quien_atiende_id === (int) $request->user()->id && ! $ticket->asignado_visto_at) {
            $ticket->asignado_visto_at = now();
            $ticket->save();
        }

        return response()->json(
            $ticket->load([
                'categoria',
                'prioridad',
                'estado',
                'solicitante:id,name,email',
                'asignado:id,name,email',
                'sede:id,nom_sede,cod_sede',
                'historial.user:id,name,email',
                'historial.adjuntos:id,historial_id,ticket_id,original_name,created_at',
            ])
        );
    }

    public function update(HelpDeskTickets $ticket, Request $request)
    {
        $this->assertCanSeeTicket($ticket, $request);

        // Solicitante no edita (decisión): solo soporte/admin
        if ($request->user()->hasRole(['Medico', 'Administrativo', 'Transcriptor']) && ! $request->user()->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria_id' => 'nullable|integer|exists:help_desk_categorias,id',
            'prioridad_id' => 'nullable|integer|exists:help_desk_prioridades,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $before = $ticket->replicate();

        if ($request->filled('titulo')) {
            $ticket->titulo = $request->titulo;
        }
        if ($request->filled('descripcion')) {
            $ticket->descripcion = $this->sanitizeHtml($request->descripcion);
        }
        if ($request->filled('categoria_id')) {
            $ticket->categoria_id = (int) $request->categoria_id;
        }
        if ($request->filled('prioridad_id')) {
            $ticket->prioridad_id = (int) $request->prioridad_id;
        }

        $ticket->save();

        // Historial de cambios (si aplica)
        $changedCategoria = (int) $before->categoria_id !== (int) $ticket->categoria_id;
        $changedPrioridad = (int) $before->prioridad_id !== (int) $ticket->prioridad_id;
        if ($changedCategoria || $changedPrioridad) {
            HelpDeskTicketHistorial::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'tipo' => 'COMENTARIO',
                'categoria_anterior_id' => $changedCategoria ? $before->categoria_id : null,
                'categoria_nueva_id' => $changedCategoria ? $ticket->categoria_id : null,
                'prioridad_anterior_id' => $changedPrioridad ? $before->prioridad_id : null,
                'prioridad_nueva_id' => $changedPrioridad ? $ticket->prioridad_id : null,
                'comentario' => 'Actualización de categoría/prioridad.',
            ]);
        }

        return response()->json($ticket);
    }

    public function destroy(HelpDeskTickets $ticket, Request $request)
    {
        // Solo Administrador (decisión)
        if (! $request->user()->hasRole('Administrador')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $ticket->delete();

        return response()->json(['ok' => true]);
    }

    public function comment(HelpDeskTickets $ticket, Request $request)
    {
        $this->assertCanSeeTicket($ticket, $request);

        $validator = Validator::make($request->all(), [
            'comentario' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $comentario = $this->sanitizeHtml($request->comentario);

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'tipo' => 'COMENTARIO',
            'comentario' => $comentario,
        ]);

        // Pendiente de (solo aplica Abierto/En Progreso)
        if ($this->isPendienteAplica($ticket)) {
            $isSoporte = $request->user()->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico']);
            $ticket->pendiente_de = $isSoporte ? 'SOLICITANTE' : 'SOPORTE';
            $ticket->save();
        }

        // Notificación interna a la otra parte
        $this->notifyPartes($ticket, 'COMENTARIO', 'Nuevo comentario', $request->user()->id);

        return response()->json(['ok' => true]);
    }

    public function reassign(HelpDeskTickets $ticket, Request $request)
    {
        $this->assertCanSeeTicket($ticket, $request);

        if (! $request->user()->hasRole(['Administrador', 'Soporte Manager'])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'asignado_id' => 'required|integer|exists:users,id',
            'comentario' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $asignado = User::query()->where('id', (int) $request->asignado_id)->where('estado', 1)->first();
        if (! $asignado || ! $asignado->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return response()->json(['message' => 'Asignado inválido'], 422);
        }

        $prev = $ticket->quien_atiende_id;
        $ticket->quien_atiende_id = $asignado->id;
        $ticket->save();

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'tipo' => 'REASIGNADO',
            'asignado_anterior_id' => $prev,
            'asignado_nuevo_id' => $asignado->id,
            'comentario' => $this->sanitizeHtml($request->comentario),
        ]);

        $this->notifyPartes($ticket, 'REASIGNADO', 'Ticket reasignado', null);

        return response()->json(['ok' => true]);
    }

    public function changeStatus(HelpDeskTickets $ticket, Request $request)
    {
        $this->assertCanSeeTicket($ticket, $request);

        if (! $request->user()->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            // Solicitante solo puede reabrir (decisión)
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'estado_id' => 'required|integer|exists:help_desk_estados,id',
            'comentario' => 'nullable|string',
            'descripcion_solucion' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos', 'errors' => $validator->errors()], 422);
        }

        $nuevo = HelpDeskEstado::query()->findOrFail((int) $request->estado_id);
        $actual = HelpDeskEstado::query()->find($ticket->estado_id);
        if (! $actual) {
            return response()->json(['message' => 'Estado actual inválido'], 500);
        }

        $comentario = $request->filled('comentario') ? $this->sanitizeHtml($request->comentario) : null;

        $transitionOk = $this->isValidTransition($actual->nombre, $nuevo->nombre);
        if (! $transitionOk) {
            return response()->json(['message' => 'Transición de estado no permitida'], 422);
        }

        // Reglas de comentario obligatorias
        if (in_array($nuevo->nombre, ['Resuelto', 'Cerrado'], true) && ! $comentario) {
            return response()->json(['message' => 'Comentario obligatorio para este cambio de estado'], 422);
        }
        if ($nuevo->nombre === 'Resuelto') {
            $descSol = $request->filled('descripcion_solucion') ? $this->sanitizeHtml($request->descripcion_solucion) : null;
            if (! $descSol) {
                return response()->json(['message' => 'Descripción solución obligatoria al marcar Resuelto'], 422);
            }
            $ticket->descripcion_solucion = $descSol;
            $ticket->quien_soluciona_id = $request->user()->id;
            $ticket->resolved_at = now();
            $ticket->fechor_sol_quien_soluciona = now();
        }
        if ($nuevo->nombre === 'Cerrado') {
            $ticket->closed_at = now();
        }

        $ticket->estado_id = $nuevo->id;

        // Si reabren (Cerrado -> Abierto)
        if ($actual->nombre === 'Cerrado' && $nuevo->nombre === 'Abierto') {
            $ticket->closed_at = null;
        }

        $ticket->save();

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'tipo' => 'CAMBIO_ESTADO',
            'estado_anterior_id' => $actual->id,
            'estado_nuevo_id' => $nuevo->id,
            'comentario' => $comentario,
        ]);

        $this->notifyPartes($ticket, 'CAMBIO_ESTADO', "Estado: {$nuevo->nombre}", null);

        return response()->json(['ok' => true]);
    }

    private function notifyPartes(HelpDeskTickets $ticket, string $evento, string $mensaje, ?int $exceptUserId): void
    {
        $titulo = (string) $ticket->titulo;
        $notif = new HelpDeskTicketNotification($ticket->id, $ticket->folio, $titulo, $evento, $mensaje, null);

        $sol = User::query()->find($ticket->quien_registra_id);
        $asg = User::query()->find($ticket->quien_atiende_id);

        if ($sol && (! $exceptUserId || (int) $sol->id !== (int) $exceptUserId)) {
            $sol->notify($notif);
        }
        if ($asg && (! $exceptUserId || (int) $asg->id !== (int) $exceptUserId)) {
            $asg->notify($notif);
        }
    }

    private function assertCanSeeTicket(HelpDeskTickets $ticket, Request $request): void
    {
        $user = $request->user();
        $isOwner = (int) $ticket->quien_registra_id === (int) $user->id;
        $isAssigned = (int) $ticket->quien_atiende_id === (int) $user->id;
        $isManager = $user->hasRole(['Administrador', 'Soporte Manager']);

        if (! $isOwner && ! $isAssigned && ! $isManager) {
            abort(response()->json(['message' => 'Forbidden'], 403));
        }
    }

    private function sanitizeHtml(string $html): string
    {
        $allowed = '<p><br><b><strong><i><em><u><ul><ol><li><a>';
        $clean = strip_tags($html, $allowed);
        $clean = preg_replace('/\son\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/href\s*=\s*([\'"])\s*javascript:[^\'"]*\1/i', 'href="#"', $clean) ?? $clean;

        return $clean;
    }

    private function isValidTransition(string $actual, string $nuevo): bool
    {
        $actual = mb_strtolower($actual);
        $nuevo = mb_strtolower($nuevo);

        $map = [
            'abierto' => ['en progreso'],
            'en progreso' => ['resuelto'],
            'resuelto' => ['cerrado'],
            'cerrado' => ['abierto'], // reabrir
        ];

        return in_array($nuevo, $map[$actual] ?? [], true);
    }

    private function isPendienteAplica(HelpDeskTickets $ticket): bool
    {
        $estado = HelpDeskEstado::query()->find($ticket->estado_id);
        if (! $estado) {
            return false;
        }

        return in_array($estado->nombre, ['Abierto', 'En Progreso'], true);
    }
}
