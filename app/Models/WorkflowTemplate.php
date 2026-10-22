<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkflowTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'icon',
        'nodes',
        'connections',
        'is_public',
        'is_active',
        'usage_count',
    ];

    protected $casts = [
        'nodes' => 'array',
        'connections' => 'array',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function incrementUsage()
    {
        $this->increment('usage_count');
    }
}
