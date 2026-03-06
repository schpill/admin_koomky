<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $renderedSubject,
        public string $renderedBody
    ) {}

    public function build(): self
    {
        return $this->subject($this->renderedSubject !== '' ? $this->renderedSubject : 'Campaign test')
            ->html($this->renderedBody);
    }
}
