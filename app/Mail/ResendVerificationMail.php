<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResendVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $nombreEmpresa;
    public $descripcionEmpresa;
    public $verificationUrl;
    public $activationExpireText;

    public function __construct($user, $nombreEmpresa, $descripcionEmpresa, $verificationUrl, $activationExpireText)
    {
        $this->user = $user;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->descripcionEmpresa = $descripcionEmpresa;
        $this->verificationUrl = $verificationUrl;
        $this->activationExpireText = $activationExpireText;
    }

    public function build()
    {
        return $this->from(config('mail.from.address'), $this->nombreEmpresa)
            ->subject('Verifica tu cuenta en ' . $this->nombreEmpresa)
            ->view('emails.verify-account');
    }
}
