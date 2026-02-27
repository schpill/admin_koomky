<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductDurationUnit: string
{
    case Hours = 'hours';
    case Days = 'days';
    case Weeks = 'weeks';
    case Months = 'months';
}
