<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'description',
        'type',
        'created_by',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_channel_user')
            ->withPivot('last_read_at', 'is_moderator')
            ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    public function scopeForUser($query, User $user)
    {
        return $query->whereHas('users', fn($q) => $q->where('user_id', $user->id));
    }

    public function getLastMessageAttribute()
    {
        return $this->messages()->latest()->first();
    }

    public function getUnreadCountAttribute()
    {
        $lastRead = $this->users()->where('user_id', auth()->id())->first()?->pivot->last_read_at;
        return $this->messages()->when($lastRead, fn($q) => $q->where('created_at', '>', $lastRead))->count();
    }
}
