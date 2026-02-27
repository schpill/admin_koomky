<?php

namespace App\Enums;

enum ProductPriceType: string
{
    case Fixed = 'fixed';
    case Hourly = 'hourly';
    case Daily = 'daily';
    case PerUnit = 'per_unit';
}
