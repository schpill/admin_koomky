<?php

namespace App\Services\Sms;

interface SmsService
{
    /**
     * @return array{message_id:string, provider:string}
     */
    public function send(string $to, string $message): array;

    /**
     * @return array<string, mixed>
     */
    public function getDeliveryStatus(string $messageId): array;
}
