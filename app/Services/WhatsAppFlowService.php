<?php

namespace App\Services;

use App\Models\Citas\Cita;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\General\Paciente;
use App\Models\Interface\Sahi\Paciente as SahiPaciente;

class WhatsAppFlowService
{
    protected $whatsAppService;

    protected $PASOS = [
        'inicio' => 'saludo_inicial',
        'saludo_inicial' => 'seleccionar_servicio',
        'seleccionar_servicio' => 'pedir_documento',
        'pedir_documento' => 'buscar_paciente',
        'buscar_paciente' => 'pedir_orden',
        'pedir_orden' => 'pedir_autorizacion',
        'pedir_autorizacion' => 'confirmar_solicitud',
        'confirmar_solicitud' => 'finalizado',
    ];

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    public function procesarMensaje(string $telefono, string $mensaje): ?string
    {
        $telefono = $this->formatearTelefono($telefono);
        $mensaje = trim($mensaje);
        $mensajeLower = mb_strtolower($mensaje);

        $conv = $this->obtenerOCrearConversacion($telefono);
        $paso = $conv->paso;

        if ($mensajeLower === 'hola' || $mensajeLower === 'hi' || $mensajeLower === 'inicio' || $mensajeLower === '0') {
            return $this->iniciarConversacion($conv);
        }

        if ($mensajeLower === 'menu' || $mensajeLower === 'menú') {
            $this->resetearConversacion($conv);

            return $this->iniciarConversacion($conv);
        }

        return match ($paso) {
            'inicio' => $this->iniciarConversacion($conv),
            'seleccionar_servicio' => $this->procesarSeleccionServicio($conv, $mensaje),
            'pedir_documento' => $this->procesarDocumento($conv, $mensaje),
            'pedir_orden' => $this->procesarOrden($conv, $mensaje),
            'pedir_autorizacion' => $this->procesarAutorizacion($conv, $mensaje),
            default => $this->iniciarConversacion($conv),
        };
    }

    protected function iniciarConversacion(WhatsAppConversation $conv): string
    {
        $this->resetearConversacion($conv);

        $servicios = ConfigServicioCita::where('estado', true)->orderBy('nombre')->get();

        if ($servicios->isEmpty()) {
            return "Lo sentimos, actualmente no hay servicios disponibles.";
        }

        $mensaje = "🏥 *Bienvenido al sistema de citas médicas.*\n\n";
        $mensaje .= "Seleccione el servicio para su cita:\n\n";

        foreach ($servicios as $i => $serv) {
            $num = $i + 1;
            $mensaje .= "{$num}. {$serv->nombre}\n";
        }

        $mensaje .= "\n*Escriba el número de su opción.*";

        $conv->update(['paso' => 'seleccionar_servicio']);

        return $mensaje;
    }

