<?php

namespace App\Services;

class PhoneValidationService
{
    public function isValidE164(string $phone): bool
    {
        return preg_match('/^\+[1-9]\d{7,14}$/', $phone) === 1;
    }
}
