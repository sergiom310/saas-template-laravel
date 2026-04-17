<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $name_company;
    public $frontUrl;

    public function __construct($name, $name_company, $frontUrl)
    {
        $this->name = $name;
        $this->name_company = $name_company;
        $this->frontUrl = $frontUrl;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), 'Agendas - Grupoados.com')
            ->subject('Registro exitoso')
            ->view('emails.registration-success');
    }
}
