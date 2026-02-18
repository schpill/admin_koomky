<?php

namespace App\Services;

use App\Models\Client;
use App\Models\PortalAccessToken;
use App\Models\PortalActivityLog;
use Illuminate\Http\Request;

class PortalActivityLogger
{
    public function log(
        Request $request,
        Client $client,
        string $action,
        ?PortalAccessToken $portalAccessToken = null,
        ?string $entityType = null,
        ?string $entityId = null,
    ): PortalActivityLog {
        return PortalActivityLog::query()->create([
            'client_id' => $client->id,
            'portal_access_token_id' => $portalAccessToken?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
