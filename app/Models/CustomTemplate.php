<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTemplate extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'type',
        'content',
        'variables',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
