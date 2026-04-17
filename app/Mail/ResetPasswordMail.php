<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $resetUrl;
    public $nombreEmpresa;
    public $descripcionEmpresa;

    public function __construct($user, $resetUrl, $nombreEmpresa, $descripcionEmpresa)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
        $this->nombreEmpresa = $nombreEmpresa;
        $this->descripcionEmpresa = $descripcionEmpresa;
    }

    public function build()
    {
        return $this->subject('Restablece tu contraseña en ' . $this->nombreEmpresa)
                    ->from(config('mail.from.address'), $this->nombreEmpresa)
                    ->view('emails.reset-password');
    }
}
