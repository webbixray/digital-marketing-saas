<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialComment extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'social_post_id',
        'social_account_id',
        'platform',
        'platform_comment_id',
        'author_name',
        'author_id',
        'content',
        'parent_id',
        'is_replied',
        'replied_at',
    ];

    protected $casts = [
        'is_replied' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function socialPost()
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
