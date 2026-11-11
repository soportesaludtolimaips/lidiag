<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Aviso al paciente de que su resultado está disponible en el portal.
 *
 * Lleva SOLO el enlace: sin informe ni imágenes adjuntas (son datos de salud y el correo no es
 * un canal seguro) y sin usuario ni contraseña.
 */
class EnlaceResultadoPaciente extends Mailable
{
    public function __construct(
        public readonly ?string $nombre,
        public readonly ?string $sede,
        public readonly string $url,
        public readonly int $diasVigencia,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Su resultado de imágenes diagnósticas está disponible');
    }

    public function content(): Content
    {
        return new Content(view: 'modulos.notificaciones-email.enlace-resultado');
    }
}
