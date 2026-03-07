<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportProspectsCommand extends Command
{
    protected $signature = 'leads:import-xlsx
                            {--user= : Email of the user to assign leads to (defaults to first user)}
                            {--dry-run : Show what would be imported without writing to database}';

    protected $description = 'Import prospect XLSX files from data/prospects/ into leads';

    private const PROSPECTS_DIR = '/var/www/data/prospects';

    private const IMPORTED_DIR = '/var/www/data/prospects/imported';

    private const MAX_EMAILS = 10;

    public function handle(): int
    {
        $user = $this->resolveUser();
        if ($user === null) {
            return self::FAILURE;
        }

        $files = glob(self::PROSPECTS_DIR.'/*.xlsx');
        if ($files === false || count($files) === 0) {
            $this->warn('No XLSX files found in '.self::PROSPECTS_DIR);

            return self::SUCCESS;
        }

        if (! is_dir(self::IMPORTED_DIR)) {
            mkdir(self::IMPORTED_DIR, 0755, true);
        }

        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('[DRY RUN] No data will be written.');
        }

        $totalImported = 0;
        $totalSkipped = 0;

        foreach ($files as $file) {
            $this->info('Processing: '.basename($file));

            $tags = $this->extractTagsFromFilename($file);
            $this->line('  Tags: '.implode(', ', $tags));

            [$imported, $skipped] = $this->processFile($file, $user, $tags, $isDryRun);
            $totalImported += $imported;
            $totalSkipped += $skipped;

            if (! $isDryRun) {
                $destination = self::IMPORTED_DIR.'/'.basename($file);
                rename($file, $destination);
                $this->line('  → Moved to imported/'.basename($file));
            }
        }

        $this->newLine();
        $this->info("Done. Imported: {$totalImported} | Skipped (duplicates): {$totalSkipped}");

        return self::SUCCESS;
    }

    /**
     * Extract tags from the filename.
     * Convention: {job_type}_{department}.xlsx
     * Example: wedding_planner_oise → ['wedding_planner', 'oise']
     *
     * @return string[]
     */
    private function extractTagsFromFilename(string $file): array
    {
        $basename = pathinfo($file, PATHINFO_FILENAME);
        $parts = explode('_', $basename);

        if (count($parts) === 1) {
            return [$parts[0]];
        }

        $department = array_pop($parts);
        $job = implode('_', $parts);

        return [$job, $department];
    }

    /**
     * @param  string[]  $tags
     * @return array{int, int}
     */
    private function processFile(string $file, User $user, array $tags, bool $isDryRun): array
    {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $this->extractHeaders($sheet);
        $rows = $sheet->toArray();

        $imported = 0;
        $skipped = 0;

        foreach (array_slice($rows, 1) as $rowIndex => $row) {
            $data = $this->mapRow($headers, $row, $user, $tags);

            if ($data === null) {
                $skipped++;

                continue;
            }

            if ($this->isDuplicate($user->id, $data)) {
                $this->line('  [skip] '.($data['company_name'] ?? 'row '.($rowIndex + 2)).' (already exists)');
                $skipped++;

                continue;
            }

            if (! $isDryRun) {
                Lead::query()->create($data);
            } else {
                $emails = array_filter(array_map(
                    fn (int $n) => $data["email_{$n}"] ?? ($n === 1 ? ($data['email'] ?? null) : null),
                    range(1, self::MAX_EMAILS)
                ));
                $this->line('  [dry] '.($data['company_name'] ?? '?').' — '.implode(', ', $emails ?: ['no email']));
            }

            $imported++;
        }

        return [$imported, $skipped];
    }

    /**
     * @return array<string, int>
     */
    private function extractHeaders(Worksheet $sheet): array
    {
        $headers = [];
        $firstRow = $sheet->toArray()[0] ?? [];
        foreach ($firstRow as $index => $value) {
            if (is_string($value) && $value !== '') {
                $headers[trim($value)] = $index;
            }
        }

        return $headers;
    }

    /**
     * @param  array<string, int>  $headers
     * @param  array<int, mixed>  $row
     * @param  string[]  $tags
     * @return array<string, mixed>|null
     */
    private function mapRow(array $headers, array $row, User $user, array $tags): ?array
    {
        $get = function (string $col) use ($headers, $row): string {
            $index = $headers[$col] ?? null;

            return ($index !== null && isset($row[$index]) && $row[$index] !== null)
                ? trim((string) $row[$index])
                : '';
        };

        $companyName = $get('name');
        if ($companyName === '') {
            return null;
        }

        $emails = $this->extractEmails($get('emails'), self::MAX_EMAILS);
        $phone = $this->normalizePhone($get('phone') ?: $get('phone_formatted'));

        $notesParts = array_filter([
            $get('type') !== '' ? 'Type: '.$get('type') : '',
            $get('full_address') !== '' ? 'Adresse: '.$get('full_address') : '',
            $get('website') !== '' ? 'Site web: '.$get('website') : '',
            $get('rate') !== '' ? 'Note: '.$get('rate').($get('reviews') !== '' ? ' ('.$get('reviews').' avis)' : '') : '',
            $get('description') !== '' ? $get('description') : '',
        ]);

        $data = [
            'user_id' => $user->id,
            'company_name' => mb_substr($companyName, 0, 255),
            'first_name' => '-',
            'last_name' => '-',
            'email' => $emails[0] ?? null,
            'phone' => $phone !== '' ? mb_substr($phone, 0, 50) : null,
            'source' => 'other',
            'status' => 'new',
            'notes' => count($notesParts) > 0 ? implode("\n", $notesParts) : null,
            'tags' => $tags,
            'pipeline_position' => 0,
        ];

        for ($i = 2; $i <= self::MAX_EMAILS; $i++) {
            $data["email_{$i}"] = isset($emails[$i - 1]) ? mb_substr($emails[$i - 1], 0, 255) : null;
        }

        return $data;
    }

    /**
     * Extract up to $max valid email addresses from a raw string.
     *
     * @return string[]
     */
    private function extractEmails(string $raw, int $max): array
    {
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s,;]+/', $raw) ?: [];
        $emails = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            if (filter_var($part, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }
            $emails[] = $part;
            if (count($emails) >= $max) {
                break;
            }
        }

        return $emails;
    }

    private function normalizePhone(string $raw): string
    {
        $clean = preg_replace('/[^\d+]/', '', $raw) ?? '';

        return $clean !== '' ? $raw : '';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function isDuplicate(string $userId, array $data): bool
    {
        $query = Lead::query()->where('user_id', $userId);

        if (isset($data['email']) && is_string($data['email']) && $data['email'] !== '') {
            if ($query->clone()->where('email', $data['email'])->exists()) {
                return true;
            }
        }

        return $query->where('company_name', $data['company_name'])->exists();
    }

    private function resolveUser(): ?User
    {
        $email = $this->option('user');

        if (is_string($email) && $email !== '') {
            /** @var User|null $user */
            $user = User::query()->where('email', $email)->first();
            if ($user === null) {
                $this->error("User not found: {$email}");

                return null;
            }

            return $user;
        }

        /** @var User|null $user */
        $user = User::query()->orderBy('created_at')->first();
        if ($user === null) {
            $this->error('No users found in the database. Create a user first with: php artisan users:create');

            return null;
        }

        $this->line("Assigning leads to: {$user->email}");

        return $user;
    }
}
