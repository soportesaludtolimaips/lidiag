<?php

namespace App\Services;

use App\Models\Citas\Cita;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigSedeWhatsappNumber;
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

    public function procesarMensaje(
        string $telefono,
        string $mensaje = '',
        string $tipoMensaje = 'text',
        ?string $mediaPath = null,
        ?array $mediaInfo = null,
        ?string $phoneNumberId = null
    ): ?string {
        $telefono = $this->formatearTelefono($telefono);
        $mensaje = trim($mensaje);
        $mensajeLower = mb_strtolower($mensaje);

        $conv = $this->obtenerOCrearConversacion($telefono, $phoneNumberId);
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
            'pedir_nombres' => $this->procesarNombres($conv, $mensaje),
            'pedir_primer_apellido' => $this->procesarPrimerApellido($conv, $mensaje),
            'pedir_segundo_apellido' => $this->procesarSegundoApellido($conv, $mensaje),
            'confirmar_datos_paciente' => $this->procesarConfirmarDatos($conv, $mensaje),
            'pedir_orden' => $this->procesarOrden($conv, $mensaje, $tipoMensaje, $mediaPath),
            'pedir_autorizacion' => $this->procesarAutorizacion($conv, $mensaje, $tipoMensaje, $mediaPath),
            default => $this->iniciarConversacion($conv),
        };
    }

    protected function iniciarConversacion(WhatsAppConversation $conv): string
    {
        $this->resetearConversacion($conv);

        $sedeId = $conv->sede_id;
        $query = ConfigServicioCita::where('estado', true);

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        $servicios = $query->orderBy('nombre')->get();

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

        $sedeId = $conv->sede_id;
        $query = ConfigServicioCita::where('estado', true);

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        $servicios = $query->orderBy('nombre')->get();

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

        return "✅ *Servicio seleccionado: {$servicio->nombre}*\n\n📄 *Por favor, ingrese su número de documento:*";
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

        if ($paciente) {
            $datos['paciente_id'] = $paciente->id;
            $datos['nombres'] = $paciente->nombres;
            $datos['pri_ape'] = $paciente->pri_ape;
            $datos['seg_ape'] = $paciente->seg_ape;

            $conv->update([
                'datos' => $datos,
                'paso' => 'pedir_orden',
            ]);

            $respuesta = "📄 *Documento: {$documento}*\n";
            $respuesta .= "👤 Paciente: {$paciente->nombres} {$paciente->pri_ape} {$paciente->seg_ape}\n\n";
            $respuesta .= "*Ahora envíe la orden médica.*\n";
            $respuesta .= "Adjunte la imagen o PDF de la orden.\n";
            $respuesta .= "_(Opcional: escriba el número en el texto o en el pie del archivo)_";

            return $respuesta;
        }

        $datos['paciente_id'] = null;
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_nombres',
        ]);

        return "⚠️ No encontramos su documento en el sistema.\n\n".
               "Por favor, escriba sus *nombres completos*:";
    }

    protected function procesarNombres(WhatsAppConversation $conv, string $nombres): string
    {
        $nombres = trim($nombres);

        if (empty($nombres) || strlen($nombres) < 3) {
            return "❌ Por favor ingrese sus nombres completos válidos.";
        }

        $datos = $conv->datos ?: [];
        $datos['nombres'] = $nombres;
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_primer_apellido',
        ]);

        return "Gracias. Ahora escriba su *primer apellido*:";
    }

    protected function procesarPrimerApellido(WhatsAppConversation $conv, string $apellido): string
    {
        $apellido = trim($apellido);

        if (empty($apellido) || strlen($apellido) < 2) {
            return "❌ Por favor ingrese un apellido válido.";
        }

        $datos = $conv->datos ?: [];
        $datos['pri_ape'] = $apellido;
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_segundo_apellido',
        ]);

        return "Gracias. Ahora escriba su *segundo apellido* (o escriba *0* si no tiene):";
    }

    protected function procesarSegundoApellido(WhatsAppConversation $conv, string $apellido): string
    {
        $apellido = trim($apellido);

        $datos = $conv->datos ?: [];
        $datos['seg_ape'] = ($apellido === '0') ? '' : $apellido;

        $nombreCompleto = $datos['nombres'].' '.$datos['pri_ape'].' '.$datos['seg_ape'];

        $conv->update([
            'datos' => $datos,
            'paso' => 'confirmar_datos_paciente',
        ]);

        return "📋 *Confirme sus datos:*\n\n".
               "• Nombres: {$datos['nombres']}\n".
               "• Primer apellido: {$datos['pri_ape']}\n".
               "• Segundo apellido: ".($datos['seg_ape'] ?: '—')."\n".
               "• Documento: {$datos['num_docu']}\n\n".
               "¿Los datos son correctos?\n".
               "1. *Sí, continuar*\n".
               "2. *No, corregir*";
    }

    protected function procesarConfirmarDatos(WhatsAppConversation $conv, string $respuesta): string
    {
        $respuesta = trim(mb_strtolower($respuesta));

        if (in_array($respuesta, ['1', 'si', 'sí', 'correcto', 'continuar'])) {
            return $this->crearPacienteYContinuar($conv);
        }

        $datos = $conv->datos ?: [];
        unset($datos['nombres'], $datos['pri_ape'], $datos['seg_ape']);
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_nombres',
        ]);

        return "Entendido. Vamos a empezar de nuevo.\n\nPor favor, escriba sus *nombres completos*:";
    }

    protected function crearPacienteYContinuar(WhatsAppConversation $conv): string
    {
        $datos = $conv->datos ?: [];

        $paciente = Paciente::create([
            'tipo_documento_id' => 1,
            'num_docu' => $datos['num_docu'],
            'nombres' => $datos['nombres'],
            'pri_ape' => $datos['pri_ape'],
            'seg_ape' => $datos['seg_ape'] ?? '',
            'cel' => $conv->telefono,
        ]);

        $datos['paciente_id'] = $paciente->id;

        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_orden',
        ]);

        $respuesta = "✅ *Paciente registrado exitosamente!*\n\n";
        $respuesta .= "*Ahora envíe la orden médica.*\n";
        $respuesta .= "Adjunte la imagen o PDF de la orden.\n";
        $respuesta .= "_(Opcional: escriba el número en el texto o en el pie del archivo)_";

        return $respuesta;
    }

    protected function procesarOrden(
        WhatsAppConversation $conv,
        string $mensaje,
        string $tipoMensaje = 'text',
        ?string $mediaPath = null
    ): string {
        $datos = $conv->datos ?: [];

        if ($tipoMensaje !== 'text' && $mediaPath) {
            $datos['ruta_orden_media'] = $mediaPath;
        }

        if (! empty($mensaje)) {
            $datos['numero_orden'] = $mensaje;
        }

        if (empty($datos['ruta_orden_media']) && empty($datos['numero_orden'])) {
            return "❌ Debe adjuntar la orden médica (imagen o PDF) o escribir el número.\n\n*Envíe la orden médica.*";
        }

        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_autorizacion',
        ]);

        $response = "✅ *Orden registrada*";
        if (! empty($datos['numero_orden'])) {
            $response .= "\n🔢 Número: {$datos['numero_orden']}";
        }
        if (! empty($datos['ruta_orden_media'])) {
            $response .= "\n📎 Archivo de orden recibido.";
        }
        $response .= "\n\n*Ahora envíe la autorización médica.*\n";
        $response .= "Adjunte la imagen o PDF de la autorización.\n";
        $response .= "_(Opcional: escriba el número en el texto o en el pie del archivo)_";

        return $response;
    }

    protected function procesarAutorizacion(
        WhatsAppConversation $conv,
        string $mensaje,
        string $tipoMensaje = 'text',
        ?string $mediaPath = null
    ): string {
        $datos = $conv->datos ?: [];

        if ($tipoMensaje !== 'text' && $mediaPath) {
            $datos['ruta_autorizacion_media'] = $mediaPath;
        }

        if (! empty($mensaje)) {
            $datos['numero_autorizacion'] = $mensaje;
        }

        if (empty($datos['ruta_autorizacion_media']) && empty($datos['numero_autorizacion'])) {
            return "❌ Debe adjuntar la autorización (imagen o PDF) o escribir el número.\n\n*Envíe la autorización médica.*";
        }

        $pacienteId = $datos['paciente_id'] ?? null;
        $paciente = $pacienteId ? Paciente::find($pacienteId) : null;

        if (! $paciente) {
            $this->resetearConversacion($conv);
            return "❌ Error al crear el paciente. Por favor contacte a soporte.";
        }

        $cita = Cita::create([
            'paciente_id' => $paciente->id,
            'servicio_cita_id' => $datos['servicio_cita_id'],
            'sede_id' => $conv->sede_id,
            'quien_registra_id' => 1,
            'numero_orden' => $datos['numero_orden'] ?? null,
            'numero_autorizacion' => $datos['numero_autorizacion'] ?? null,
            'ruta_orden_media' => $datos['ruta_orden_media'] ?? null,
            'ruta_autorizacion_media' => $datos['ruta_autorizacion_media'] ?? null,
            'estado' => 'solicitada',
            'whatsapp_token' => bin2hex(random_bytes(16)),
        ]);

        $conv->update([
            'cita_id' => $cita->id,
            'paso' => 'confirmar_solicitud',
        ]);

        $servicio = $datos['servicio_nombre'] ?? 'Estudio';
        $orden = $datos['numero_orden'] ?? '—';
        $autorizacion = $datos['numero_autorizacion'] ?? '—';
        $nombrePaciente = $datos['nombres'].' '.($datos['pri_ape'] ?? '').' '.($datos['seg_ape'] ?? '');

        $mensajeR = "✅ *Solicitud de Cita Confirmada*\n\n";
        $mensajeR .= "📋 *Detalles:*\n";
        $mensajeR .= "• Servicio: {$servicio}\n";
        $mensajeR .= "• Paciente: {$nombrePaciente}\n";
        $mensajeR .= "• Documento: {$datos['num_docu']}\n";
        $mensajeR .= "• Orden: {$orden}\n";
        $mensajeR .= "• Autorización: {$autorizacion}\n";

        if (! empty($datos['ruta_orden_media'])) {
            $mensajeR .= "• 📎 Documento de orden adjunto\n";
        }
        if (! empty($datos['ruta_autorizacion_media'])) {
            $mensajeR .= "• 📎 Documento de autorización adjunto\n";
        }

        $mensajeR .= "\n⏰ Su solicitud ha sido registrada.\n";
        $mensajeR .= "En 24 horas será notificado con la fecha y hora de su cita.\n\n";
        $mensajeR .= "_Gracias por confiar en nosotros._";

        $this->whatsAppService->enviarSolicitudRecibida($cita);

        return $mensajeR;
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

    protected function obtenerOCrearConversacion(string $telefono, ?string $phoneNumberId = null): WhatsAppConversation
    {
        $conv = WhatsAppConversation::firstOrCreate(
            ['telefono' => $telefono],
            ['paso' => 'inicio', 'datos' => []]
        );

        if ($phoneNumberId && ! $conv->sede_id) {
            $number = ConfigSedeWhatsappNumber::where('phone_number_id', $phoneNumberId)
                ->where('activo', true)
                ->first();

            if ($number) {
                $conv->update(['sede_id' => $number->sede_id]);
            }
        }

        return $conv->fresh();
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
