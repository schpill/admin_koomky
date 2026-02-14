<?php

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;

class LogAuthEvent
{
    public function __construct(protected Request $request)
    {
    }

    public function handle(object $event): void
    {
        $eventName = match (get_class($event)) {
            Login::class => 'auth.login',
            Logout::class => 'auth.logout',
            Registered::class => 'auth.register',
            default => 'auth.unknown',
        };

        AuditLog::create([
            'user_id' => $event->user?->id,
            'event' => $eventName,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => [
                'guard' => property_exists($event, 'guard') ? $event->guard : null,
            ],
        ]);
    }
}
