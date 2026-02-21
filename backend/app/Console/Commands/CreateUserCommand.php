<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    protected $signature = 'users:create {email? : Email address of the new user}';

    protected $description = 'Create a new CRM user with a generated strong password';

    public function handle(): int
    {
        $email = $this->argument('email');

        if (! is_string($email) || trim($email) === '') {
            $email = (string) $this->ask('Email address');
        }

        $email = trim($email);

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email']]
        );

        if ($validator->fails()) {
            $this->error((string) $validator->errors()->first('email'));

            return self::FAILURE;
        }

        $password = $this->generateStrongPassword();

        /** @var User|null $existing */
        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            $existing->update(['password' => $password]);

            $this->warn('A user with this email already exists. Their password has been reset.');
            $this->line('Email: '.$existing->email);
            $this->line('New password: '.$password);
            $this->warn('Store this password now. It will not be shown again.');

            return self::SUCCESS;
        }

        $user = User::query()->create([
            'name' => $this->resolveNameFromEmail($email),
            'email' => $email,
            'password' => $password,
        ]);

        $this->info('User created successfully.');
        $this->line('Email: '.$user->email);
        $this->line('Password: '.$password);
        $this->warn('Store this password now. It will not be shown again.');

        return self::SUCCESS;
    }

    private function resolveNameFromEmail(string $email): string
    {
        $localPart = Str::before($email, '@');

        $candidate = (string) Str::of($localPart)
            ->replaceMatches('/[^A-Za-z0-9]+/', ' ')
            ->trim()
            ->title();

        return $candidate !== '' ? $candidate : 'User';
    }

    private function generateStrongPassword(int $length = 16): string
    {
        $length = max(8, $length);

        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()-_=+[]{}:;,.?/|~';
        $all = $lowercase.$uppercase.$numbers.$symbols;

        $characters = [
            $lowercase[random_int(0, strlen($lowercase) - 1)],
            $uppercase[random_int(0, strlen($uppercase) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $countCharacters = count($characters);

        for ($i = $countCharacters; $i < $length; $i++) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = $countCharacters - 1; $i > 0; $i--) {
            $swapIndex = random_int(0, $i);
            [$characters[$i], $characters[$swapIndex]] = [$characters[$swapIndex], $characters[$i]];
        }

        return implode('', $characters);
    }
}
