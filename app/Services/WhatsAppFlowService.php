<?php

namespace App\Services;

use App\Models\Agenda\AgendaCita;
use App\Models\Citas\WhatsAppConversation;
use App\Models\Configuracion\ConfigSede;
use App\Models\Configuracion\ConfigSedeWhatsappNumber;
use App\Models\Configuracion\ConfigServicioCita;
use App\Models\Configuracion\ConfigWhatsapp;
use App\Models\General\Paciente;
use App\Models\Interface\Sahi\Paciente as SahiPaciente;
use App\Models\User;
use App\Notifications\Citas\CitaSolicitadaNotification;
use Illuminate\Support\Facades\Notification;

class WhatsAppFlowService
{
    protected $whatsAppService;

    protected const SEPARADOR = "\n━━━━━━━━━━━━━━━━━━\n";

    protected const EXTENSIONES_PERMITIDAS = ['jpg', 'jpeg', 'png', 'pdf'];

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
    ): ?array {
        try {
            $telefono = $this->formatearTelefono($telefono);
            $mensaje = trim($mensaje);
            $mensajeLower = mb_strtolower($mensaje);

            $conv = $this->obtenerOCrearConversacion($telefono, $phoneNumberId);
            $paso = $conv->paso;

            $pasosBloqueados = ['pedir_foto_cedula_frente', 'pedir_foto_cedula_dorso', 'pedir_orden', 'pedir_autorizacion'];

            if (in_array($paso, $pasosBloqueados, true)) {
                if ($mensajeLower === '0' || $mensajeLower === 'menu' || $mensajeLower === 'menú') {
                    $this->resetearConversacion($conv);
                    return $this->iniciarConversacion($conv);
                }
                if ($mensajeLower === 'hola' || $mensajeLower === 'hi' || $mensajeLower === 'inicio') {
                    return $this->recordatorioArchivo($paso);
                }
            } else {
                if ($mensajeLower === 'hola' || $mensajeLower === 'hi' || $mensajeLower === 'inicio' || $mensajeLower === '0') {
                    return $this->iniciarConversacion($conv);
                }

                if ($mensajeLower === 'menu' || $mensajeLower === 'menú') {
                    $this->resetearConversacion($conv);
                    return $this->iniciarConversacion($conv);
                }
            }

            return match ($paso) {
                'inicio' => $this->iniciarConversacion($conv),
                'seleccionar_sede' => $this->procesarSeleccionSede($conv, $mensaje),
                'seleccionar_servicio' => $this->procesarSeleccionServicio($conv, $mensaje),
                'pedir_documento' => $this->procesarDocumento($conv, $mensaje),
                'confirmar_datos_completos' => $this->procesarConfirmarDatosCompletos($conv, $mensaje),
                'pedir_nombres' => $this->procesarNombres($conv, $mensaje),
                'pedir_primer_apellido' => $this->procesarPrimerApellido($conv, $mensaje),
                'pedir_segundo_apellido' => $this->procesarSegundoApellido($conv, $mensaje),
                'confirmar_datos_paciente' => $this->procesarConfirmarDatos($conv, $mensaje),
                'confirmar_direccion' => $this->procesarConfirmarDireccion($conv, $mensaje),
                'editar_direccion' => $this->procesarEditarDireccion($conv, $mensaje),
                'confirmar_telefono1' => $this->procesarConfirmarTelefono1($conv, $mensaje),
                'editar_telefono1' => $this->procesarEditarTelefono1($conv, $mensaje),
                'confirmar_telefono2' => $this->procesarConfirmarTelefono2($conv, $mensaje),
                'editar_telefono2' => $this->procesarEditarTelefono2($conv, $mensaje),
                'pedir_direccion' => $this->procesarDireccion($conv, $mensaje),
                'pedir_telefono1' => $this->procesarTelefono1($conv, $mensaje),
                'pedir_telefono2' => $this->procesarTelefono2($conv, $mensaje),
                'pedir_foto_cedula_frente' => $this->procesarFotoCedulaFrente($conv, $mensaje, $tipoMensaje, $mediaPath),
                'pedir_foto_cedula_dorso' => $this->procesarFotoCedulaDorso($conv, $mensaje, $tipoMensaje, $mediaPath),
                'pedir_orden' => $this->procesarOrden($conv, $mensaje, $tipoMensaje, $mediaPath),
                'pedir_autorizacion' => $this->procesarAutorizacion($conv, $mensaje, $tipoMensaje, $mediaPath),
                default => $this->iniciarConversacion($conv),
            };
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WhatsAppFlow error: '.$e->getMessage(), [
                'telefono' => $telefono,
                'paso' => $paso ?? 'unknown',
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->text("❌ Ocurrió un error inesperado. Por favor intente de nuevo más tarde.");
        }
    }

    protected function text(string $mensaje): array
    {
        return ['type' => 'text', 'text' => $mensaje];
    }

    protected function validarMedia(string $mediaPath, string $mensajePaso): ?array
    {
        $extension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
        if (! in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
            $mensaje = "❌ *Formato no permitido: .{$extension}*\n\n"
                     . "Solo se aceptan archivos con extensión: *JPG, JPEG, PNG o PDF*."
                     . self::SEPARADOR
                     . $mensajePaso;
            return $this->text($mensaje);
        }
        return null;
    }

    protected function recordatorioArchivo(string $paso): array
    {
        $documento = match ($paso) {
            'pedir_autorizacion' => 'autorización médica',
            'pedir_orden' => 'orden médica',
            'pedir_foto_cedula_frente' => 'foto frontal de la cédula',
            'pedir_foto_cedula_dorso' => 'foto posterior de la cédula',
            default => 'el archivo solicitado',
        };

        $instrucciones = match ($paso) {
            'pedir_foto_cedula_frente' => "📷 Adjunte la *foto del frente* de su cédula.\n_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_",
            'pedir_foto_cedula_dorso' => "📷 Adjunte la *foto del reverso* de su cédula.\n_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_",
            default => "Adjunte la imagen o PDF.\n_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_",
        };

        $mensaje = "⛔ *No puede saltar este paso.*\n\n"
                 . "Para continuar, debe adjuntar la {$documento} como archivo.\n"
                 . "📋 Solo se aceptan: *JPG, JPEG, PNG o PDF*."
                 . self::SEPARADOR
                 . $instrucciones;

        return $this->text($mensaje);
    }

    protected function iniciarConversacion(WhatsAppConversation $conv): array
    {
        $sedes = ConfigSede::where('estado', true)
            ->whereHas('servicios', fn($q) => $q->where('estado', true))
            ->orderBy('nom_sede')
            ->get();

        if ($sedes->isEmpty()) {
            $this->resetearConversacion($conv);
            return $this->text("Lo sentimos, actualmente no hay sedes con servicios disponibles.");
        }

        $this->resetearConversacion($conv);
        $conv = $conv->fresh();

        $conv->update(['paso' => 'pedir_documento']);

        $mensaje = $this->obtenerMensajeBienvenida($conv);

        return $this->text($mensaje);
    }

    protected function obtenerMensajeBienvenida(WhatsAppConversation $conv): string
    {
        $sedeId = $conv->sede_id;
        $servicioId = null;

        $datos = $conv->datos ?: [];
        if (isset($datos['servicio_cita_id'])) {
            $servicioId = $datos['servicio_cita_id'];
        }

        $config = ConfigWhatsapp::paraSedeYservicio($sedeId, $servicioId)->first();

        if ($config && ! empty($config->mensaje_bienvenida)) {
            return $config->mensaje_bienvenida;
        }

        return "📄 *Bienvenido al sistema de citas de Salud Tolima*\n\n" .
               "Por favor, ingrese su *número de documento*:";
    }

    protected function mostrarSedes(WhatsAppConversation $conv): array
    {
        $sedes = ConfigSede::where('estado', true)
            ->whereHas('servicios', function ($q) {
                $q->where('estado', true);
            })
            ->orderBy('nom_sede')
            ->get();

        if ($sedes->isEmpty()) {
            return $this->text("Lo sentimos, actualmente no hay sedes con servicios disponibles.");
        }

        $rows = [];
        foreach ($sedes as $i => $sede) {
            $rows[] = [
                'id' => (string) ($i + 1),
                'title' => mb_substr($this->tituloSede($sede), 0, 24),
                'description' => $this->descripcionSede($sede),
            ];
        }

        $conv->update(['paso' => 'seleccionar_sede']);

        return [
            'type' => 'list',
            'header' => '🏥 Sistema de Citas',
            'body' => "Seleccione la *sede* donde desea su cita.",
            'button' => 'Ver sedes',
            'footer' => 'Toque una opción para continuar',
            'sections' => [
                [
                    'title' => 'Sedes disponibles',
                    'rows' => $rows,
                ],
            ],
        ];
    }

    protected function tituloSede(ConfigSede $sede): string
    {
        $partes = explode(' - ', $sede->nom_sede, 2);

        return trim($partes[0]);
    }

    protected function descripcionSede(ConfigSede $sede): string
    {
        $partes = explode(' - ', $sede->nom_sede, 2);
        $hospital = isset($partes[1]) ? trim($partes[1]) : '';

        $count = $sede->servicios()->where('estado', true)->count();
        $servicios = $count === 1 ? '1 servicio' : "{$count} servicios";

        $desc = $servicios;
        if (! empty($hospital)) {
            $desc = "{$hospital} · {$servicios}";
        }

        return mb_substr($desc, 0, 72);
    }

    protected function procesarSeleccionSede(WhatsAppConversation $conv, string $opcion): array
    {
        $opcion = trim($opcion);
        $numero = is_numeric($opcion) ? (int) $opcion : 0;

        $sedes = ConfigSede::where('estado', true)
            ->whereHas('servicios', function ($q) {
                $q->where('estado', true);
            })
            ->orderBy('nom_sede')
            ->get();

        if ($numero < 1 || $numero > $sedes->count()) {
            return $this->iniciarConversacion($conv);
        }

        $sede = $sedes[$numero - 1];

        $conv->update(['sede_id' => $sede->id]);

        return $this->mostrarServicios($conv->fresh());
    }

    protected function mostrarServicios(WhatsAppConversation $conv): array
    {
        $sedeId = $conv->sede_id;
        $servicios = ConfigServicioCita::where('estado', true)
            ->where('sede_id', $sedeId)
            ->orderBy('nombre')
            ->get();

        if ($servicios->isEmpty()) {
            return $this->text("Lo sentimos, la sede seleccionada no tiene servicios disponibles.");
        }

        $sede = ConfigSede::find($sedeId);

        $rows = [];
        foreach ($servicios as $i => $serv) {
            $rows[] = [
                'id' => (string) ($i + 1),
                'title' => mb_substr($serv->nombre, 0, 24),
                'description' => mb_substr($this->descripcionServicio($serv), 0, 72),
            ];
        }

        $conv->update(['paso' => 'seleccionar_servicio']);

        return [
            'type' => 'list',
            'header' => "🏥 {$sede->nom_sede}",
            'body' => "Sede seleccionada correctamente.\n\nSeleccione el *servicio* para su cita.",
            'button' => 'Ver servicios',
            'footer' => 'Toque una opción para continuar',
            'sections' => [
                [
                    'title' => 'Servicios disponibles',
                    'rows' => $rows,
                ],
            ],
        ];
    }

    protected function descripcionServicio(ConfigServicioCita $serv): string
    {
        $count = $serv->productos()->count();
        if ($count === 0) {
            return 'Servicio médico';
        }

        return $count === 1 ? '1 estudio disponible' : "{$count} estudios disponibles";
    }

    protected function procesarSeleccionServicio(WhatsAppConversation $conv, string $opcion): array
    {
        $opcion = trim($opcion);
        $numero = is_numeric($opcion) ? (int) $opcion : 0;

        $servicios = ConfigServicioCita::where('estado', true)
            ->where('sede_id', $conv->sede_id)
            ->orderBy('nombre')
            ->get();

        if ($numero < 1 || $numero > $servicios->count()) {
            return $this->mostrarServicios($conv);
        }

        $servicio = $servicios[$numero - 1];

        $datos = $conv->datos ?: [];
        $datos['servicio_cita_id'] = $servicio->id;
        $datos['servicio_nombre'] = $servicio->nombre;
        $conv->update(['datos' => $datos]);

        $this->whatsAppService->enviarMensaje($conv->telefono, "✅ *Servicio seleccionado: {$servicio->nombre}*");

        $conv->update(['paso' => 'pedir_foto_cedula_frente']);

        return $this->text("📷 *Envíe la foto del frente de su cédula.*\n_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_");
    }

    protected function procesarDocumento(WhatsAppConversation $conv, string $documento): array
    {
        $documento = trim($documento);

        if (empty($documento) || strlen($documento) < 4) {
            return $this->text("❌ Número de documento inválido. Por favor ingrese un número de documento válido.");
        }

        $paciente = $this->buscarPaciente($documento, $conv->telefono);

        $datos = $conv->datos ?: [];
        $datos['num_docu'] = $documento;

        if ($paciente) {
            $datos['paciente_id'] = $paciente->id;
            $datos['nombres'] = $paciente->nombres;
            $datos['pri_ape'] = $paciente->pri_ape;
            $datos['seg_ape'] = $paciente->seg_ape;
            $datos['direccion'] = $paciente->direccion ?? '';
            $datos['tel'] = $paciente->tel ?? '';
            $datos['cel'] = $paciente->cel ?? '';

            if (! $paciente->cel) {
                $paciente->update(['cel' => $conv->telefono]);
                $datos['cel'] = $conv->telefono;
            }

            $conv->update([
                'datos' => $datos,
                'paso' => 'confirmar_datos_completos',
            ]);

            $body = "📄 *Documento: {$documento}*\n";
            $body .= "👤 *{$paciente->nombres} {$paciente->pri_ape} {$paciente->seg_ape}*\n";
            if ($paciente->direccion) {
                $body .= "📍 {$paciente->direccion}\n";
            }
            if ($paciente->tel) {
                $body .= "📞 {$paciente->tel}";
            }
            $body .= self::SEPARADOR;
            $body .= "¿Son correctos sus datos?";

            return [
                'type' => 'button',
                'body' => $body,
                'header' => '✅ Datos encontrados',
                'footer' => 'Verifique antes de continuar',
                'buttons' => [
                    ['id' => '1', 'title' => 'Sí, correcto'],
                    ['id' => '2', 'title' => 'No, editar'],
                ],
            ];
        }

        $paciente = Paciente::create([
            'tipo_documento_id' => 1,
            'num_docu' => $documento,
            'nombres' => 'Pendiente',
            'cel' => $conv->telefono,
        ]);

        $datos['paciente_id'] = $paciente->id;
        $datos['nombres'] = 'Pendiente';
        $datos['pri_ape'] = '';
        $datos['seg_ape'] = '';

        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_nombres',
        ]);

        return $this->text("📄 *Documento: {$documento}*\n\nNo encontramos sus datos registrados.\nPor favor, ingrese sus *nombres completos*:");
    }

    protected function procesarNombres(WhatsAppConversation $conv, string $nombres): array
    {
        $nombres = trim($nombres);

        if (empty($nombres) || strlen($nombres) < 3) {
            return $this->text("❌ Por favor ingrese sus nombres completos válidos.");
        }

        $datos = $conv->datos ?: [];
        $datos['nombres'] = $nombres;
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_primer_apellido',
        ]);

        return $this->text("Gracias. Ahora escriba su *primer apellido*:");
    }

    protected function procesarPrimerApellido(WhatsAppConversation $conv, string $apellido): array
    {
        $apellido = trim($apellido);

        if (empty($apellido) || strlen($apellido) < 2) {
            return $this->text("❌ Por favor ingrese un apellido válido.");
        }

        $datos = $conv->datos ?: [];
        $datos['pri_ape'] = $apellido;
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_segundo_apellido',
        ]);

        return $this->text("Gracias. Ahora escriba su *segundo apellido* (o escriba *0* si no tiene):");
    }

    protected function procesarSegundoApellido(WhatsAppConversation $conv, string $apellido): array
    {
        $apellido = trim($apellido);

        $datos = $conv->datos ?: [];
        $datos['seg_ape'] = ($apellido === '0') ? '' : $apellido;

        $conv->update([
            'datos' => $datos,
            'paso' => 'confirmar_datos_paciente',
        ]);

        $body = "*Confirme sus datos:*\n\n"
              . "• Nombres: {$datos['nombres']}\n"
              . "• Primer apellido: {$datos['pri_ape']}\n"
              . "• Segundo apellido: " . ($datos['seg_ape'] ?: '—') . "\n"
              . "• Documento: {$datos['num_docu']}";

        return [
            'type' => 'button',
            'body' => $body,
            'header' => '✅ Datos del paciente',
            'footer' => 'Verifique antes de continuar',
            'buttons' => [
                ['id' => '1', 'title' => 'Sí, correcto'],
                ['id' => '2', 'title' => 'No, corregir'],
            ],
        ];
    }

    protected function procesarConfirmarDatos(WhatsAppConversation $conv, string $respuesta): array
    {
        $respuesta = trim(mb_strtolower($respuesta));

        if (in_array($respuesta, ['1', 'si', 'sí', 'correcto', 'continuar'])) {
            $datos = $conv->datos ?: [];
            $pacienteId = $datos['paciente_id'] ?? null;

            if ($pacienteId) {
                Paciente::where('id', $pacienteId)->update([
                    'nombres' => $datos['nombres'],
                    'pri_ape' => $datos['pri_ape'],
                    'seg_ape' => $datos['seg_ape'] ?? '',
                ]);
            }

            $conv->update(['paso' => 'pedir_direccion']);

            return $this->text("✅ *Nombres registrados*\n\n📍 *Escriba su dirección de residencia:*");
        }

        $datos = $conv->datos ?: [];
        unset($datos['nombres'], $datos['pri_ape'], $datos['seg_ape']);
        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_nombres',
        ]);

        return $this->text("Entendido. Vamos a empezar de nuevo.\n\nPor favor, escriba sus *nombres completos*:");
    }

    protected function procesarConfirmarDatosCompletos(WhatsAppConversation $conv, string $respuesta): array
    {
        $respuesta = trim(mb_strtolower($respuesta));

        if (in_array($respuesta, ['1', 'si', 'sí', 'correcto', 'continuar'])) {
            $datos = $conv->datos ?: [];
            $pacienteId = $datos['paciente_id'] ?? null;

            if ($pacienteId) {
                Paciente::where('id', $pacienteId)->update([
                    'direccion' => $datos['direccion'] ?? '',
                    'tel' => $datos['tel'] ?? '',
                    'cel' => $datos['cel'] ?? $conv->telefono,
                ]);
            }

            $conv->update(['paso' => 'seleccionar_sede']);

            $this->whatsAppService->enviarMensaje($conv->telefono, "✅ *Datos confirmados*");

            return $this->mostrarSedes($conv->fresh());
        }

        $datos = $conv->datos ?: [];
        unset($datos['nombres'], $datos['pri_ape'], $datos['seg_ape'], $datos['direccion'], $datos['tel'], $datos['cel']);
        $conv->update(['datos' => $datos, 'paso' => 'pedir_nombres']);

        return $this->text("Entendido. Ingrese sus *nombres completos*:");
    }

    protected function crearPacienteYContinuar(WhatsAppConversation $conv): array
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
        $datos['requiere_fotos'] = true;

        $conv->update([
            'datos' => $datos,
            'paso' => 'seleccionar_sede',
        ]);

        $respuesta = "✅ *Paciente registrado exitosamente!*";
        $this->whatsAppService->enviarMensaje($conv->telefono, $respuesta);

        return $this->mostrarSedes($conv->fresh());
    }

    protected function mostrarConfirmarDireccion(WhatsAppConversation $conv): array
    {
        $datos = $conv->datos ?: [];
        $direccion = $datos['direccion'] ?? '';

        if (empty($direccion)) {
            $conv->update(['paso' => 'editar_direccion']);
            return $this->text("📍 *Escriba su dirección de residencia:*");
        }

        return [
            'type' => 'button',
            'body' => "📍 *Dirección actual:*\n{$direccion}\n\n¿Es correcta?",
            'header' => 'Confirmar dirección',
            'footer' => 'Verifique antes de continuar',
            'buttons' => [
                ['id' => '1', 'title' => 'Sí, correcto'],
                ['id' => '2', 'title' => 'No, cambiar'],
            ],
        ];
    }

    protected function procesarConfirmarDireccion(WhatsAppConversation $conv, string $respuesta): array
    {
        $respuesta = trim(mb_strtolower($respuesta));

        if (in_array($respuesta, ['1', 'si', 'sí', 'correcto', 'continuar'])) {
            $conv->update(['paso' => 'confirmar_telefono1']);
            return $this->mostrarConfirmarTelefono1($conv);
        }

        $conv->update(['paso' => 'editar_direccion']);
        return $this->text("📍 *Escriba la nueva dirección:*");
    }

    protected function procesarEditarDireccion(WhatsAppConversation $conv, string $direccion): array
    {
        $direccion = trim($direccion);

        if (empty($direccion) || strlen($direccion) < 5) {
            return $this->text("❌ Dirección inválida. Por favor ingrese una dirección válida (mínimo 5 caracteres).");
        }

        $datos = $conv->datos ?: [];
        $datos['direccion'] = $direccion;
        $conv->update(['datos' => $datos, 'paso' => 'confirmar_telefono1']);

        return $this->mostrarConfirmarTelefono1($conv);
    }

    protected function mostrarConfirmarTelefono1(WhatsAppConversation $conv): array
    {
        $datos = $conv->datos ?: [];
        $tel = $datos['tel'] ?? '';

        if (empty($tel)) {
            $conv->update(['paso' => 'editar_telefono1']);
            return $this->text("📞 *Escriba su número de teléfono 1:*");
        }

        return [
            'type' => 'button',
            'body' => "📞 *Teléfono 1 actual:*\n{$tel}\n\n¿Es correcto?",
            'header' => 'Confirmar teléfono 1',
            'footer' => 'Verifique antes de continuar',
            'buttons' => [
                ['id' => '1', 'title' => 'Sí, correcto'],
                ['id' => '2', 'title' => 'No, cambiar'],
            ],
        ];
    }

    protected function procesarConfirmarTelefono1(WhatsAppConversation $conv, string $respuesta): array
    {
        $respuesta = trim(mb_strtolower($respuesta));

        if (in_array($respuesta, ['1', 'si', 'sí', 'correcto', 'continuar'])) {
            $conv->update(['paso' => 'confirmar_telefono2']);
            return $this->mostrarConfirmarTelefono2($conv);
        }

        $conv->update(['paso' => 'editar_telefono1']);
        return $this->text("📞 *Escriba el nuevo número de teléfono 1:*");
    }

    protected function procesarEditarTelefono1(WhatsAppConversation $conv, string $telefono): array
    {
        $telefono = trim($telefono);

        if (empty($telefono) || strlen($telefono) < 7) {
            return $this->text("❌ Número inválido. Por favor ingrese un número de teléfono válido (mínimo 7 dígitos).");
        }

        $datos = $conv->datos ?: [];
        $datos['tel'] = $telefono;
        $conv->update(['datos' => $datos, 'paso' => 'confirmar_telefono1']);

        return $this->mostrarConfirmarTelefono1($conv->fresh());
    }

    protected function mostrarConfirmarTelefono2(WhatsAppConversation $conv): array
    {
        $datos = $conv->datos ?: [];
        $cel = $datos['cel'] ?? '';

        if (empty($cel)) {
            $conv->update(['paso' => 'editar_telefono2']);
            return $this->text("📱 *Escriba su número de teléfono 2:*\n\n_(Si no tiene, escriba 0 para omitir)_");
        }

        return [
            'type' => 'button',
            'body' => "📱 *Teléfono 2 actual:*\n{$cel}\n\n¿Es correcto?",
            'header' => 'Confirmar teléfono 2',
            'footer' => 'Verifique antes de continuar',
            'buttons' => [
                ['id' => '1', 'title' => 'Sí, correcto'],
                ['id' => '2', 'title' => 'No, cambiar'],
            ],
        ];
    }

    protected function procesarConfirmarTelefono2(WhatsAppConversation $conv, string $respuesta): array
    {
        $respuesta = trim(mb_strtolower($respuesta));

        if (in_array($respuesta, ['1', 'si', 'sí', 'correcto', 'continuar'])) {
            return $this->guardarContactoYContinuar($conv);
        }

        $conv->update(['paso' => 'editar_telefono2']);
        return $this->text("📱 *Escriba el nuevo número de teléfono 2:*\n\n_(Si no tiene, escriba 0 para omitir)_");
    }

    protected function procesarEditarTelefono2(WhatsAppConversation $conv, string $telefono): array
    {
        $telefono = trim($telefono);

        $datos = $conv->datos ?: [];
        $datos['cel'] = ($telefono === '0') ? '' : $telefono;
        $conv->update(['datos' => $datos]);

        return $this->guardarContactoYContinuar($conv);
    }

    protected function guardarContactoYContinuar(WhatsAppConversation $conv): array
    {
        $datos = $conv->datos ?: [];
        $pacienteId = $datos['paciente_id'] ?? null;

        if ($pacienteId) {
            Paciente::where('id', $pacienteId)->update([
                'nombres' => $datos['nombres'] ?? 'Pendiente',
                'pri_ape' => $datos['pri_ape'] ?? '',
                'seg_ape' => $datos['seg_ape'] ?? '',
                'direccion' => $datos['direccion'] ?? '',
                'tel' => $datos['tel'] ?? '',
                'cel' => $datos['cel'] ?? $conv->telefono,
            ]);
        }

        $conv->update(['paso' => 'seleccionar_sede']);

        $this->whatsAppService->enviarMensaje($conv->telefono, "✅ *Datos registrados correctamente!*");

        return $this->mostrarSedes($conv->fresh());
    }

    protected function procesarDireccion(WhatsAppConversation $conv, string $direccion): array
    {
        $direccion = trim($direccion);

        if (empty($direccion) || strlen($direccion) < 5) {
            return $this->text("❌ Dirección inválida. Por favor ingrese una dirección válida (mínimo 5 caracteres).");
        }

        $datos = $conv->datos ?: [];
        $datos['direccion'] = $direccion;
        $conv->update(['datos' => $datos, 'paso' => 'pedir_telefono1']);

        return $this->text("✅ *Dirección registrada*\n\n📞 *Escriba su número de teléfono 1:*");
    }

    protected function procesarTelefono1(WhatsAppConversation $conv, string $telefono): array
    {
        $telefono = trim($telefono);

        if (empty($telefono) || strlen($telefono) < 7) {
            return $this->text("❌ Número inválido. Por favor ingrese un número de teléfono válido (mínimo 7 dígitos).");
        }

        $datos = $conv->datos ?: [];
        $datos['tel'] = $telefono;
        $conv->update(['datos' => $datos, 'paso' => 'pedir_telefono2']);

        return $this->text("✅ *Teléfono 1 registrado*\n\n📱 *Escriba su número de teléfono 2* (o *0* si no tiene):");
    }

    protected function procesarTelefono2(WhatsAppConversation $conv, string $telefono): array
    {
        $telefono = trim($telefono);

        $datos = $conv->datos ?: [];
        $datos['cel'] = ($telefono === '0') ? '' : $telefono;
        $conv->update(['datos' => $datos]);

        return $this->guardarContactoYContinuar($conv);
    }

    protected function procesarFotoCedulaFrente(
        WhatsAppConversation $conv,
        string $mensaje,
        string $tipoMensaje = 'text',
        ?string $mediaPath = null
    ): array {
        if ($tipoMensaje === 'text' || ! $mediaPath) {
            return $this->recordatorioArchivo('pedir_foto_cedula_frente');
        }

        $error = $this->validarMedia($mediaPath, "📷 *Envíe la foto del frente de su cédula.*");
        if ($error) {
            return $error;
        }

        $datos = $conv->datos ?: [];
        $datos['ruta_cedula_frente_media'] = $mediaPath;

        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_foto_cedula_dorso',
        ]);

        $response = "✅ *Foto del frente registrada*";
        $response .= "\n📎 Archivo: " . basename($mediaPath);
        $response .= self::SEPARADOR;
        $response .= "📷 *Envíe la foto del reverso de su cédula.*\n";
        $response .= "_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_";

        return $this->text($response);
    }

    protected function procesarFotoCedulaDorso(
        WhatsAppConversation $conv,
        string $mensaje,
        string $tipoMensaje = 'text',
        ?string $mediaPath = null
    ): array {
        if ($tipoMensaje === 'text' || ! $mediaPath) {
            return $this->recordatorioArchivo('pedir_foto_cedula_dorso');
        }

        $error = $this->validarMedia($mediaPath, "📷 *Envíe la foto del reverso de su cédula.*");
        if ($error) {
            return $error;
        }

        $datos = $conv->datos ?: [];
        $datos['ruta_cedula_dorso_media'] = $mediaPath;

        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_orden',
        ]);

        $response = "✅ *Foto del reverso registrada*";
        $response .= "\n📎 Archivo: " . basename($mediaPath);
        $response .= self::SEPARADOR;
        $response .= "📋 *Envíe la orden médica.*\n";
        $response .= "Adjunte la imagen o PDF de la orden.\n";
        $response .= "_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_";

        return $this->text($response);
    }

    protected function procesarOrden(
        WhatsAppConversation $conv,
        string $mensaje,
        string $tipoMensaje = 'text',
        ?string $mediaPath = null
    ): array {
        if ($tipoMensaje === 'text' || ! $mediaPath) {
            return $this->recordatorioArchivo('pedir_orden');
        }

        $error = $this->validarMedia($mediaPath, "📋 *Envíe la orden médica.*");
        if ($error) {
            return $error;
        }

        $datos = $conv->datos ?: [];
        $datos['ruta_orden_media'] = $mediaPath;
        unset($datos['numero_orden']);

        $conv->update([
            'datos' => $datos,
            'paso' => 'pedir_autorizacion',
        ]);

        $response = "✅ *Orden registrada*";
        $response .= "\n📎 Archivo: " . basename($mediaPath);
        $response .= self::SEPARADOR;
        $response .= "📋 *Envíe la autorización médica.*\n";
        $response .= "Adjunte la imagen o PDF de la autorización.\n";
        $response .= "_(Solo se aceptan archivos JPG, JPEG, PNG o PDF)_";

        return $this->text($response);
    }

    protected function procesarAutorizacion(
        WhatsAppConversation $conv,
        string $mensaje,
        string $tipoMensaje = 'text',
        ?string $mediaPath = null
    ): array {
        if ($tipoMensaje === 'text' || ! $mediaPath) {
            return $this->recordatorioArchivo('pedir_autorizacion');
        }

        $error = $this->validarMedia($mediaPath, "📋 *Envíe la autorización médica.*");
        if ($error) {
            return $error;
        }

        $datos = $conv->datos ?: [];
        $datos['ruta_autorizacion_media'] = $mediaPath;
        unset($datos['numero_autorizacion']);

        $conv->update([
            'datos' => $datos,
        ]);

        $pacienteId = $datos['paciente_id'] ?? null;
        $paciente = $pacienteId ? Paciente::find($pacienteId) : null;

        if (! $paciente) {
            $this->resetearConversacion($conv);
            return $this->text("❌ Error al procesar el paciente. Por favor contacte a soporte.");
        }

        if ($conv->cita_id) {
            $cita = AgendaCita::find($conv->cita_id);
            if ($cita) {
                $conv->update(['paso' => 'confirmar_solicitud']);
                return $this->text("✅ Su solicitud ya fue registrada. En 24 horas será notificado.");
            }
        }

        $cita = AgendaCita::create([
            'paciente_id' => $paciente->id,
            'servicio_cita_id' => $datos['servicio_cita_id'],
            'sede_id' => $conv->sede_id,
            'quien_registro_id' => null,
            'quien_confirma_id' => null,
            'numero_orden' => null,
            'numero_autorizacion' => null,
            'ruta_orden_media' => $datos['ruta_orden_media'] ?? null,
            'ruta_cedula_frente_media' => $datos['ruta_cedula_frente_media'] ?? null,
            'ruta_cedula_dorso_media' => $datos['ruta_cedula_dorso_media'] ?? null,
            'ruta_autorizacion_media' => $datos['ruta_autorizacion_media'] ?? null,
            'estado' => 'solicitada',
            'whatsapp_token' => bin2hex(random_bytes(16)),
        ]);

        $conv->update([
            'cita_id' => $cita->id,
            'paso' => 'confirmar_solicitud',
        ]);

        try {
            $users = User::permission('citas.index')->get();
            $nombrePacienteNotif = trim(($datos['nombres'] ?? '') . ' ' . ($datos['pri_ape'] ?? ''));
            Notification::send($users, new CitaSolicitadaNotification(
                citaId: $cita->id,
                pacienteNombre: $nombrePacienteNotif ?: 'Paciente',
                servicio: $datos['servicio_nombre'] ?? null,
                sede: $conv->sede_id ? (ConfigSede::find($conv->sede_id)?->nom_sede) : null,
            ));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error al notificar nueva cita: ' . $e->getMessage());
        }

        $servicio = $datos['servicio_nombre'] ?? 'Estudio';
        $nombrePaciente = $datos['nombres'] . ' ' . ($datos['pri_ape'] ?? '') . ' ' . ($datos['seg_ape'] ?? '');
        $sede = $conv->sede_id ? ConfigSede::find($conv->sede_id) : null;
        $sedeNombre = $sede?->nom_sede ?? '—';

        $mensajeR = "✅ *Solicitud de Cita Confirmada*";
        $mensajeR .= self::SEPARADOR;
        $mensajeR .= "📋 *Detalles:*\n";
        $mensajeR .= "• Servicio: {$servicio}\n";
        $mensajeR .= "• Sede: {$sedeNombre}\n";
        $mensajeR .= "• Paciente: {$nombrePaciente}\n";
        $mensajeR .= "• Documento: {$datos['num_docu']}\n";

        if (! empty($datos['ruta_cedula_frente_media'])) {
            $mensajeR .= "• 📎 Cédula frente: " . basename($datos['ruta_cedula_frente_media']) . "\n";
        }
        if (! empty($datos['ruta_cedula_dorso_media'])) {
            $mensajeR .= "• 📎 Cédula reverso: " . basename($datos['ruta_cedula_dorso_media']) . "\n";
        }
        if (! empty($datos['ruta_orden_media'])) {
            $mensajeR .= "• 📎 Orden: " . basename($datos['ruta_orden_media']) . "\n";
        }
        if (! empty($datos['ruta_autorizacion_media'])) {
            $mensajeR .= "• 📎 Autorización: " . basename($datos['ruta_autorizacion_media']) . "\n";
        }

        $mensajeR .= self::SEPARADOR;
        $mensajeR .= "⏰ Su solicitud ha sido registrada.\n";
        $mensajeR .= "En 24 horas será notificado con la fecha y hora de su cita.\n\n";
        $mensajeR .= "_Gracias por confiar en nosotros._";

        return $this->text($mensajeR);
    }

    protected function buscarPaciente(string $documento, ?string $telefono = null): ?Paciente
    {
        try {
            $sahiPaciente = SahiPaciente::where('PA_NUMDOCU', $documento)->first();

            if ($sahiPaciente) {
                return Paciente::updateOrCreate(
                    ['num_docu' => $sahiPaciente->PA_NUMDOCU],
                    [
                        'tipo_documento_id' => 1,
                        'nombres' => $sahiPaciente->PA_NOMB1.' '.$sahiPaciente->PA_NOMB2,
                        'pri_ape' => $sahiPaciente->PA_APELL1,
                        'seg_ape' => $sahiPaciente->PA_APELL2,
                        'sexo' => $sahiPaciente->PA_SEXO,
                        'fec_naci' => $sahiPaciente->PA_FENAC,
                        'direccion' => $sahiPaciente->PA_DIRECC,
                        'tel' => $sahiPaciente->PA_TELEFO,
                        'cel' => $telefono ?? '',
                    ]
                );
            }
        } catch (\Throwable $e) {
            // SAHI no disponible — continuar con búsqueda local
        }

        return Paciente::where('num_docu', $documento)->first();
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
            'sede_id' => null,
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