    protected function procesarSeleccionServicio(WhatsAppConversation $conv, string $opcion): string
    {
        $opcion = trim($opcion);
        $numero = is_numeric($opcion) ? (int) $opcion : 0;

        $servicios = ConfigServicioCita::where('estado', true)->orderBy('nombre')->get();

        if ($numero < 1 || $numero > $servicios->count()) {
            return "❌ Opción inválida. Por favor seleccione un número de la lista.";
        }

        $servicio = $servicios[$numero - 1];

        $datos = $conv->datos ?: [];
        $datos['servicio_cita_id'] = $servicio->id;
        $datos['servicio_nombre'] = $servicio->nombre;
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_documento',
        ]);

        return "✅ *Servicio seleccionado: {$servicio->nombre}*\n\n";
    }

    protected function procesarDocumento(WhatsAppConversation $conv, string $documento): string
    {
        $documento = trim($documento);

        if (empty($documento) || strlen($documento) < 4) {
            return "❌ Número de documento inválido. Por favor ingrese un número de documento válido.";
        }

        $paciente = $this->buscarPaciente($documento);

        $datos = $conv->datos ?: [];
        $datos['num_docu'] = $documento;
        $datos['paciente_id'] = $paciente?->id;
        $datos['nombres'] = $paciente ? $paciente->nombres.' '.$paciente->pri_ape : 'Nuevo paciente';
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_orden',
        ]);

        $respuesta = "📄 *Documento: {$documento}*";

        if ($paciente) {
            $respuesta .= "\n👤 Paciente: {$paciente->nombres} {$paciente->pri_ape}";
        } else {
            $respuesta .= "\n⚠️ Paciente no encontrado en el sistema.";
        }

        $respuesta .= "\n\n*Ahora ingrese el número de orden médica.*";

        return $respuesta;
    }

    protected function procesarOrden(WhatsAppConversation $conv, string $orden): string
    {
        $datos = $conv->datos ?: [];
        $datos['numero_orden'] = trim($orden);
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_autorizacion',
        ]);

        return "✅ *Orden registrada: {$orden}*\n\n";
    }

    protected function procesarAutorizacion(WhatsAppConversation $conv, string $autorizacion): string
    {
        $datos = $conv->datos ?: [];
        $datos['numero_autorizacion'] = trim($autorizacion);

        $telefono = $conv->telefono;

        $paciente = $this->buscarOCrearPaciente($datos, $telefono);

        if (! $paciente) {
            $this->resetearConversacion($conv);

            return "❌ Error al crear el paciente. Por favor contacte a soporte.";
        }

        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_cita_id' => $datos['servicio_cita_id'],
            'quien_registra_id' => 1,
            'numero_orden' => $datos['numero_orden'] ?? null,
            'numero_autorizacion' => $datos['numero_autorizacion'] ?? null,
            'estado' => 'solicitada',
            'whatsapp_token' => bin2hex(random_bytes(16)),
        ]);

        $conv->update([
            'cita_id' => $cita->id,
            'paso' => 'confirmar_solicitud',
        ]);

        $servicio = $datos['servicio_nombre'] ?? 'Estudio';

        $mensaje = "✅ *Solicitud de Cita Confirmada*\n\n";
        $mensaje .= "📋 *Detalles:*\n";
        $mensaje .= "• Servicio: {$servicio}\n";
        $mensaje .= "• Paciente: {$datos['nombres']}\n";
        $mensaje .= "• Documento: {$datos['num_docu']}\n";
        $mensaje .= "• Orden: {$datos['numero_orden']}\n";
        $mensaje .= "• Autorización: {$datos['numero_autorizacion']}\n\n";
        $mensaje .= "⏰ Su solicitud ha sido registrada.\n";
        $mensaje .= "En 24 horas será notificado con la fecha y hora de su cita.\n\n";
        $mensaje .= "_Gracias por confiar en nosotros._";

        $this->whatsAppService->enviarConfirmacion($cita);

        return $mensaje;
    }

    protected function buscarPaciente(string $documento): ?Paciente
    {
        $paciente = Paciente::where('num_docu', $documento)->first();

        if ($paciente) {
            return $paciente;
        }

        $sahiPaciente = SahiPaciente::where('PA_NUMDOCU', $documento)->first();

        if ($sahiPaciente) {
            return Paciente::create([
                'tipo_documento_id' => 1,
                'num_docu' => $sahiPaciente->PA_NUMDOCU,
                'nombres' => $sahiPaciente->PA_NOMB1.' '.$sahiPaciente->PA_NOMB2,
                'pri_ape' => $sahiPaciente->PA_APELL1,
                'seg_ape' => $sahiPaciente->PA_APELL2,
                'sexo' => $sahiPaciente->PA_SEXO,
                'fec_naci' => $sahiPaciente->PA_FENAC,
                'direccion' => $sahiPaciente->PA_DIRECC,
                'tel' => $sahiPaciente->PA_TELEFO,
            ]);
        }

        return null;
    }

    protected function buscarOCrearPaciente(array $datos, string $telefono): ?Paciente
    {
        if (! empty($datos['paciente_id'])) {
            return Paciente::find($datos['paciente_id']);
        }

        return Paciente::create([
            'tipo_documento_id' => 1,
            'num_docu' => $datos['num_docu'] ?? null,
            'nombres' => $datos['nombres'] ?? 'Paciente',
            'cel' => $telefono,
        ]);
    }

    protected function obtenerOCrearConversacion(string $telefono): WhatsAppConversation
    {
        return WhatsAppConversation::firstOrCreate(
            ['telefono' => $telefono],
            ['paso' => 'inicio', 'datos' => []]
        );
    }

    protected function resetearConversacion(WhatsAppConversation $conv): void
    {
        $conv->update([
            'paso' => 'inicio',
            'datos' => [],
            'cita_id' => null,
        ]);
    }

    protected function formatearTelefono(string $telefono): string
    {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        if (str_starts_with($telefono, '57')) {
            return $telefono;
        }

        if (str_starts_with($telefono, '0')) {
            return '57'.substr($telefono, 1);
        }

        if (str_starts_with($telefono, '3')) {
            return '57'.$telefono;
        }

        return $telefono;
    }
}
