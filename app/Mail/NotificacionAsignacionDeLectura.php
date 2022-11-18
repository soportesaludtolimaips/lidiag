<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacionAsignacionDeLectura extends Mailable
{
    use Queueable, SerializesModels;
    protected $estudio, $paciente;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($paciente, $estudio)
    {
        $this->paciente = $paciente;
        $this->estudio = $estudio;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Notificacion Asignacion De Lectura',
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
            with: ['paciente' => $this->paciente, 'estudio' => $this->estudio]
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
