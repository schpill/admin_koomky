<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'client_id',
        'reference',
        'status',
        'total',
    ];
}
