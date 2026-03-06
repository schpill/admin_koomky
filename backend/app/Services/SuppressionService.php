<?php

namespace App\Services;

use App\Models\SuppressedEmail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class SuppressionService
{
    public function suppress(User $user, string $email, string $reason, ?string $sourceCampaignId = null): void
    {
        $normalizedEmail = mb_strtolower(trim($email));
        if ($normalizedEmail === '') {
            return;
        }

        SuppressedEmail::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'email' => $normalizedEmail,
            ],
            [
                'reason' => $reason,
                'source_campaign_id' => $sourceCampaignId,
                'suppressed_at' => now(),
            ]
        );
    }

    public function isSuppressed(User $user, string $email): bool
    {
        return SuppressedEmail::query()
            ->forUser($user)
            ->where('email', mb_strtolower(trim($email)))
            ->exists();
    }

    /**
     * @return Collection<int, SuppressedEmail>
     */
    public function getSuppressedEmails(User $user): Collection
    {
        return SuppressedEmail::query()
            ->forUser($user)
            ->orderBy('email')
            ->get();
    }

    /**
     * @return array{imported:int, skipped:int}
     */
    public function importCsv(User $user, string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $imported = 0;
        $skipped = 0;
        $isHeader = true;

        while (($row = fgetcsv($handle)) !== false) {
            if ($isHeader) {
                $isHeader = false;

                continue;
            }

            $email = isset($row[0]) ? mb_strtolower(trim((string) $row[0])) : '';
            if ($email === '') {
                continue;
            }

            $exists = SuppressedEmail::query()
                ->forUser($user)
                ->where('email', $email)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $this->suppress($user, $email, 'manual');
            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function exportCsv(User $user): Response
    {
        $rows = $this->getSuppressedEmails($user);

        $output = fopen('php://temp', 'w+');
        if ($output === false) {
            return response('', 500);
        }

        fputcsv($output, ['email', 'reason', 'suppressed_at']);
        foreach ($rows as $row) {
            $suppressedAt = Carbon::parse((string) $row->suppressed_at);

            fputcsv($output, [
                $row->email,
                $row->reason,
                $suppressedAt->toIso8601String(),
            ]);
        }

        rewind($output);
        $content = stream_get_contents($output) ?: '';
        fclose($output);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="suppression-list.csv"',
        ]);
    }
}
