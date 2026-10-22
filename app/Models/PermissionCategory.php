<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Permission;

class PermissionCategory extends Model
{
    use HasAgency;
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_system' => 'boolean',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'category_id');
    }
}
