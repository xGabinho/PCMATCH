<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CotizacionEnviadaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cotizacion;
    public $pdfContent;
    public $user;

    /**
     * Create a new message instance.
     *
     * @param object|array $cotizacion
     * @param string $pdfContent
     * @param object $user
     */
    public function __construct($cotizacion, $pdfContent, $user)
    {
        $this->cotizacion = $cotizacion;
        $this->pdfContent = $pdfContent;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Tu Cotización en PCMATCH - ' . $this->cotizacion->codigo)
                    ->view('emails.cotizacion')
                    ->attachData($this->pdfContent, 'Cotizacion-' . $this->cotizacion->codigo . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}
