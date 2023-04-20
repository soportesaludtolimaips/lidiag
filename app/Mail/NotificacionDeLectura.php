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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($paciente, $estudio, $sucursalEstudio, $prioridadEstudio)
    {
        $this->paciente = $paciente;
        $this->estudio = $estudio;
        $this->sucursalEstudio = $sucursalEstudio;
        $this->prioridadEstudio = $prioridadEstudio;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Notificacion De Lectura',
            metadata: [
                'paciente_id' => $this->paciente->id,
                'num_docu' => $this->paciente->num_docu,
                'nombres' => $this->paciente->nombres,
                'study_id' => $this->estudio->id,
                'fec_estudio' => $this->estudio->fec_estudio,
                'accession_no' => $this->estudio->accession_no,
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
            view: 'notificaciones-email.notificacion-aignacion-lectura',
            with: ['paciente' => $this->paciente, 'estudio' => $this->estudio, 'sucursalEstudio' => $this->sucursalEstudio, 'prioridadEstudio' => $this->prioridadEstudio]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
