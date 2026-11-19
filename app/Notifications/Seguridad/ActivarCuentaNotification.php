<?php

namespace App\Notifications\Seguridad;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivarCuentaNotification extends Notification
{
    use Queueable;

    /**
     * Inicializa la notificación de activación de cuenta.
     *
     * Flujo del proceso:
     * 1. Almacena el token seguro generado para la activación.
     *
     * @param  string  $token  Token criptográfico temporal de activación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    public function __construct(
        public readonly string $token,
    ) {}

    /**
     * Define los canales de entrega de la notificación.
     *
     * @param  object  $notifiable  Usuario destinatario.
     * @return array<int, string> Canales de notificación.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    public function via(object $notifiable): array
    {
        return ["mail"];
    }

    /**
     * Construye el mensaje de correo electrónico para la activación.
     *
     * Flujo del proceso:
     * 1. Construye la URL segura de activación con token y correo del usuario.
     * 2. Configura asunto, saludo, cuerpo, botón de acción y advertencia de expiración.
     *
     * @param  object  $notifiable  Usuario destinatario.
     * @return MailMessage Mensaje de correo estructurado.
     *
     * @author Jhon Javer Lozano Arce
     *
     * @date 2026-11-19
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route("usuario.activar", [
            "token" => $this->token,
            "email" => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject("Activación de Cuenta — ".config("app.name", "LIDiag"))
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Se ha creado una cuenta para ti en el sistema de información radiológica LIDiag.")
            ->line("Para activar tu cuenta y establecer tu contraseña definitiva de acceso, haz clic en el siguiente botón:")
            ->action("Activar mi Cuenta", $url)
            ->line("Por motivos de seguridad, este enlace de activación expirará en 60 minutos.")
            ->line("Si tú no solicitaste esta cuenta o consideras que se trata de un error, puedes ignorar este mensaje de forma segura.")
            ->salutation("Atentamente,\nEl equipo de ".config("app.name", "LIDiag"));
    }
}
