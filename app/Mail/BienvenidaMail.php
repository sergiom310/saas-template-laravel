<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BienvenidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreEmpresa;
    public $descripcionEmpresa;
    public $name;
    public $frontUrl;

    public function __construct($nombreEmpresa, $descripcionEmpresa, $name, $frontUrl)
    {
        $this->nombreEmpresa = $nombreEmpresa;
        $this->descripcionEmpresa = $descripcionEmpresa;
        $this->name = $name;
        $this->frontUrl = $frontUrl;
    }

    public function build()
    {
        return $this->subject("🎉 ¡Bienvenido!")
            ->view('emails.bienvenida');
    }
}
