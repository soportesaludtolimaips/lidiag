<?php

namespace App\Notifications\Seguridad;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RestablecerPasswordEmailNotification extends Notification
{
    use Queueable;

    /**
     * Inicializa la notificación de restablecimiento de contraseña.
     *
     * Flujo del proceso:
     * 1. Almacena el token seguro generado para el restablecimiento.
     *
     * @param  string  $token  Token criptográfico temporal de restablecimiento.
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
     * Construye el mensaje de correo electrónico para el restablecimiento de contraseña.
     *
     * Flujo del proceso:
     * 1. Construye la URL segura de restablecimiento con token y correo del usuario.
     * 2. Configura asunto, saludo, cuerpo, botón de acción y advertencia de seguridad.
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
        $url = route("password.reset", [
            "token" => $this->token,
            "email" => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject("Restablecimiento de Contraseña — ".config("app.name", "LIDiag"))
            ->greeting("¡Hola, {$notifiable->name}!")
            ->line("Se ha solicitado un enlace para restablecer la contraseña de acceso a tu cuenta en LIDiag.")
            ->line("Para definir una nueva contraseña, haz clic en el siguiente botón:")
            ->action("Restablecer mi Contraseña", $url)
            ->line("Este enlace es de uso único y expirará en 60 minutos.")
            ->line("Si tú no solicitaste este cambio, te recomendamos comunicarte de inmediato con el administrador del sistema.")
            ->salutation("Atentamente,\nEl equipo de ".config("app.name", "LIDiag"));
    }
}
