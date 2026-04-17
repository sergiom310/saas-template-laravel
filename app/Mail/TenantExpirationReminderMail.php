<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Tenant\CustomTenantModel;

class TenantExpirationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenant;
    public $diasRestantes;
    public $frontUrl;

    public function __construct(CustomTenantModel $tenant, int $diasRestantes, string $frontUrl)
    {
        $this->tenant = $tenant;
        $this->diasRestantes = $diasRestantes;
        $this->frontUrl = $frontUrl;
    }

    public function envelope(): Envelope
    {
        $subject = 'Recordatorio: Tu suscripción expirará pronto - ' . $this->tenant->name_company;
        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-expiration-reminder',
            with: [
                'tenant' => $this->tenant,
                'diasRestantes' => $this->diasRestantes,
                'modulos' => $this->tenant->modulos,
                'frontUrl' => $this->frontUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
