<?php

namespace Modules\Email\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\Models\Tenant;

class RentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Tenant $tenant;

    public array $placeholders;

    public string $subjectLine;

    public string $bodyContent;

    public function __construct(Tenant $tenant, string $subject, string $body, array $placeholdersData = [])
    {
        $this->tenant = $tenant;
        $this->subjectLine = $subject;
        $this->bodyContent = $body;
        $this->placeholders = $placeholdersData;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        $parsedBody = $this->parsePlaceholders($this->bodyContent);

        return new Content(
            htmlString: $parsedBody,
        );
    }

    protected function parsePlaceholders(string $text): string
    {
        $replacements = [
            '{tenant_name}' => e($this->tenant->name),
            '{tenant_email}' => e($this->tenant->email ?? ''),
            '{pg_name}' => e($this->tenant->pg?->pg_name ?? ''),
            '{room_no}' => e($this->tenant->room?->room_no ?? ''),
            '{checkin_date}' => e($this->tenant->checkin_date?->format('d-m-Y') ?? ''),
            '{monthly_rent}' => e(number_format((float) ($this->tenant->monthly_rent ?? 0), 2)),
        ];

        $replacements = array_merge($replacements, $this->placeholders);

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
