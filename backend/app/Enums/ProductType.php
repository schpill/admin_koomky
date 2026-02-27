<?php

namespace App\Enums;

enum ProductType: string
{
    case Service = 'service';
    case Training = 'training';
    case Product = 'product';
    case Subscription = 'subscription';
}
