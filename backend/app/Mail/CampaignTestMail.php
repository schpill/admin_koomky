<?php

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CampaignTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Campaign $campaign) {}

    public function build(): self
    {
        return $this->subject((string) ($this->campaign->subject ?? 'Campaign test'))
            ->html((string) $this->campaign->content);
    }
}
