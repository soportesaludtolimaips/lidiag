<?php

namespace App\Services\HelpDesk;

use App\Models\Configuracion\ConfigSede;
use App\Models\HelpDesk\HelpDeskEstado;
use App\Models\HelpDesk\HelpDeskFolioCounter;
use App\Models\HelpDesk\HelpDeskTicketHistorial;
use App\Models\HelpDesk\HelpDeskTickets;
use App\Models\User;
use App\Notifications\HelpDesk\HelpDeskTicketNotification;
use App\Services\BaseService;
use App\Services\HelpDesk\Traits\TicketAuthorization;
use Illuminate\Support\Facades\DB;

class HelpDeskTicketService extends BaseService
{
    use TicketAuthorization;

    /**
     * Lista los tickets aplicando filtros, ordenamiento por prioridad y paginación.
     *
     * @param  array  $filters  Filtros de consulta (scope, estado_id, categoria_id, prioridad_id, sede_id, desde, hasta, q, per_page, page).
     * @param  User  $user  Usuario autenticado que realiza la consulta.
     *
     * @returns array Respuesta con los items paginados y metadatos de paginación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function list(array $filters, User $user): array
    {
        $scope = $filters['scope'] ?? 'mine';

        if ($scope === 'all' && ! $user->hasRole(['Administrador', 'Soporte Manager'])) {
            return $this->unauthorized();
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

        if (! empty($filters['estado_id'])) {
            $q->where('estado_id', (int) $filters['estado_id']);
        }
        if (! empty($filters['categoria_id'])) {
            $q->where('categoria_id', (int) $filters['categoria_id']);
        }
        if (! empty($filters['prioridad_id'])) {
            $q->where('prioridad_id', (int) $filters['prioridad_id']);
        }
        if (! empty($filters['sede_id'])) {
            $q->where('sedes_id', (int) $filters['sede_id']);
        }
        if (! empty($filters['desde'])) {
            $q->whereDate('created_at', '>=', $filters['desde']);
        }
        if (! empty($filters['hasta'])) {
            $q->whereDate('created_at', '<=', $filters['hasta']);
        }
        if (! empty($filters['q'])) {
            $term = trim((string) $filters['q']);
            // Escapar % y _ para evitar wildcards no intencionales en LIKE
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $term);
            $q->where(function ($qq) use ($escaped) {
                $qq->where('folio', 'like', "%{$escaped}%")
                    ->orWhere('titulo', 'like', "%{$escaped}%")
                    ->orWhere('descripcion', 'like', "%{$escaped}%");
            });
        }

        $q->leftJoin('help_desk_prioridades as p', 'help_desk_tickets.prioridad_id', '=', 'p.id')
            ->orderByRaw('COALESCE(p.orden, 999999) asc')
            ->orderByDesc('help_desk_tickets.created_at')
            ->select('help_desk_tickets.*');

        $perPage = max(1, min(200, (int) ($filters['per_page'] ?? 50)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $paginator = $q->paginate(perPage: $perPage, page: $page);

        return $this->success([
            'items' => $paginator->items(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    /**
     * Obtiene el detalle de un ticket con todas sus relaciones y marca como visto si aplica.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a consultar.
     * @param  User  $user  Usuario autenticado que realiza la consulta.
     *
     * @returns array Respuesta con el ticket y sus relaciones cargadas.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function find(HelpDeskTickets $ticket, User $user): array
    {
        $this->assertCanSeeTicket($ticket, $user);

        if ((int) $ticket->quien_atiende_id === (int) $user->id && ! $ticket->asignado_visto_at) {
            $ticket->asignado_visto_at = now();
            $ticket->save();
        }

        return $this->success(
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

    /**
     * Crea un nuevo ticket generando su folio, estado inicial y notificando a las partes.
     *
     * @param  array  $data  Datos validados del ticket (titulo, descripcion, categoria_id, prioridad_id, sede_id, asignado_id).
     * @param  User  $user  Usuario autenticado que registra el ticket.
     *
     * @returns array Respuesta con el ticket creado o error de validación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function create(array $data, User $user): array
    {
        $asignado = User::query()->where('id', (int) $data['asignado_id'])->where('estado', 1)->first();
        if (! $asignado || ! $asignado->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return $this->error('Asignado inválido', 422);
        }

        $estadoAbierto = HelpDeskEstado::query()->where('nombre', 'Abierto')->first();
        if (! $estadoAbierto) {
            return $this->error('Catálogo de estados no inicializado', 500);
        }

        $sede = ConfigSede::query()->select(['id', 'cod_sede'])->findOrFail((int) $data['sede_id']);
        if (! $sede->cod_sede) {
            return $this->error('La sede no tiene cod_sede configurado', 422);
        }

        $ticket = DB::transaction(function () use ($data, $user, $estadoAbierto, $sede, $asignado) {
            $now = now();
            $anio = (int) $now->format('Y');

            $counter = HelpDeskFolioCounter::query()->where('anio', $anio)->lockForUpdate()->first();
            if (! $counter) {
                $counter = HelpDeskFolioCounter::query()->where('anio', $anio)->first();
                if (! $counter) {
                    $counter = HelpDeskFolioCounter::create(['anio' => $anio, 'last_number' => 0]);
                }
            }

            $counter->last_number = (int) $counter->last_number + 1;
            $counter->save();

            $seq = str_pad((string) $counter->last_number, 5, '0', STR_PAD_LEFT);
            $fecha = $now->format('Ymd');
            $folio = "{$sede->cod_sede}-{$fecha}-{$seq}";

            $ticket = HelpDeskTickets::create([
                'folio' => $folio,
                'titulo' => $data['titulo'],
                'descripcion' => $this->sanitizeHtml($data['descripcion']),
                'categoria_id' => (int) $data['categoria_id'],
                'prioridad_id' => (int) $data['prioridad_id'],
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

            $msg = "Ticket creado ({$ticket->folio})";
            $notif = new HelpDeskTicketNotification($ticket->id, $ticket->folio, $ticket->titulo, 'CREADO', $msg);
            $user->notify($notif);
            if ((int) $asignado->id !== (int) $user->id) {
                $asignado->notify($notif);
            }

            return $ticket;
        });

        return $this->created($ticket->load(['categoria', 'prioridad', 'estado']));
    }

    /**
     * Actualiza campos editables de un ticket y registra cambios de categoría o prioridad en el historial.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a actualizar.
     * @param  array  $data  Datos validados a modificar.
     * @param  User  $user  Usuario autenticado que realiza la actualización.
     *
     * @returns array Respuesta con el ticket actualizado o error de permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function update(HelpDeskTickets $ticket, array $data, User $user): array
    {
        $this->assertCanSeeTicket($ticket, $user);

        if ($user->hasRole(['Medico', 'Administrativo', 'Transcriptor']) && ! $user->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return $this->unauthorized();
        }

        $before = $ticket->replicate();

        if (! empty($data['titulo'])) {
            $ticket->titulo = $data['titulo'];
        }
        if (! empty($data['descripcion'])) {
            $ticket->descripcion = $this->sanitizeHtml($data['descripcion']);
        }
        if (! empty($data['categoria_id'])) {
            $ticket->categoria_id = (int) $data['categoria_id'];
        }
        if (! empty($data['prioridad_id'])) {
            $ticket->prioridad_id = (int) $data['prioridad_id'];
        }

        $ticket->save();

        $changedCategoria = (int) $before->categoria_id !== (int) $ticket->categoria_id;
        $changedPrioridad = (int) $before->prioridad_id !== (int) $ticket->prioridad_id;
        if ($changedCategoria || $changedPrioridad) {
            HelpDeskTicketHistorial::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'tipo' => 'COMENTARIO',
                'categoria_anterior_id' => $changedCategoria ? $before->categoria_id : null,
                'categoria_nueva_id' => $changedCategoria ? $ticket->categoria_id : null,
                'prioridad_anterior_id' => $changedPrioridad ? $before->prioridad_id : null,
                'prioridad_nueva_id' => $changedPrioridad ? $ticket->prioridad_id : null,
                'comentario' => 'Actualización de categoría/prioridad.',
            ]);
        }

        return $this->success($ticket);
    }

    /**
     * Elimina un ticket (solo administradores y siempre que esté en estado Abierto o Cerrado).
     *
     * @param  HelpDeskTickets  $ticket  Ticket a eliminar.
     * @param  User  $user  Usuario autenticado que realiza la eliminación.
     *
     * @returns array Respuesta de eliminación o error de validación/permisos.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function delete(HelpDeskTickets $ticket, User $user): array
    {
        if (! $user->hasRole('Administrador')) {
            return $this->unauthorized();
        }

        $estado = HelpDeskEstado::query()->find($ticket->estado_id);
        if ($estado && ! in_array($estado->nombre, ['Abierto', 'Cerrado'], true)) {
            return $this->error('Solo se pueden eliminar tickets en estado Abierto o Cerrado.', 422);
        }

        $ticket->delete();

        return $this->deleted();
    }

    /**
     * Registra un comentario en el historial del ticket y ajusta el campo pendiente_de.
     *
     * @param  HelpDeskTickets  $ticket  Ticket comentado.
     * @param  array  $data  Datos validados que incluyen el comentario.
     * @param  User  $user  Usuario autenticado que comenta.
     *
     * @returns array Respuesta con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function comment(HelpDeskTickets $ticket, array $data, User $user): array
    {
        $this->assertCanSeeTicket($ticket, $user);

        $comentario = $this->sanitizeHtml($data['comentario']);

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'tipo' => 'COMENTARIO',
            'comentario' => $comentario,
        ]);

        if ($this->isPendienteAplica($ticket)) {
            $isSoporte = $user->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico']);
            $ticket->pendiente_de = $isSoporte ? 'SOLICITANTE' : 'SOPORTE';
            $ticket->save();
        }

        $this->notifyPartes($ticket, 'COMENTARIO', 'Nuevo comentario', $user->id);

        return $this->success(['ok' => true]);
    }

    /**
     * Reasigna el ticket a otro usuario de soporte y notifica a las partes.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a reasignar.
     * @param  array  $data  Datos validados con el asignado_id y comentario.
     * @param  User  $user  Usuario autenticado con permisos de reasignación.
     *
     * @returns array Respuesta con resultado de la operación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function reassign(HelpDeskTickets $ticket, array $data, User $user): array
    {
        $this->assertCanSeeTicket($ticket, $user);

        if (! $user->hasRole(['Administrador', 'Soporte Manager'])) {
            return $this->unauthorized();
        }

        $asignado = User::query()->where('id', (int) $data['asignado_id'])->where('estado', 1)->first();
        if (! $asignado || ! $asignado->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return $this->error('Asignado inválido', 422);
        }

        $prev = $ticket->quien_atiende_id;
        $ticket->quien_atiende_id = $asignado->id;
        $ticket->save();

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'tipo' => 'REASIGNADO',
            'asignado_anterior_id' => $prev,
            'asignado_nuevo_id' => $asignado->id,
            'comentario' => $this->sanitizeHtml($data['comentario']),
        ]);

        $this->notifyPartes($ticket, 'REASIGNADO', 'Ticket reasignado', null);

        return $this->success(['ok' => true]);
    }

    /**
     * Cambia el estado del ticket validando la transición, solución o cierre y notificando.
     *
     * @param  HelpDeskTickets  $ticket  Ticket a cambiar de estado.
     * @param  array  $data  Datos validados con estado_id y comentario/solución.
     * @param  User  $user  Usuario autenticado con permisos de soporte.
     *
     * @returns array Respuesta con resultado de la operación o error de transición.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-02-13
     */
    public function changeStatus(HelpDeskTickets $ticket, array $data, User $user): array
    {
        $this->assertCanSeeTicket($ticket, $user);

        if (! $user->hasRole(['Administrador', 'Soporte Manager', 'Soporte Tecnico'])) {
            return $this->unauthorized();
        }

        $nuevo = HelpDeskEstado::query()->findOrFail((int) $data['estado_id']);
        $actual = HelpDeskEstado::query()->find($ticket->estado_id);
        if (! $actual) {
            return $this->error('Estado actual inválido', 500);
        }

        $comentario = ! empty($data['comentario']) ? $this->sanitizeHtml($data['comentario']) : null;

        $transitionOk = $this->isValidTransition($actual->nombre, $nuevo->nombre);
        if (! $transitionOk) {
            return $this->error('Transición de estado no permitida', 422);
        }

        if (in_array($nuevo->nombre, ['Resuelto', 'Cerrado'], true) && ! $comentario) {
            return $this->error('Comentario obligatorio para este cambio de estado', 422);
        }
        if ($nuevo->nombre === 'Resuelto') {
            $descSol = ! empty($data['descripcion_solucion']) ? $this->sanitizeHtml($data['descripcion_solucion']) : null;
            if (! $descSol) {
                return $this->error('Descripción solución obligatoria al marcar Resuelto', 422);
            }
            $ticket->descripcion_solucion = $descSol;
            $ticket->quien_soluciona_id = $user->id;
            $ticket->resolved_at = now();
            $ticket->fechor_sol_quien_soluciona = now();
        }
        if ($nuevo->nombre === 'Cerrado') {
            $ticket->closed_at = now();
        }

        $ticket->estado_id = $nuevo->id;

        if ($actual->nombre === 'Cerrado' && $nuevo->nombre === 'Abierto') {
            $ticket->closed_at = null;
        }

        $ticket->save();

        HelpDeskTicketHistorial::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'tipo' => 'CAMBIO_ESTADO',
            'estado_anterior_id' => $actual->id,
            'estado_nuevo_id' => $nuevo->id,
            'comentario' => $comentario,
        ]);

        $this->notifyPartes($ticket, 'CAMBIO_ESTADO', "Estado: {$nuevo->nombre}", null);

        return $this->success(['ok' => true]);
    }

    private function notifyPartes(HelpDeskTickets $ticket, string $evento, string $mensaje, ?int $exceptUserId): void
    {
        $titulo = (string) $ticket->titulo;
        $notif = new HelpDeskTicketNotification($ticket->id, $ticket->folio, $titulo, $evento, $mensaje);

        // Cargar relaciones en una sola query en lugar de N queries separadas
        $ticket->loadMissing(['solicitante', 'asignado']);

        $sol = $ticket->solicitante;
        $asg = $ticket->asignado;

        if ($sol && (! $exceptUserId || (int) $sol->id !== (int) $exceptUserId)) {
            $sol->notify($notif);
        }
        if ($asg && (! $exceptUserId || (int) $asg->id !== (int) $exceptUserId)) {
            $asg->notify($notif);
        }
    }

    private function isValidTransition(string $actual, string $nuevo): bool
    {
        $actual = mb_strtolower($actual);
        $nuevo = mb_strtolower($nuevo);

        $map = [
            'abierto' => ['en progreso'],
            'en progreso' => ['resuelto'],
            'resuelto' => ['cerrado'],
            'cerrado' => ['abierto'],
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
