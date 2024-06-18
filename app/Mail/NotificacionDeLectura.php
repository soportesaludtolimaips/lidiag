<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionDeLectura extends Mailable
{
    use Queueable, SerializesModels;
    protected $estudio, $nomArchivoReporte;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($estudio, $nomArchivoReporte)
    {
        $this->estudio = $estudio;
        $this->nomArchivoReporte = $nomArchivoReporte;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {

        return new Envelope(
            subject: 'Notificación: Reporte de lectura de imagen diagnostica disponible',
            metadata: [
                'num_docu' => $this->estudio->num_docu,
                'nombres' => $this->estudio->nom_pacien,
                'fec_estudio' => $this->estudio->fec_estudio,
            ],
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'modulos.notificaciones-email.notificacion-lectura',
            with: ['estudio' => $this->estudio]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [public_path('reporte_lecturas/' . $this->nomArchivoReporte)];
    }
}
